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

namespace Mageplaza\Affiliate\Model\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Mageplaza\Affiliate\Helper\Calculation\Commission;
use Mageplaza\Affiliate\Helper\Calculation\Discount;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class Affiliate
 * @package Mageplaza\Affiliate\Model\Total\Quote
 */
class Affiliate extends AbstractTotal
{
    /**
     * @var \Mageplaza\Affiliate\Helper\Calculation\Discount
     */
    protected $_discountHelper;

    /**
     * @var \Mageplaza\Affiliate\Helper\Calculation\Commission
     */
    protected $_commissionHelper;

    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Affiliate constructor.
     *
     * @param \Mageplaza\Affiliate\Helper\Calculation\Discount $discountHelper
     * @param \Mageplaza\Affiliate\Helper\Calculation\Commission $commissionHelper
     * @param \Mageplaza\Affiliate\Helper\Data $dataHelper
     */
    public function __construct(
        Discount $discountHelper,
        Commission $commissionHelper,
        Data $dataHelper
    )
    {
        $this->_discountHelper = $discountHelper;
        $this->_commissionHelper = $commissionHelper;
        $this->_dataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException *@throws \Zend_Serializer_Exception
     * @throws \Zend_Serializer_Exception
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (($quote->isVirtual() && ($this->_address->getAddressType() == 'shipping')) ||
            (!$quote->isVirtual() && ($this->_address->getAddressType() == 'billing'))
        ) {
            return $this;
        }

        $this->_discountHelper->collect($quote, $shippingAssignment, $total);
        $this->_commissionHelper->collect($quote, $shippingAssignment, $total);

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = [];
        $amount = $quote->getAffiliateDiscountAmount();
        if ($amount > 0.001) {
            $result[] = [
                'code'  => $this->getCode(),
                'title' => __('Affiliate Discount'),
                'value' => -$amount
            ];
        }

        return $result;
    }
}
