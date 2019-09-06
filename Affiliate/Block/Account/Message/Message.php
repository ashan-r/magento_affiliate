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

namespace Mageplaza\Affiliate\Block\Account\Message;

use Magento\Framework\View\Element\Template\Context;
use Mageplaza\Affiliate\Model\MessageFactory;
/**
 * Class Transaction
 * @package Mageplaza\Affiliate\Block\Account\Withdraw
 */
class Message extends \Magento\Framework\View\Element\Template
{

    protected $customerSession;
    protected $_customer;
    protected $_customerFactory;

    public function __construct(
        Context $context,
        MessageFactory $test,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Customer $customers,
        array $data = []
    ) {
        $this->_test = $test;
        parent::__construct($context);

        $this->customerSession = $customerSession;
        $this->_customerFactory = $customerFactory;
        $this->_customer = $customers;
    }


    public function getAffUserID(){
        $customerId = $this->customerSession->getCustomer()->getId(); //ADDED LATER
        return $customerId;
    }


    public function getTestCollection()
    {
        $page = ($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
        $pageSize = ($this->getRequest()->getParam('limit'))? $this->getRequest()->getParam('limit') : 20;
        $customerId = $this->customerSession->getCustomer()->getId(); //ADDED LATER

        $test = $this->_test->create();
        $customer = $this->_customerFactory->create()->getCollection();//Added Later
        $collection = $test->getCollection();

       //$collection->addFieldToFilter('title','Test Title 01'); // if you want to use filter
        //$collection->setOrder('test_id','ASC'); // if you want to set collection order

        /*
        $joinConditions = 'customer_entity.entity_id = affiliate.customer_id';
        $collection->getSelect()->join(
            ['affiliate' => $collection->getTable('mageplaza_affiliate_account')],
            $joinConditions,
            []
        );
        */

        //  $collection ->addFieldToFilter('customer_id',  $customerId);//ADDED LATER

         $collection->setPageSize($pageSize);
         $collection->setCurPage($page);
         return $collection;
    } //Get Affiliate Account Collection

    public function getCustomerCollection()
    {
        $customerId = $this->customerSession->getCustomer()->getId(); //ADDED LATER
        $customerCollection = $this->_customerFactory->create()->getCollection();//Added Later


        //$collection->addFieldToFilter('title','Test Title 01'); // if you want to use filter
        //$collection->setOrder('test_id','ASC'); // if you want to set collection order

        /*
        $joinConditions = 'customer_entity.entity_id = affiliate.customer_id';

        $collection->getSelect()->join(
            ['affiliate' => $collection->getTable('mageplaza_affiliate_account')],
            $joinConditions,
            []
        );
        */

        //  $collection ->addFieldToFilter('customer_id',  $customerId);//ADDED LATER

        return $customerCollection;
    }



    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
}