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

namespace Mageplaza\Affiliate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\AccountFactory;
use Mageplaza\Affiliate\Model\Config\Source\Urlparam;

/**
 * Class PredispatchObserver
 * @package Mageplaza\Affiliate\Observer
 */
class PredispatchObserver implements ObserverInterface
{
    /**
     * @var AccountFactory
     */
    protected $_accountFactory;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var Urlparam
     */
    protected $urlParam;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PredispatchObserver constructor.
     *
     * @param AccountFactory $accountFactory
     * @param Data $helper
     * @param Urlparam $urlParam
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AccountFactory $accountFactory,
        Data $helper,
        Urlparam $urlParam,
        StoreManagerInterface $storeManager
    )
    {
        $this->_accountFactory = $accountFactory;
        $this->_helper = $helper;
        $this->urlParam = $urlParam;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException *@throws
     *     \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $urlPrefix = $this->_helper->getUrlPrefix() ?: 'u';
        $key = $observer->getRequest()->getParam($urlPrefix);
        if (!$this->_helper->isEnabled() || !$key) {
            return $this;
        }

        $account = $this->_accountFactory->create();

        $urlParams = $this->urlParam->getOptionHash();
        foreach ($urlParams as $code => $label) {
            $account->load($key, $code);
            if ($account->getId()) {
                if ($account->isActive()) {
                    $isSetCookie = false;
                    if ($this->_helper->getAffiliateKeyFromCookie()) {
                        if ($this->_helper->isOverwriteCookies()) {
                            $isSetCookie = true;
                        }
                    } else {
                        $isSetCookie = false;
                    }

                    if (!$isSetCookie && !$this->_helper->getAffiliateKeyFromCookie()) {
                        $isSetCookie = true;
                    }

                    if ($isSetCookie) {
                        $this->setAffiliateKeyToCookie($observer->getRequest(), $account->getCode());
                        $redirectUrl = $this->storeManager->getStore()->getBaseUrl();
                        $url = $this->_helper->getDefaultReferLink();

                        if ($url) {
                            $redirectUrl = $url;
                        }
                        $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
                    }
                }
                break;
            }
        }

        return $this;
    }

    /**
     * @param $request
     * @param $code
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setAffiliateKeyToCookie($request, $code)
    {
        $this->_helper->setAffiliateKeyToCookie($code);
        if ($source = $request->getParam('source')) {
            $this->_helper->setAffiliateKeyToCookie($source, Data::AFFILIATE_COOKIE_SOURCE_NAME);
            if ($key = $request->getParam('key')) {
                $this->_helper->setAffiliateKeyToCookie($key, Data::AFFILIATE_COOKIE_SOURCE_VALUE);
            }
        }
    }
}
