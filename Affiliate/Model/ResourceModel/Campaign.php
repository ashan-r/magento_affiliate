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

namespace Mageplaza\Affiliate\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;
use Mageplaza\Affiliate\Helper\Data as Helper;

/**
 * Class Campaign
 * @package Mageplaza\Affiliate\Model\ResourceModel
 */
class Campaign extends AbstractDb
{
    /**
     * Date time handler
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     *
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $helper;

    /**
     * Campaign constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param Helper $helper
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
        DateTime $dateTime,
        Helper $helper,
        Context $context
    )
    {
        $this->_dateTime = $dateTime;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mageplaza_affiliate_campaign', 'campaign_id');
    }

    /**
     * @param $id
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCampaignNameById($id)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from($this->getMainTable(), 'name')
            ->where('campaign_id = :campaign_id');
        $binds = ['campaign_id' => (int)$id];

        return $adapter->fetchOne($select, $binds);
    }

    /**
     * @inheritdoc
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if (is_array($object->getWebsiteIds())) {
            $object->setWebsiteIds(implode(',', $object->getWebsiteIds()));
        }

        if (is_array($object->getAffiliateGroupIds())) {
            $object->setWebsiteIds(implode(',', $object->getAffiliateGroupIds()));
        }
        $object->setUpdatedAt((new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT));
        if ($object->isObjectNew()) {
            $object->setCreatedAt((new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT));
        }
        if ($object->getCommission() && is_array($object->getCommission())) {
            $object->setCommission($this->helper->serialize($object->getCommission()));
        } else {
            $object->setCommission($this->helper->serialize([]));
        }

        foreach (['from_date', 'to_date'] as $field) {
            $value = !$object->getData($field) ? null : $object->getData($field);
            $object->setData($field, $this->_dateTime->formatDate($value));
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     * @throws \Zend_Serializer_Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);
        //fixbug unserialize $config  = null for m2 v2.1 EE
        if (!is_null($object->getCommission())) {
            $object->setCommission($this->helper->unserialize($object->getCommission()));
        } else {
            $object->setCommission(null);
        }

        return $this;
    }
}
