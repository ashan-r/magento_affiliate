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

/**
 * Class Payment
 * @package Mageplaza\Affiliate\Helper
 */
class Payment extends Data
{
    const CONFIG_PAYMENT_METHODS = 'payment_method';

    const SYSTEM_PAYMENT_METHODS = 'withdraw';

    const PAYMENT_METHOD_SELECT_NAME = 'payment_method';

    /**
     * @var null
     */
    private $_methods = null;

    /**
     * @var null
     */
    private $_activeMethods = null;

    /**
     * @param $code
     *
     * @return mixed
     * @throws \Zend_Serializer_Exception
     */
    public function getMethodModel($code)
    {
        $method = $this->getAllMethods();
        $methodModel = $this->objectManager->create($method[$code]['model']);

        return $methodModel;
    }

    /**
     * @return mixed|null
     * @throws \Zend_Serializer_Exception
     */
    public function getActiveMethods()
    {
        if (is_null($this->_activeMethods)) {
            $methods = $this->getAllMethods();
            foreach ($methods as $code => $config) {
                if (!isset($config['active']) || !$config['active']) {
                    unset($methods[$code]);
                }
            }
            $this->_activeMethods = $methods;
        }

        return $this->_activeMethods;
    }

    /**
     * @return mixed|null
     * @throws \Zend_Serializer_Exception
     */
    public function getAllMethods()
    {
        $methodConfig = null;
        if (is_null($this->_methods)) {
            $methodConfig = $this->getPaymentMethod();
            //fixbug unserialize $config  = null for m2 v2.1 EE
            if (!is_null($methodConfig)) {
                $methodConfig = $this->unserialize($methodConfig);
            }

            /**
             * Get default payment method from default config.xml
             */
            $initialMethod = $this->getModuleConfig('payment_method');

            if ($initialMethod) {
                foreach ($initialMethod as $code => $method) {
                    if (isset($methodConfig[$code])) {
                        $initialMethod[$code] = array_merge($method, $methodConfig[$code]);
                    }
                }
            }

            $this->_methods = $initialMethod;
        }

        return $this->_methods;
    }

    /**
     * @param $code
     * @param $amount
     *
     * @return float|int
     * @throws \Zend_Serializer_Exception
     */
    public function getFee($code, $amount)
    {
        $methodConfig = $this->getAllMethods();

        if (!empty($methodConfig) && isset($methodConfig[$code]) && isset($methodConfig[$code]['fee'])) {
            $feeConfig = $methodConfig[$code]['fee'];
            if (strpos($feeConfig, '%') !== false) {
                $fee = floatval(trim($feeConfig, '%'));

                return ($amount * $fee / 100);
            } else {
                return floatval($feeConfig);
            }
        }

        return 0;
    }
}
