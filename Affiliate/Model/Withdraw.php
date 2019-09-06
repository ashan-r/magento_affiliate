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

namespace Mageplaza\Affiliate\Model;

use Magento\Framework\App\Action\Context as ContextAction;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Affiliate\Helper\Payment;
use Mageplaza\Affiliate\Model\Withdraw\Status;

/**
 * Class Withdraw
 * @package Mageplaza\Affiliate\Model
 */
class Withdraw extends AbstractModel
{
    const XML_PATH_EMAIL_ENABLE = 'email/withdraw/enable';

    const XML_PATH_WITHDRAW_EMAIL_COMPLETE_TEMPLATE = 'affiliate/email/withdraw/complete';

    /**
     * Cache tag
     *
     * @var string
     */
    const CACHE_TAG = 'affiliate_withdraw';

    /**
     * Cache tag
     *
     * @var string
     */
    protected $_cacheTag = 'affiliate_withdraw';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'affiliate_withdraw';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Mageplaza\Affiliate\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * Withdraw constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Action\Context $contextAction
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Mageplaza\Affiliate\Helper\Payment $helper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ContextAction $contextAction,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Payment $helper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->storeManager = $storeManager;
        $this->messageManager = $contextAction->getMessageManager();
        $this->_paymentHelper = $helper;
        $this->objectManager = $contextAction->getObjectManager();

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mageplaza\Affiliate\Model\ResourceModel\Withdraw');
    }

    /**
     * @return $this
     */
    public function afterLoad()
    {
        parent::afterLoad();

        $paymentDetail = $this->objectManager->create('Magento\Framework\Json\Helper\Data')
            ->jsonDecode($this->getPaymentDetails());

        $this->addData($paymentDetail);

        return $this;
    }

    /**
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        parent::beforeSave();

        //set payment method detail
        $methodModel = $this->getPaymentModel();

        $this->setPaymentDetails($methodModel->getWithdrawInfoDetail());

        if (!$this->getStatus()) {
            $this->setStatus(Status::PENDING);
        }

        $editWithdraw = $this->getEditRecord();

        if ($this->isObjectNew() && !$editWithdraw) {
            $this->prepareData();
        }
    }

    /**
     * @return mixed
     * @throws \Zend_Serializer_Exception
     */
    public function getPaymentModel()
    {
        $paymentModel = $this->_paymentHelper->getMethodModel($this->getPaymentMethod());
        $paymentModel->setData('withdraw', $this);

        return $paymentModel;
    }

    /**
     * @return $this|void
     */
    public function afterSave()
    {
        parent::afterSave();

        if ($this->dataHasChangedFor('status') && ($this->getStatus() == Status::COMPLETE)) {
            $this->sendWithdrawCompleteEmail();
        }
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Serializer_Exception
     */
    public function prepareData()
    {
        $account = $this->_paymentHelper->getAffiliateAccount($this->getCustomerId(), 'customer_id');

        $this->setAccount($account)
            ->setAccountId($account->getId());

        $fee = $this->getFee();
        if (empty($fee) && $fee !== '0') {
            $this->setFee($this->_paymentHelper->getFee($this->getPaymentMethod(), $this->getAmount()));
        }

        $transferAmount = $this->getAmount() - $this->getFee();
        if ($transferAmount <= 0) {
            throw new LocalizedException(__('The amount request is not enough to pay for fee.'));
        }

        if ($this->getAccount()->getBalance() < $this->getAmount()) {
            throw new LocalizedException(__('The amount request is not enough to withdraw'));
        }

        $this->setTransferAmount($transferAmount);

        if (!$this->getTransactionId()) {
            $transaction = $this->objectManager->create('Mageplaza\Affiliate\Model\Transaction')
                ->createTransaction('withdraw/create', $this->getAccount(), $this);

            if (!$transaction || !$transaction->getId()) {
                throw new LocalizedException(__('Cannot create transaction for this withdraw.'));
            }

            $this->setTransactionId($transaction->getId());
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function cancel()
    {
        if (!$this->getId()) {
            throw new \Exception(
                __('Invalid withdraw data for canceling.')
            );
        }

        if ($this->getStatus() == Status::CANCEL) {
            throw new \Exception(
                __('Some the withdraw had cancelled.')
            );
        }

        $transaction = $this->objectManager->create('Mageplaza\Affiliate\Model\Transaction')
            ->load($this->getTransactionId());
        $transaction->cancel();

        $this->setStatus(Status::CANCEL)
            ->save();

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function approve()
    {
        if (!$this->getId() || $this->getStatus() >= Status::COMPLETE) {
            throw new \Exception(
                __('Invalid withdraw data for approved.')
            );
        }

        $this->setStatus(Status::COMPLETE)
            ->save();

        return $this;
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param null $status
     *
     * @return mixed
     */
    public function getStatusLabel($status = null)
    {
        if ($status == null) {
            $status = $this->getStatus();
        }
        $statusHash = $this->objectManager->create('\Mageplaza\Affiliate\Model\Withdraw\Status')->getOptionHash();

        return $statusHash[$status];
    }

    /**
     * @param null $payment
     *
     * @return \Magento\Framework\Phrase|null
     * @throws \Zend_Serializer_Exception
     */
    public function getPaymentLabel($payment = null)
    {
        if ($payment == null) {
            $payment = $this->getPaymentMethod();
        }

        $payments = $this->_paymentHelper->getAllMethods();

        if (isset($payments[$payment])) {
            return __($payments[$payment]['label']);
        }

        return $payment;
    }

    /**
     * @return bool
     */
    public function canCancel()
    {
        return $this->getStatus() == Status::PENDING;
    }

    /**
     * @return mixed
     */
    public function getAffiliateAccount()
    {
        if (!$this->hasData('affiliate_account')) {
            $this->setData('affiliate_account',
                           $this->objectManager->create('\Mageplaza\Affiliate\Model\Account')->load($this->getAccountId())
            );
        }

        return $this->getData('affiliate_account');
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->objectManager->create('\Magento\Customer\Model\Customer')->load($this->getCustomerId());
    }

    /**
     * @return mixed
     */
    public function getPricingHelper()
    {
        return $this->objectManager->create('\Magento\Framework\Pricing\Helper\Data');
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getAmountFormated($store)
    {
        return $this->getPricingHelper()->currencyByStore($this->getAmount(), $store->getId(), true, false);
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getFeeAmountFormated($store)
    {
        return $this->getPricingHelper()->currencyByStore($this->getFee(), $store->getId(), true, false);
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getTransferAmountFormated($store)
    {
        return $this->getPricingHelper()->currencyByStore($this->getTransferAmount(), $store->getId(), true, false);
    }

    /**
     * @return $this
     */
    public function sendWithdrawCompleteEmail()
    {
        $account = $this->getAffiliateAccount();
        if (!$this->_paymentHelper->allowSendEmail($account, self::XML_PATH_EMAIL_ENABLE)) {
            return $this;
        }

        $customer = $this->getCustomer();
        if (!$customer || !$customer->getId()) {
            return $this;
        }

        try {
            $this->_paymentHelper->sendEmailTemplate(
                $customer,
                self::XML_PATH_WITHDRAW_EMAIL_COMPLETE_TEMPLATE,
                ['account' => $account, 'withdraw' => $this]);
        } catch (\Exception $e) {
        }

        return $this;
    }
}
