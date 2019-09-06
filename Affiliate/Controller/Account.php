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

namespace Mageplaza\Affiliate\Controller;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\Affiliate\Helper\Data as DataHelper;
use Mageplaza\Affiliate\Model\AccountFactory;
use Mageplaza\Affiliate\Model\TransactionFactory;
use Mageplaza\Affiliate\Model\WithdrawFactory;

/**
 * Class Account
 * @package Mageplaza\Affiliate\Controller
 */
abstract class Account extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Mageplaza\Affiliate\Model\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Mageplaza\Affiliate\Model\AccountFactory
     */
    protected $accountFactory;

    /**
     * @var \Mageplaza\Affiliate\Model\WithdrawFactory
     */
    protected $withdrawFactory;

    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Account constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Mageplaza\Affiliate\Model\TransactionFactory $transactionFactory
     * @param \Mageplaza\Affiliate\Model\AccountFactory $accountFactory
     * @param \Mageplaza\Affiliate\Model\WithdrawFactory $withdrawFactory
     * @param \Mageplaza\Affiliate\Helper\Data $dataHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        TransactionFactory $transactionFactory,
        AccountFactory $accountFactory,
        WithdrawFactory $withdrawFactory,
        DataHelper $dataHelper,
        CustomerSession $customerSession,
        Registry $registry
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->transactionFactory = $transactionFactory;
        $this->accountFactory = $accountFactory;
        $this->withdrawFactory = $withdrawFactory;
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
        $this->registry = $registry;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
