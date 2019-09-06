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

namespace Mageplaza\Affiliate\Block\Adminhtml\Transaction\View\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Registry;
use Magento\User\Model\UserFactory;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\Transaction\Status;
use Mageplaza\Affiliate\Model\Transaction\Type;

/**
 * Class Transaction
 * @package Mageplaza\Affiliate\Block\Adminhtml\Transaction\View\Tab
 */
class Transaction extends Generic implements TabInterface
{
    /**
     * @var \Mageplaza\Affiliate\Model\Transaction\Type
     */
    protected $_transactionType;

    /**
     * @var \Mageplaza\Affiliate\Model\Transaction\Status
     */
    protected $_transactionStatus;

    /**
     * @type \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @type \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @type \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @type \Magento\User\Model\UserFactory
     */
    protected $user;

    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $helperData;

    /**
     * Transaction constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Mageplaza\Affiliate\Model\Transaction\Type $type
     * @param \Mageplaza\Affiliate\Model\Transaction\Status $status
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param \Mageplaza\Affiliate\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        CustomerFactory $customerFactory,
        PricingHelper $pricingHelper,
        Type $type,
        Status $status,
        JsonHelper $jsonHelper,
        UserFactory $userFactory,
        Data $helperData,
        array $data = []
    )
    {
        $this->jsonHelper = $jsonHelper;
        $this->user = $userFactory;
        $this->pricingHelper = $pricingHelper;
        $this->customerFactory = $customerFactory;
        $this->_transactionType = $type;
        $this->_transactionStatus = $status;
        $this->helperData = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        /** @var \Mageplaza\Affiliate\Model\Transaction $transaction */
        $transaction = $this->_coreRegistry->registry('current_transaction');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('transaction_');
        $form->setFieldNameSuffix('transaction');
        $fieldset = $form->addFieldset('base_fieldset', [
                                                          'legend' => __('Transaction Information'),
                                                          'class'  => 'fieldset-wide'
                                                      ]
        );

        if ($transaction->getId()) {
            $this->editTransaction($fieldset, $transaction);
        } else {
            $this->addTransaction($fieldset);
        }

        $form->addValues($transaction->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param $fieldset
     * @param $transaction
     */
    public function editTransaction($fieldset, $transaction)
    {
        $customerModel = $this->customerFactory->create()->load($transaction->getCustomerId());
        $fieldset->addField('affiliate_account', 'link', [
                                                   'href'   => $this->getUrl('affiliate/account/edit', ['id' => $transaction->getAccountId()]),
                                                   'name'   => 'affiliate_account',
                                                   'value'  => $customerModel->getName() . ' <' . $this->escapeHtml($customerModel->getEmail()) . '>',
                                                   'label'  => __('Affiliate Account'),
                                                   'target' => '_blank',
                                                   'style'  => 'text-decoration: none',
                                                   'class'  => 'control-value'
                                               ]
        );

        $transactionType = $this->_transactionType->getOptionHash();
        $fieldset->addField('type', 'note', [
                                      'label' => __('Type'),
                                      'text'  => $transactionType[$transaction->getType()]
                                  ]
        );
        $fieldset->addField('title', 'note', [
                                       'label' => __('Title'),
                                       'text'  => $transaction->getTitle()
                                   ]
        );
        $fieldset->addField('amount', 'note', [
                                        'label' => __('Amount'),
                                        'text'  => $this->pricingHelper->currency($transaction->getAmount())
                                    ]
        );
        $transactionStatus = $this->_transactionStatus->getOptionHash();
        $fieldset->addField('status', 'note', [
                                        'label' => __('Status'),
                                        'text'  => $transactionStatus[$transaction->getStatus()]
                                    ]
        );

        if ($transaction->getOrderId()) {
            $fieldset->addField('order', 'link', [
                                           'href'   => $this->getUrl('sales/order/view', ['order_id' => $transaction->getOrderId()]),
                                           'name'   => 'order',
                                           'value'  => '#' . $transaction->getOrderIncrementId(),
                                           'label'  => __('Order'),
                                           'target' => '_blank',
                                           'style'  => 'text-decoration: none',
                                           'class'  => 'control-value'
                                       ]
            );
        }

        if ($transaction->getExtraContent() && ($transaction->getType() == Type::ADMIN)) {
            $extraContent = $this->jsonHelper->jsonDecode($transaction->getExtraContent());
            if (is_array($extraContent) && isset($extraContent['admin_id'])) {
                $admin = $this->user->create()->load($extraContent['admin_id']);
                $href = $this->getUrl('adminhtml/user/edit', ['user_id' => $admin->getId()]);
                $fieldset->addField('admin_account', 'link', [
                                                       'label'  => __('Created by'),
                                                       'href'   => $href,
                                                       'value'  => $admin->getName() . ' <' . $this->escapeHtml($admin->getEmail()) . '>',
                                                       'target' => '_blank',
                                                       'style'  => 'text-decoration: none',
                                                       'class'  => 'control-value'
                                                   ]
                );
            }
        }

        if ($transaction->getStatus() == Status::STATUS_HOLD && $transaction->getHoldingTo()) {
            $fieldset->addField('holding_to', 'note', [
                'label' => __('This transaction will be holed to'),
                'text'  => $this->_localeDate->formatDate($transaction->getHoldingTo(), \IntlDateFormatter::MEDIUM, true)
            ]);
        }

        $fieldset->addField('created_at', 'note', [
                                            'label' => __('Created Time'),
                                            'text'  => $this->_localeDate->formatDate($transaction->getCreatedAt(), \IntlDateFormatter::MEDIUM, true)
                                        ]
        );
    }

    /**
     * @param $fieldset
     */
    public function addTransaction($fieldset)
    {
        $fieldset->addField('customer_id', 'hidden', [
                                             'name' => 'customer_id'
                                         ]
        );

        $this->helperData->addCustomerEmailFieldset($fieldset, 'transaction', $this->getAjaxUrl(), 'create_transaction');

        $fieldset->addField('amount', 'text', [
                                        'label'    => __('Amount'),
                                        'name'     => 'amount',
                                        'required' => true,
                                        'class'    => 'validate-number',
                                        'note'     => __('Add or subtract affiliate\'s balance. E.g: 99 or -99')
                                    ]
        );
        $fieldset->addField('title', 'text', [
                                       'label' => __('Title'),
                                       'name'  => 'title'
                                   ]
        );
        $fieldset->addField('hold_day', 'text', [
                                          'name'  => 'hold_day',
                                          'label' => __('Holding Transaction For'),
                                          'note'  => 'day(s)'
                                      ]
        );
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Transaction');
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
     * Get transaction grid url
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('affiliate/customer/grid');
    }
}
