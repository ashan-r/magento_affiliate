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

namespace Mageplaza\Affiliate\Model\ResourceModel\Campaign;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mageplaza\Affiliate\Model\Campaign\Status;

/**
 * Class Collection
 * @package Mageplaza\Affiliate\Model\ResourceModel\Campaign
 */
class Collection extends AbstractCollection
{
    /**
     * ID Field Name
     *
     * @var string
     */
    protected $_idFieldName = 'campaign_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'mageplaza_affiliate_campaign_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'campaign_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mageplaza\Affiliate\Model\Campaign', 'Mageplaza\Affiliate\Model\ResourceModel\Campaign');
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);

        return $countSelect;
    }

    /**
     * @param string $valueField
     * @param string $labelField
     * @param array $additional
     *
     * @return array
     */
    protected function _toOptionArray($valueField = 'campaign_id', $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    /**
     * @param $affiliateGroupId
     * @param $websiteId
     * @param bool $filterDate
     * @param null $customerGroupId
     *
     * @return $this
     */
    public function getAvailableCampaign($affiliateGroupId, $websiteId, $filterDate = true, $customerGroupId = null)
    {
        $this->addFieldToFilter('website_ids', ['finset' => $websiteId])
            ->addFieldToFilter('status', Status::ENABLED);

        if (!is_null($customerGroupId)) {
            $this->addFieldToFilter('customer_group_ids', ['finset' => (int)$customerGroupId]);
        }

        if (!is_null($affiliateGroupId)) {
            $this->addFieldToFilter('affiliate_group_ids', ['finset' => (int)$affiliateGroupId]);
        }

        if ($filterDate) {
            if ($filterDate === true) {
                $filterDate = date('Y-m-d');
            }
            $this->getSelect()->where('(from_date IS NULL) OR (date(from_date) <= date(?))', $filterDate);
            $this->getSelect()->where('(to_date IS NULL) OR (date(to_date) >= date(?))', $filterDate);
        }
        $this->setOrder('sort_order', self::SORT_ORDER_ASC);

        return $this;
    }
}
