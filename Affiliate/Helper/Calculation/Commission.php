<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Affiliate
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Affiliate\Helper\Calculation;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Mageplaza\Affiliate\Block\Adminhtml\Campaign\Edit\Tab\Commissions\Arraycommission;
use Mageplaza\Affiliate\Helper\Calculation;

/**
 * Class Commission
 * @package Mageplaza\Affiliate\Helper\Calculation
 */
class Commission extends Calculation
{
    /**
     * @var null
     */
    protected $fieldSuffix = null;

    /**
     * @var null
     */
    protected $tierId = null;

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Serializer_Exception
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);
        $items = $shippingAssignment->getItems();

        if (!$this->canCalculate() || !$items) {
            return $this;
        }

        $quote->setAffiliateKey($this->getAffiliateKey());
        $itemFields = ['affiliate_commission'];
        $this->resetAffiliateData($items, $quote, array_merge($itemFields, ['affiliate_shipping_commission']), $itemFields);
        $commissionResult = [];
        $commissionData = [];
        $this->fieldSuffix = $this->hasFirstOrder() ? '_second' : '';
        $account = $this->registry->registry('mp_affiliate_account');
        $tree = $this->getAffiliateTree($account);
        $isCalculateShipping = $this->isEarnCommissionFromShipping($quote->getStoreId());
        $isCalculateTax = $this->isEarnCommissionFromTax($quote->getStoreId());
        $totalOnCart = $this->priceCurrency->convert($this->getTotalOnCart($items, $quote, $isCalculateShipping, $isCalculateTax));
        if ($totalOnCart <= 0.001) {
            return $this;
        }
        foreach ($this->getAvailableCampaign($account) as $campaign) {
            $commissions = $campaign->getCommission();
            $campaigns[] = $campaign->getId();
            $tierData = [];
            foreach ($commissions as $key => $tier) {
                if (!isset($tree[$key])) {
                    break;
                }
                // get customer id
                $this->tierId = $tree[$key];
                if (!isset($commissionResult[$this->tierId])) {
                    $commissionResult[$this->tierId] = 0;
                }

                $tierCommission = $this->getTotalCommission($totalOnCart, $tier);
                $totalCommission = 0;
                $lastItem = null;
                foreach ($items as $item) {
                    if ($item->getParentItem()) {
                        continue;
                    }

                    if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                        /** @var \Magento\Quote\Model\Quote\Item $child */
                        foreach ($item->getChildren() as $child) {
                            $this->calculateTierItem($child, $totalOnCart, $tierCommission, $campaign, $totalCommission, $isCalculateTax, $lastItem);
                        }
                    } else {
                        $this->calculateTierItem($item, $totalOnCart, $tierCommission, $campaign, $totalCommission, $isCalculateTax, $lastItem);
                    }
                }

                if ($this->isEarnCommissionFromShipping($quote->getStoreId())) {
                    $commissionShipping = $tierCommission - $totalCommission;
                    $this->setTierData($quote, $campaign, $commissionShipping, 'affiliate_shipping_commission');
                    $totalCommission += $commissionShipping;
                } else if ($lastItem && $tierCommission > $totalCommission) {
                    $lastItemCommission = $tierCommission - $totalCommission;
                    $this->setTierData($lastItem, $campaign, $lastItemCommission);
                    $totalCommission = $tierCommission;
                }

                $tierData[$this->tierId] = $totalCommission;
            }
            $commissionData[$campaign->getId()] = $tierData;
            $quote->setAffiliateCommission($this->serialize($commissionData));
        }

        return $this;
    }

    /**
     * @param $totalOnCart
     * @param $tier
     *
     * @return float|int
     */
    public function getTotalCommission($totalOnCart, $tier)
    {
        $commission = $tier['value' . $this->fieldSuffix];
        if ($tier['type' . $this->fieldSuffix] == Arraycommission::TYPE_SALE_PERCENT) {
            $commission = $this->priceCurrency->round(($commission * $totalOnCart) / 100);
        }

        return $commission;
    }

    /**
     * @param $item
     * @param $totalOnCart
     * @param $tierCommission
     * @param $campaign
     * @param $totalCommission
     * @param $isCalculateTax
     * @param $lastItem
     *
     * @throws \Zend_Serializer_Exception
     */
    public function calculateTierItem($item, $totalOnCart, $tierCommission, $campaign, &$totalCommission, $isCalculateTax, &$lastItem)
    {
        $totalItem = $this->priceCurrency->convert($this->getItemTotalForDiscount($item, $isCalculateTax));
        $commissionItem = ($totalItem / $totalOnCart) * $tierCommission;
        $commissionItem = $this->round($commissionItem, 'commission');
        $this->setTierData($item, $campaign, $commissionItem);
        $totalCommission += $commissionItem;
        $lastItem = $item;
    }

    /**
     * @param $object
     * @param $campaign
     * @param $commissionItem
     * @param string $field
     *
     * @throws \Zend_Serializer_Exception
     */
    public function setTierData($object, $campaign, $commissionItem, $field = 'affiliate_commission')
    {
        $campaignId = $campaign->getCampaignId();
        $tierId = $this->tierId;
        $affiliateCommission = [];
        if ($object->getData($field)) {
            $affiliateCommission = $this->unserialize($object->getData($field));
        }
        if (!isset($affiliateCommission[$campaignId])) {
            $affiliateCommission[$campaignId] = [];
        }
        if (!isset($affiliateCommission[$campaignId][$tierId])) {
            $affiliateCommission[$campaignId][$tierId] = 0;
        }

        $affiliateCommission[$campaignId][$tierId] += $commissionItem;
        $object->setData($field, $this->serialize($affiliateCommission));
    }

    /**
     * @param $account
     *
     * @return array
     */
    public function getAffiliateTree($account)
    {
        $tree = explode('/', $account->getTree());
        $treeResult = [];
        $tier = 1;
        while (sizeof($tree)) {
            $treeResult['tier_' . $tier++] = array_pop($tree);
        }

        return $treeResult;
    }
}
