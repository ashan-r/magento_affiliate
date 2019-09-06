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

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Mageplaza\Affiliate\Helper\Data as DataHelper;
use Mageplaza\Affiliate\Model\Account\Status;

/**
 * Class Account
 * @package Mageplaza\Affiliate\Model
 */
class Account extends AbstractModel
{
    const XML_PATH_EMAIL_ENABLE = 'email/account/enable';

    const XML_PATH_ACCOUNT_EMAIL_WELCOME_TEMPLATE = 'affiliate/email/account/welcome';

    const XML_PATH_ACCOUNT_EMAIL_APPROVE_TEMPLATE = 'affiliate/email/account/approve';

    /**
     * Cache tag
     *
     * @var string
     */
    const CACHE_TAG = 'affiliate_account';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'affiliate_account';

    /**
     * @type \Mageplaza\Affiliate\Helper\Data
     */
    protected $_helper;

    /**
     * @type \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Object Manager
     *
     * @type
     */
    protected $objectManager;

    /**
     * Account constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DataHelper $helper
     * @param CustomerFactory $customerFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataHelper $helper,
        CustomerFactory $customerFactory,
        ObjectManagerInterface $objectmanager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_helper = $helper;
        $this->_customerFactory = $customerFactory;
        $this->objectManager = $objectmanager;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mageplaza\Affiliate\Model\ResourceModel\Account');
    }

    /**
     * @return $this|void
     */
    public function afterSave()
    {
        parent::afterSave();

        if ($this->isObjectNew()) {
            $this->sendWelcomeEmail();
        }

        if ($this->hasDataChanges() &&
            $this->getOrigData('status') == Status::NEED_APPROVED &&
            $this->getData('status') == Status::ACTIVE
        ) {
            $this->sendApproveEmail();
        }
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
     * @param $code
     *
     * @return $this
     */
    public function loadByCode($code)
    {
        return $this->load($code, 'code');
    }

    /**
     * @param $customer
     *
     * @return $this
     */
    public function loadByCustomer($customer)
    {
        return $this->loadByCustomerId($customer->getId());
    }

    /**
     * @param $customerId
     *
     * @return $this
     */
    public function loadByCustomerId($customerId)
    {
        return $this->load($customerId, 'customer_id');
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        $customer = $this->_customerFactory->create()->load($this->getCustomerId());

        return $customer;
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
    public function getBalanceFormated($store)
    {
        return $this->getPricingHelper()->currencyByStore($this->getBalance(), $store->getId(), true, false);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getStatus() == Status::ACTIVE;
    }

    /**
     * @return void
     */
    public function sendWelcomeEmail()
    {
        $this->_sendEmail(self::XML_PATH_ACCOUNT_EMAIL_WELCOME_TEMPLATE);
    }

    /**
     * @return void
     */
    public function sendApproveEmail()
    {
        $this->_sendEmail(self::XML_PATH_ACCOUNT_EMAIL_APPROVE_TEMPLATE);
    }

    /**
     * @param $template
     *
     * @return $this
     */
    protected function _sendEmail($template)
    {
        if (!$this->_helper->allowSendEmail($this, self::XML_PATH_EMAIL_ENABLE)) {
            return $this;
        }

        $customer = $this->getCustomer();
        if (!$customer || !$customer->getId()) {
            return $this;
        }

        try {
            $this->_helper->sendEmailTemplate($customer, $template, ['account' => $this]);
        } catch (\Exception $e) {
        }

        return $this;
    }
}
