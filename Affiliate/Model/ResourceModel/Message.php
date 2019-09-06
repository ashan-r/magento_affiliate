<?php
namespace Mageplaza\Affiliate\Model\ResourceModel;
class Message extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('mageplaza_affiliate_account', 'account_id', 'affiliate_account');   //here "mageplaza_affiliate_account" is table name and "id" is the primary key of custom table
    }

}