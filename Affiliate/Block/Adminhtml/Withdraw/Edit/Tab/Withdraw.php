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

namespace Mageplaza\Affiliate\Block\Adminhtml\Withdraw\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Registry;
use Mageplaza\Affiliate\Helper\Data as HelperData;
use Mageplaza\Affiliate\Helper\Payment;
use Mageplaza\Affiliate\Model\AccountFactory;
use Mageplaza\Affiliate\Model\Withdraw\Method;
use Mageplaza\Affiliate\Model\Withdraw\Status;

/**
 * Class Withdraw
 * @package Mageplaza\Affiliate\Block\Adminhtml\Withdraw\Create\Tab
 */
class Withdraw extends Generic implements TabInterface
{
    /**
     * @type AccountFactory
     */
    protected $accountFactory;

    /**
     * @type CustomerFactory
     */
    protected $customerFactory;

    /**
     * @type Data
     */
    protected $pricingHelper;

    /**
     * @var Method
     */
    protected $_method;

    /**
     * @var Payment
     */
    protected $_paymentHelper;

    /**
     * @var Status
     */
    protected $_status;

    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $helperData;

    /**
     * Withdraw constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Mageplaza\Affiliate\Model\AccountFactory $accountFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Mageplaza\Affiliate\Model\Withdraw\Method $method
     * @param \Mageplaza\Affiliate\Model\Withdraw\Status $status
     * @param \Mageplaza\Affiliate\Helper\Payment $payment
     * @param \Mageplaza\Affiliate\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        AccountFactory $accountFactory,
        CustomerFactory $customerFactory,
        Data $pricingHelper,
        Method $method,
        Status $status,
        Payment $payment,
        HelperData $helperData,
        array $data = []
    )
    {
        $this->pricingHelper = $pricingHelper;
        $this->customerFactory = $customerFactory;
        $this->accountFactory = $accountFactory;
        $this->_method = $method;
        $this->_paymentHelper = $payment;
        $this->_status = $status;
        $this->helperData = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        /** @var \Mageplaza\Affiliate\Model\Withdraw $withdraw */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('withdraw_');
        $form->setFieldNameSuffix('withdraw');
        $withdraw = $this->_coreRegistry->registry('current_withdraw');
        if ($withdraw->getId()) {
            $this->viewWithdraw($form);
        } else {
            $this->createWithdraw($form);
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return mixed
     */
    public function prepareWithdrawData()
    {
        $withdraw = $this->_coreRegistry->registry('current_withdraw');
        $customer = $this->customerFactory->create()->load($withdraw->getCustomerId());
        $withdraw->setCustomerName($customer->getName() . ' <' . $customer->getEmail() . '>');

        return $withdraw->getData();
    }

    /**
     * @param $form
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Serializer_Exception
     */
    public function createWithdraw($form)
    {
        $fieldset = $form->addFieldset('base_fieldset', [
                                                          'legend' => __('Withdraw Information'),
                                                          'class'  => 'fieldset-wide'
                                                      ]
        );

        $fieldset->addField('customer_id', 'hidden', [
                                             'name' => 'customer_id'
                                         ]
        );

        $this->helperData->addCustomerEmailFieldset($fieldset, 'withdraw', $this->getAjaxUrl(), 'create_withdraw');

        $fieldset->addField('amount', 'text', [
                                        'label'    => __('Amount'),
                                        'name'     => 'amount',
                                        'required' => true,
                                        'class'    => 'validate-number',
                                        'note'     => __('Include fee.')
                                    ]
        );
        $fieldset->addField('fee', 'text', [
                                     'label' => __('Fee'),
                                     'name'  => 'fee',
                                     'class' => 'validate-number',
                                     'note'  => __('If empty, configuration value will be used.')
                                 ]
        );

        $paymentField = $fieldset->addField('payment_method', 'select', [
            'name'     => 'payment_method',
            'label'    => __('Payment Method'),
            'required' => true,
            'values'   => $this->_method->toOptionArray()
        ]);

        $fieldset->addField('description', 'textarea', [
                                             'label' => __('Withdraw Reason'),
                                             'name'  => 'description',
                                         ]
        );

        $infoFieldset = $form->addFieldset('info_fieldset', [
                                                              'legend' => __('Payment Detail'),
                                                              'class'  => 'fieldset-wide'
                                                          ]
        );

        $dependence = $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence')
            ->addFieldMap($paymentField->getHtmlId(), $paymentField->getName());

        foreach ($this->_paymentHelper->getActiveMethods() as $method => $config) {
            $fields = $this->_paymentHelper->getMethodModel($method)->getMethodDetail();
            foreach ($fields as $key => $field) {
                $detailField = $infoFieldset->addField($key, $field['type'], $field);
                $dependence->addFieldMap($detailField->getHtmlId(), $detailField->getName())
                    ->addFieldDependence($detailField->getName(), $paymentField->getName(), $method);
            }
        }

        $this->setChild('form_after', $dependence);
    }

    /**
     * @param $form
     */
    public function viewWithdraw($form)
    {
        $fieldset = $form->addFieldset('base_fieldset', [
                                                          'legend' => __('If transaction is pending, you can complete by massaction on grid.'),
                                                          'class'  => 'fieldset-wide'
                                                      ]
        );

        $transactionId = $this->getRequest()->getParam('id');
        if ($transactionId) {
            $fieldset->addField('edit_record', 'hidden', [
                                                 'name'  => 'edit_record',
                                                 'value' => true
                                             ]
            );
        }
        $fieldset->addField('customer_id', 'hidden', [
                                             'name' => 'customer_id'
                                         ]
        );
        $fieldset->addField('account_id', 'hidden', [
                                            'name' => 'customer_id'
                                        ]
        );
        $fieldset->addField('customer_name', 'text', [
                                               'label'    => __('Account'),
                                               'name'     => 'customer_name',
                                               'readonly' => true
                                           ]
        );
        $fieldset->addField('amount', 'text', [
                                        'label'    => __('Amount'),
                                        'name'     => 'amount',
                                        'readonly' => true,
                                        'class'    => 'validate-number',
                                        'note'     => __('Include fee.')
                                    ]
        );
        $fieldset->addField('fee', 'text', [
                                     'label'    => __('Fee'),
                                     'name'     => 'fee',
                                     'readonly' => true,
                                     'class'    => 'validate-number',
                                     'note'     => __('If empty, configuration value will be used.')
                                 ]
        );
        $fieldset->addField('description', 'textarea', [
                                             'label'    => __('Withdraw Reason'),
                                             'readonly' => true,
                                             'name'     => 'description',
                                         ]
        );
        $fieldset->addField('transfer_amount', 'text', [
                                                 'label'    => __('Transfer Amount'),
                                                 'name'     => 'transfer_amount',
                                                 'readonly' => true,
                                                 'class'    => 'validate-number',
                                                 'note'     => __('Real amount will be transfer to affiliate.')
                                             ]
        );

        $paymentDetailField = $this->getPaymentDetailField();
        $fieldset->addField($this->getPaymentDetailField(), $this->getPaymentDetailInput(), [
                                                              'label'    => __($this->getPaymentDetailLabel()),
                                                              'readonly' => true,
                                                              'name'     => $paymentDetailField,
                                                          ]
        );

        if ($paymentDetailField == 'paypal_email') {
            $fieldset->addField('paypal_transaction_id', 'text', [
                                                           'label'    => __('Paypal transaction id'),
                                                           'name'     => 'paypal_transaction_id',
                                                           'readonly' => true
                                                       ]
            );
        }

        $fieldset->addField('status', 'select', [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'readonly' => true,
            'disabled' => true,
            'values'   => $this->_status->toOptionArray()
        ]);

        $form->addValues($this->prepareWithdrawData());
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Withdraw');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function getCurrentWithdraw()
    {
        return $this->_coreRegistry->registry('current_withdraw');
    }

    /**
     * get Payment Detail
     *
     * @return boolean
     */
    public function getPaymentDetailField()
    {
        $withdraw = $this->getCurrentWithdraw();
        if (isset($withdraw['banktranfer'])) {
            $payment = 'banktranfer';
        } else if (isset($withdraw['offline_address'])) {
            $payment = 'offline_address';
        } else {
            $payment = 'paypal_email';
        }

        return $payment;
    }

    /**
     * get Payment Detail Label
     *
     * @return boolean
     */
    public function getPaymentDetailLabel()
    {
        $withdraw = $this->getCurrentWithdraw();
        if (isset($withdraw['banktranfer'])) {
            $paymentLabel = 'Bank Tranfer Infomation';
        } else if (isset($withdraw['offline_address'])) {
            $paymentLabel = 'Offline Address';
        } else {
            $paymentLabel = 'Paypal Email';
        }

        return $paymentLabel;
    }

    /**
     * get Payment Detail Input Type
     *
     * @return boolean
     */
    public function getPaymentDetailInput()
    {
        $paymentInput = 'textarea';
        $withdraw = $this->getCurrentWithdraw();
        if (isset($withdraw['paypal_email'])) {
            $paymentInput = 'text';
        }

        return $paymentInput;
    }

    /**
     * Get transaction grid url
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('affiliate/customer/grid');
    }
}
