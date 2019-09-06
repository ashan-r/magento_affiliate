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

namespace Mageplaza\Affiliate\Helper;

use Magento\Backend\Model\Session\Quote;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cms\Model\BlockFactory;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\CalculatorFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Affiliate\Model\AccountFactory;
use Mageplaza\Affiliate\Model\CampaignFactory;
use Mageplaza\Affiliate\Model\Config\Source\Urlparam;
use Mageplaza\Affiliate\Model\Config\Source\Urltype;
use Mageplaza\Affiliate\Model\TransactionFactory;
use Mageplaza\Core\Helper\AbstractData;
use Zend\Validator\EmailAddress;

/**
 * Class Data
 * @package Mageplaza\Affiliate\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'affiliate';

    const AFFILIATE_COOKIE_NAME = 'affiliate_key';

    const AFFILIATE_COOKIE_SOURCE_NAME = 'affiliate_source';

    const AFFILIATE_COOKIE_SOURCE_VALUE = 'affiliate_source_value';

    const XML_PATH_EMAIL_SENDER = 'affiliate/email/sender';

    /**
     * @var AccountFactory
     */
    protected $accountFactory;

    /**
     * @var CampaignFactory
     */
    protected $campaignFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * CookieManager
     *
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * Block factory
     *
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var array
     */
    static protected $_key = [];

    /**
     * @var
     */
    protected $_store;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var $_layout
     */
    protected $_layout;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Checkout Session
     */
    protected $_checkoutSession;

    /**
     * @var array
     */
    static private $_affCache = [];

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Mageplaza\Affiliate\Model\AccountFactory $accountFactory
     * @param \Mageplaza\Affiliate\Model\CampaignFactory $campaignFactory
     * @param \Mageplaza\Affiliate\Model\TransactionFactory $transactionFactory
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManagerInterface
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        AccountFactory $accountFactory,
        CampaignFactory $campaignFactory,
        TransactionFactory $transactionFactory,
        BlockFactory $blockFactory,
        CustomerFactory $customerFactory,
        CookieManagerInterface $cookieManagerInterface,
        CustomerSession $customerSession,
        CookieMetadataFactory $cookieMetadataFactory,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        CustomerViewHelper $customerViewHelper,
        LayoutInterface $layout,
        Registry $registry
    )
    {
        $this->_blockFactory = $blockFactory;
        $this->accountFactory = $accountFactory;
        $this->customerFactory = $customerFactory;
        $this->campaignFactory = $campaignFactory;
        $this->transactionFactory = $transactionFactory;
        $this->_customerSession = $customerSession;
        $this->cookieManager = $cookieManagerInterface;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->priceCurrency = $priceCurrency;
        $this->transportBuilder = $transportBuilder;
        $this->customerViewHelper = $customerViewHelper;
        $this->_layout = $layout;
        $this->registry = $registry;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /** ============================================ General ========================================================
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultPage($storeId = null)
    {
        return $this->getConfigGeneral('page/welcome', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isOverwriteCookies($storeId = null)
    {
        return $this->getConfigGeneral('overwrite_cookies', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUrlCodeLength($storeId = null)
    {
        return $this->getConfigGeneral('url/code_length', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isProcessRefund($storeId = null)
    {
        return $this->getModuleConfig('commission/process/refund', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUrlPrefix($storeId = null)
    {
        return $this->getConfigGeneral('url/prefix', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUrlType($storeId = null)
    {
        return $this->getConfigGeneral('url/type', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getExpiredTime($storeId = null)
    {
        return $this->getConfigGeneral('expired_time', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCustomCss($storeId = null)
    {
        return $this->getConfigGeneral('custom_css', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getGeneralUrlParam($storeId = null)
    {
        return $this->getConfigGeneral('url/param', $storeId);
    }

    /** ============================================== Account ========================================================
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnableTermsAndConditions($storeId = null)
    {
        return $this->getModuleConfig('account/term_conditions/enable', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAffiliateAccountSignUp($storeId = null)
    {
        return $this->getModuleConfig('account/sign_up', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getTermsAndConditionsTitle($storeId = null)
    {
        return $this->getModuleConfig('account/term_conditions/title', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getTermsAndConditionsHtml($storeId = null)
    {
        return $this->getModuleConfig('account/term_conditions/html', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getTermsAndConditionsCheckboxText($storeId = null)
    {
        return $this->getModuleConfig('account/term_conditions/checkbox_text', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isCheckedEmailNotification($storeId = null)
    {
        return $this->getModuleConfig('account/term_conditions/default_checkbox', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultGroup($storeId = null)
    {
        return $this->getModuleConfig('account/sign_up/default_group', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isAdminApproved($storeId = null)
    {
        return $this->getModuleConfig('account/sign_up/admin_approved', $storeId);
    }

    /** ============================================ Commission ========================================================
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEarnCommissionFromTax($storeId = null)
    {
        return $this->getModuleConfig('commission/by_tax', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEarnCommissionFromShipping($storeId = null)
    {
        return $this->getModuleConfig('commission/shipping', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEarnCommissionInvoiceAfter($storeId = null)
    {
        return $this->getModuleConfig('commission/process/earn_commission_invoice', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function showAffiliateLinkOn($storeId = null)
    {
        return $this->getConfigGeneral('show_link', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCommissionHoldingDays($storeId = null)
    {
        return $this->getModuleConfig('commission/process/holding_days', $storeId);
    }

    /** ============================================== Withdraw ========================================================
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getWithdrawMinimumBalance($storeId = null)
    {
        return floatval($this->getModuleConfig('withdraw/minimum_balance', $storeId));
    }

    /**
     * @param null $storeId
     *
     * @return float
     */
    public function getWithdrawMinimum($storeId = null)
    {
        return floatval($this->getModuleConfig('withdraw/minimum', $storeId));
    }

    /**
     * @param null $storeId
     *
     * @return float
     */
    public function getWithdrawMaximum($storeId = null)
    {
        return floatval($this->getModuleConfig('withdraw/maximum', $storeId));
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isAllowWithdrawRequest($storeId = null)
    {
        return $this->getModuleConfig('withdraw/allow_request', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getPaymentMethod($storeId = null)
    {
        return $this->getModuleConfig('withdraw/payment_method', $storeId);
    }

    /** ============================================== Refer ========================================================
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getReferringPage($storeId = null)
    {
        return $this->getModuleConfig('refer/referring_page', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAddThisPubId($storeId = null)
    {
        return $this->getModuleConfig('refer/addthis_pubid', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCloudsponge($storeId = null)
    {
        return $this->getModuleConfig('refer/cloudsponge', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCloudspongeKey($storeId = null)
    {
        return $this->getModuleConfig('refer/cloudsponge_key', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultEmailSubject($storeId = null)
    {
        return $this->getModuleConfig('refer/sharing_content/subject', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultMessageShareViaSocial($storeId = null)
    {
        return $this->getModuleConfig('refer/sharing_content/social_content', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultEmailBody($storeId = null)
    {
        return $this->getModuleConfig('refer/sharing_content/email_content', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultReferLink($storeId = null)
    {
        return $this->getModuleConfig('refer/default_link', $storeId);
    }

    /**
     * @param $cacheKey
     *
     * @return bool
     */
    public static function hasCache($cacheKey)
    {
        if (isset(self::$_affCache[$cacheKey])) {
            return true;
        }

        return false;
    }

    /**
     * @param $cacheKey
     * @param null $value
     */
    public static function saveCache($cacheKey, $value = null)
    {
        self::$_affCache[$cacheKey] = $value;

        return;
    }

    /**
     * @param $cacheKey
     *
     * @return mixed|null
     */
    public static function getCache($cacheKey)
    {
        if (isset(self::$_affCache[$cacheKey])) {
            return self::$_affCache[$cacheKey];
        }

        return null;
    }

    /**
     * @param $value
     * @param null $code
     *
     * @return mixed
     */
    public function getAffiliateAccount($value, $code = null)
    {
        if ($code) {
            $account = $this->accountFactory->create()->load($value, $code);
        } else {
            $account = $this->accountFactory->create()->load($value);
        }

        return $account;
    }

    /**
     * @return mixed
     */
    public function getCurrentAffiliate()
    {
        $customerId = $this->_customerSession->getCustomerId();

        return $this->getAffiliateAccount($customerId, 'customer_id');
    }

    /**
     * Get affiliate key
     * if customer has referred by an other affiliate (has order already), get key from that order
     * else get key from cookie
     *
     * @return null|string
     */
    public function getAffiliateKey()
    {
        $key = $this->getAffiliateKeyFromCookie();
        if ($this->hasFirstOrder()) {
            $key = $this->getFirstAffiliateOrder()->getAffiliateKey();
        }

        return $key;
    }

    /**
     * Check customer has referred or not
     *
     * @return bool
     */
    public function hasFirstOrder()
    {
        $firstOrder = $this->getFirstAffiliateOrder();
        if ($firstOrder && $firstOrder->getId()) {
            return true;
        }

        return false;
    }

    /**
     * Get first order which has been referred by an affiliate
     *
     * @return mixed
     */
    public function getFirstAffiliateOrder()
    {
        $cacheKey = 'affiliate_first_order';
        if (!$this->hasCache($cacheKey)) {
            $customer = $this->getCustomer();
            if ($customer && $customer->getId()) {
                $order = $this->objectManager->create('Magento\Sales\Model\Order')
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $customer->getId())
                    ->addFieldToFilter('affiliate_key', ['notnull' => true]);

                $this->saveCache($cacheKey, $order->getFirstItem());
            }
        }

        return $this->getCache($cacheKey);
    }

    /**
     * Get customer email by order id
     *
     * @param $orderId
     *
     * @return string
     */
    public function getCustomerEmailByOId($orderId)
    {
        $customer_email = '';
        if ($orderId) {
            $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $customer_email = $order->getCustomerEmail();
        }

        return $customer_email;
    }

    /**
     * Email will be sent or not
     *
     * @param $account
     * @param $xmlEnablePath
     *
     * @return bool
     */
    public function allowSendEmail($account, $xmlEnablePath)
    {
        if (!$this->getModuleConfig($xmlEnablePath) || !$account->getEmailNotification()) {
            return false;
        }

        return true;
    }

    /**
     * @param $customer
     * @param $template
     * @param array $templateParams
     * @param string $sender
     * @param null $storeId
     * @param null $email
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendEmailTemplate(
        $customer,
        $template,
        $templateParams = [],
        $sender = self::XML_PATH_EMAIL_SENDER,
        $storeId = null,
        $email = null
    )
    {
        $templateId = $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        $templateParams['recipient'] = $customer;

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $this->getWebsiteStoreId($customer, $storeId)])
            ->setTemplateVars($templateParams)
            ->setFrom($this->scopeConfig->getValue($sender, ScopeInterface::SCOPE_STORE, $storeId))
            ->addTo($email, $customer->getName())
            ->getTransport();

        $transport->sendMessage();

        return $this;
    }

    /**
     * Get either first store ID from a set website or the provided as default
     *
     * @param $customer
     * @param null $defaultStoreId
     *
     * @return int|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getWebsiteStoreId($customer, $defaultStoreId = null)
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $defaultStoreId = current($storeIds);
        }

        if (empty($defaultStoreId)) {
            $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();
        }

        return $defaultStoreId;
    }

    /**
     * @param $blockIdentify
     * @param bool $title
     *
     * @return array|string
     */
    public function loadCmsBlock($blockIdentify, $title = false)
    {
        $html = '';
        $titleHtml = '';
        if ($blockIdentify) {
            $block = $this->_blockFactory->create()
                ->load($blockIdentify, 'identifier');
            if ($block->getIsActive()) {
                $titleHtml = $block->getTitle();
                $html = $this->_layout->createBlock('Magento\Cms\Block\Block')->setBlockId($blockIdentify)->toHtml();
            }
        }

        if ($title) {
            return [
                'title'   => $titleHtml,
                'content' => $html
            ];
        }

        return $html;
    }

    /**
     * @return null|string
     */
    public function getCustomerReferBy()
    {
        $key = $this->getAffiliateKey();
        $account = $this->accountFactory->create()->loadByCode($key);

        if (!$account->getId()) {
            $account = $this->accountFactory->create()->load($key);
        }

        if ($account->getId()) {
            return $this->getCustomerEmailByAccount($account);
        }

        return null;
    }

    /**
     * @param $input
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffiliateByEmailOrCode($input)
    {
        $account = $this->accountFactory->create();

        $validator = new EmailAddress();
        if ($validator->isValid($input)) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId)->loadByEmail($input);
            if ($customer && $customer->getId()) {
                $account->loadByCustomer($customer);
            }
        } else {
            $account->loadByCode($input);
        }

        return $account->getId();
    }

    /**
     * @param $account
     *
     * @return string
     */
    public function getCustomerEmailByAccount($account)
    {
        $customerId = '';
        if (is_object($account)) {
            $customerId = $account->getCustomerId();
        } else {
            $account = $this->accountFactory->create()->load($account);
            if ($account->getId()) {
                $customerId = $account->getCustomerId();
            }
        }

        $customer = $this->customerFactory->create()->load($customerId);
        if ($customer->getId()) {
            return $customer->getEmail();
        }

        return '';
    }

    /**
     * @param null $key
     *
     * @return mixed
     */
    public function getAffiliateKeyFromCookie($key = null)
    {
        if (is_null($key)) {
            $key = self::AFFILIATE_COOKIE_NAME;
        }

        if (!isset(self::$_key[$key])) {
            self::$_key[$key] = $this->cookieManager->getCookie($key);
        }

        return self::$_key[$key];
    }

    /**
     * @param $code
     * @param null $key
     *
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setAffiliateKeyToCookie($code, $key = null)
    {
        $expirationDay = (int)$this->getConfigGeneral('expired_time');
        $period = $expirationDay * 24 * 3600;
        if (is_null($key)) {
            $key = self::AFFILIATE_COOKIE_NAME;
        }

        if ($this->cookieManager->getCookie($key)) {
            $this->cookieManager->deleteCookie($key,
                                               $this->cookieMetadataFactory
                                                   ->createCookieMetadata()
                                                   ->setPath('/')
                                                   ->setDomain(null)
            );
        }

        $this->cookieManager->setPublicCookie($key, $code,
                                              $this->cookieMetadataFactory
                                                  ->createPublicCookieMetadata()
                                                  ->setDuration($period)
                                                  ->setPath('/')
                                                  ->setDomain(null)
        );

        self::$_key[$key] = $code;

        return $this;
    }

    /**
     * @param null $key
     *
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function deleteAffiliateKeyFromCookie($key = null)
    {
        if (is_null($key)) {
            $key = self::AFFILIATE_COOKIE_NAME;
        }

        if ($this->cookieManager->getCookie($key)) {
            $this->cookieManager->deleteCookie($key,
                                               $this->cookieMetadataFactory
                                                   ->createCookieMetadata()
                                                   ->setPath('/')
                                                   ->setDomain(null)
            );
        }

        self::$_key[$key] = null;

        return $this;
    }

    /**
     * @param null $url
     * @param array $params
     * @param null $urlType
     *
     * @return string
     */
    public function getSharingUrl($url = null, $params = [], $urlType = null)
    {
        $url = $url ?: $this->getDefaultReferLink();
        if (!$url) {
            $url = $this->_urlBuilder->getUrl('affiliate/index/index');
        }

        $prefix = $this->getUrlPrefix() ?: 'u';
        $urlType = $urlType ?: $this->getUrlType();
        $accountCode = $this->getCurrentAffiliate()->getCode();

        if ($this->getGeneralUrlParam() == Urlparam::PARAM_ID) {
            $accountCode = $this->getCurrentAffiliate()->getId();
        }
        if ($urlType == Urltype::TYPE_HASH) {
            $param = '#' . $prefix . $accountCode;

            return trim($url, '/') . $param;
        }

        $params[$prefix] = $accountCode;
        $param = '';
        foreach ($params as $key => $code) {
            $paramPrefix = ($param != '') ? '&' : '?';
            $param .= $paramPrefix . $key . '=' . urlencode($code);
        }

        return trim($url, '/') . $param;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->_customerSession->getCustomer();
    }

    /**
     * @param $router
     * @param array $param
     *
     * @return string
     */
    public function getAffiliateUrl($router, $param = [])
    {
        return $this->_getUrl($router, $param);
    }

    /**
     * @param $price
     *
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->priceCurrency->convertAndFormat($price, false);
    }

    /**
     * @return \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    public function getPriceCurrency()
    {
        return $this->priceCurrency;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getCreditTitle()
    {
        $account = $this->getCurrentAffiliate();

        return __('My Credit (%1)', $this->formatPrice($account->getBalance()));
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCheckoutSession()
    {
        if (!$this->_checkoutSession) {
            $this->_checkoutSession = $this->objectManager->get($this->isAdmin() ? Quote::class : CheckoutSession::class);
        }

        return $this->_checkoutSession;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAdmin()
    {
        /** @var \Magento\Framework\App\State $state */
        $state = $this->objectManager->get('Magento\Framework\App\State');

        return $state->getAreaCode() == Area::AREA_ADMINHTML;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAffiliateDiscount()
    {
        $affDiscountData = $this->getCheckoutSession()->getAffDiscountData();
        if (!is_array($affDiscountData) || $this->hasFirstOrder()) {
            $affDiscountData = [];
        }

        return $affDiscountData;
    }

    /**
     * @param $affiliateDiscount
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveAffiliateDiscount($affiliateDiscount)
    {
        $affDiscountData = $this->getAffiliateDiscount();
        $this->getCheckoutSession()->setAffDiscountData(array_merge($affDiscountData, $affiliateDiscount));

        return $this;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getAffiliateByKeyOrCode($key)
    {
        $account = $this->accountFactory->create()->loadByCode($key);
        if (!$account->getId()) {
            $account = $this->accountFactory->create()->load($key);
        }

        return $account;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function isEnableReferFriend($store = null)
    {
        return $this->getModuleConfig('refer/enable', $store);
    }

    /**
     * @param $object
     *
     * @return null|string
     * @throws \Zend_Serializer_Exception
     */
    public function getSerializeString($object)
    {
        if (is_null($object)) {
            return null;
        }

        return $this->serialize($object);
    }

    /**
     * @param $object
     *
     * @return mixed|null
     * @throws \Zend_Serializer_Exception
     */
    public function getArrayUnserialize($object)
    {
        if (is_null($object)) {
            return null;
        }

        return $this->unserialize($object);
    }

    /**
     * @param $fieldset
     * @param $prefix
     * @param $url
     * @param $action
     */
    public function addCustomerEmailFieldset($fieldset, $prefix, $url, $action)
    {
        $fieldset->addField('customer_email', 'text', [
            'label'    => __('Account'),
            'name'     => 'customer_email',
            'required' => true,
            'readonly' => true,
            'style'    => 'background-color:white;opacity: 1;cursor: pointer; '
        ])->setAfterElementHtml(
            '<div id="customer-grid" style="display:none"></div>
                <script type="text/x-magento-init">
                    {
                        "#' . $prefix . '_customer_email": {
                            "Mageplaza_Affiliate/js/customer":{
                                "url": "' . $url . '",
                                "prefix" : "' . $prefix . '",
                                "action" : "' . $action . '"
                            }
                        }
                    }
                </script>'
        );
    }

    /**
     * @param $string
     *
     * @return mixed
     * @throws \Zend_Serializer_Exception
     */
    public function unserialize($string)
    {
        if ($string) {
            return parent::unserialize($string);
        }

        return [];
    }
}
