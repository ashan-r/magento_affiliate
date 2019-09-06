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

namespace Mageplaza\Affiliate\Controller\Adminhtml\Account;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\Affiliate\Controller\Adminhtml\Account;
use Mageplaza\Affiliate\Model\AccountFactory;

/**
 * Class Edit
 * @package Mageplaza\Affiliate\Controller\Adminhtml\Account
 */
class Edit extends Account
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Mageplaza\Affiliate\Model\AccountFactory $accountFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        AccountFactory $accountFactory,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;

        parent::__construct($context, $accountFactory, $coreRegistry, $resultPageFactory);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Mageplaza_Affiliate::account');
        $resultPage->getConfig()->getTitle()->set(__('Accounts'));

        /** @var \Mageplaza\Affiliate\Model\Account $account */
        $account = $this->_initAccount();

        $data = $this->_getSession()->getData('affiliate_account_data', true);
        if (!empty($data)) {
            $account->setData($data);
        }
        $this->_coreRegistry->register('current_account', $account);

        $title = $account->getId() ? __('Edit Account "%1"', $account->getId()) : __('New Account');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
