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

namespace Mageplaza\Affiliate\Block\Adminhtml\Account\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Registry;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\Account\Group;
use Mageplaza\Affiliate\Model\Account\Status;
use Mageplaza\Affiliate\Model\AccountFactory;

/**
 * Class Account
 * @package Mageplaza\Affiliate\Block\Adminhtml\Account\Edit\Tab
 */
class Account extends Generic implements TabInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_boolean;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Mageplaza\Affiliate\Model\Account\Status
     */
    protected $_status;

    /**
     * @var \Mageplaza\Affiliate\Model\Account\Group
     */
    protected $_group;

    /**
     * @var \Mageplaza\Affiliate\Model\AccountFactory
     */
    protected $_accountFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_pricingHelper;

    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $helperData;

    /**
     * Account constructor.
     *
     * @param \Magento\Config\Model\Config\Source\Yesno $boolean
     * @param \Mageplaza\Affiliate\Model\Account\Status $status
     * @param \Mageplaza\Affiliate\Model\Account\Group $group
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Mageplaza\Affiliate\Model\AccountFactory $accountFactory
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Mageplaza\Affiliate\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        Yesno $boolean,
        Status $status,
        Group $group,
        Context $context,
        Registry $registry,
        CustomerFactory $customerFactory,
        AccountFactory $accountFactory,
        PricingHelper $pricingHelper,
        FormFactory $formFactory,
        Data $helperData,
        array $data = []
    )
    {
        $this->_accountFactory = $accountFactory;
        $this->_customerFactory = $customerFactory;
        $this->_pricingHelper = $pricingHelper;
        $this->_boolean = $boolean;
        $this->_status = $status;
        $this->_group = $group;
        $this->helperData = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        /** @var \Mageplaza\Affiliate\Model\Account $account */
        $account = $this->_coreRegistry->registry('current_account');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('account_');
        $form->setFieldNameSuffix('account');

        if ($account->getId()) {
            $this->editAccount($form, $account);
        } else {
            $this->createNewAccount($form);
        }

        $form->addValues($account->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param $form
     * @param $account
     */
    public function editAccount($form, $account)
    {
        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Account Information'),
            'class'  => 'fieldset-wide'
        ]);

        $fieldset->addField('customer_id', 'hidden', ['name' => 'customer_id']);
        $customer = $this->_customerFactory->create()->load($account->getCustomerId());
        $fieldset->addField('customer_name', 'link', [
            'href'   => $this->getUrl('customer/index/edit', ['id' => $customer->getId()]),
            'name'   => 'customer_name',
            'label'  => __('Customer'),
            'title'  => __('Customer'),
            'value'  => $customer->getName() . ' <' . $this->escapeHtml($customer->getEmail()) . '>',
            'target' => '_blank',
            'class'  => 'control-value',
            'style'  => 'text-decoration: none'
        ]);

        $fieldset->addField('group_id', 'select', [
            'name'   => 'group_id',
            'label'  => __('Affiliate Group'),
            'title'  => __('Affiliate Group'),
            'values' => $this->_group->toOptionArray()
        ]);

        $fieldset->addField('balance', 'note', [
            'label' => __('Balance'),
            'text'  => $this->_pricingHelper->currency($account->getBalance())
        ]);

        $fieldset->addField('holding_balance', 'note', [
            'label' => __('Holding Balance'),
            'text'  => $this->_pricingHelper->currency($account->getHoldingBalance())
        ]);

        if ($account->getParent()) {
            $fieldset->addField('parent', 'hidden', ['name' => 'parent']);
            $parentAccount = $this->_accountFactory->create()->load($account->getParent());
            $parentCustomer = $this->_customerFactory->create()->load($parentAccount->getCustomerId());
            $fieldset->addField('parent_account', 'link', [
                'href'   => $this->getUrl('affiliate/account/edit', ['id' => $account->getParent()]),
                'name'   => 'parent_account',
                'label'  => __('Referred By'),
                'title'  => __('Referred By'),
                'value'  => $parentCustomer->getName() . ' <' . $this->escapeHtml($parentCustomer->getEmail()) . '>',
                'target' => '_blank',
                'class'  => 'control-value',
                'style'  => 'text-decoration: none'
            ]);
        }

        $fieldset->addField('code', 'note', [
            'label' => __('Referral Code'),
            'text'  => $account->getCode(),
        ]);

        $fieldset->addField('status', 'select', [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => true,
            'values'   => $this->_status->toOptionArray(),
        ]);

        $fieldset->addField('email_notification', 'select', [
            'name'   => 'email_notification',
            'label'  => __('Email Notification'),
            'title'  => __('Email Notification'),
            'values' => $this->_boolean->toOptionArray(),
        ]);
    }

    /**
     * @param $form
     */
    public function createNewAccount($form)
    {
        $fieldset = $form->addFieldset('base_fieldset1', [
            'legend' => __('Account Information'),
            'class'  => 'fieldset-wide'
        ]);

        $fieldset->addField('customer_id', 'hidden', [
            'name' => 'customer_id'
        ]);

        $this->helperData->addCustomerEmailFieldset($fieldset, 'account', $this->getAjaxUrl(), 'create_account');

        $fieldset->addField('group_id', 'select', [
            'name'     => 'group',
            'label'    => __('Affiliate Group'),
            'title'    => __('Affiliate Group'),
            'required' => true,
            'values'   => $this->_group->toOptionArray()
        ]);

        $fieldset->addField('parent', 'text', [
            'name'  => 'parent',
            'label' => __('Referred By'),
            'title' => __('Referred By'),
            'class' => 'validate-number',
            'note'  => __('Affiliate account Id')
        ]);
        $fieldset->addField('status', 'select', [
            'name'     => 'status',
            'label'    => __('Status'),
            'title'    => __('Status'),
            'required' => true,
            'values'   => $this->_status->toOptionArray()
        ]);
        $fieldset->addField('email_notification', 'select', [
            'name'   => 'email_notification',
            'label'  => __('Email Notification'),
            'title'  => __('Email Notification'),
            'values' => $this->_boolean->toOptionArray(),
        ]);
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Account');
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
