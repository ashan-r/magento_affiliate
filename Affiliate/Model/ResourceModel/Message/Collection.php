<?php
namespace Mageplaza\Affiliate\Model\ResourceModel\Message;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Mageplaza\Affiliate\Model\Message',
            'Mageplaza\Affiliate\Model\ResourceModel\Message'
        );
    }
}