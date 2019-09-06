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

namespace Mageplaza\Affiliate\Block\Account;
/**
 * Class Refer
 * @package Mageplaza\Affiliate\Block\Account
 */
class Message extends \Mageplaza\Affiliate\Block\Account
{
    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Send Message to Your Affiliate Members'));


//        var_dump($this->customerSession->getCustomer()->getId());
//        exit();

        //Added from test
        if ($this->getTestCollection()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'mageplaza.affiliate.pager'
            )->setAvailableLimit(array(5=>5,10=>10,15=>15))->setShowPerPage(true)->setCollection(
                $this->getTestCollection()
            );
            $this->setChild('pager', $pager);
            $this->getTestCollection()->load();
        }


        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getSendMailUrl()
    {
        return $this->getUrl('*/*/message');
    }

    /**
     * @return string
     */
    public function getSharingUrl()
    {
        return $this->_affiliateHelper->getSharingUrl();
    }

    /**
     * @return string
     */
    public function getSharingEmail()
    {
        return $this->getCustomer()->getEmail();
    }

    /**
     * @return mixed
     */
    public function getSharingCode()
    {
        return $this->getCurrentAccount()->getCode();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSocialContent()
    {
        $content = $this->_affiliateHelper->getDefaultMessageShareViaSocial();
        $storeModel = $this->_storeManager->getStore();

        return str_replace([
            '{{store_name}}',
            '{{refer_url}}'
        ], [
            $storeModel->getFrontendName(),
            $this->getSharingUrl()
        ], $content);
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEmailContent()
    {
        $content = $this->_affiliateHelper->getDefaultEmailBody();
        $storeModel = $this->_storeManager->getStore();

        return str_replace([
            '{{store_name}}',
            '{{refer_url}}',
            '{{account_name}}'
        ], [
            $storeModel->getFrontendName(),
            $this->getSharingUrl(),
            $this->getCustomer()->getName()
        ], $content);
    }

}
