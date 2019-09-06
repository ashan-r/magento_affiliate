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

namespace Mageplaza\Affiliate\Controller\Account;

use Mageplaza\Affiliate\Controller\Account;
use Mageplaza\Affiliate\Model\Account\Status;

/**
 * Class Signuppost
 * @package Mageplaza\Affiliate\Controller\Account
 */
class Signuppost extends Account
{
    /**
     * @return \Magento\Framework\View\Result\Page|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $account = $this->dataHelper->getCurrentAffiliate();
        if ($account && $account->getId()) {
            if (!$account->isActive()) {
                $this->messageManager->addNoticeMessage(__('Your account is not active. Please contact us.'));
            }
            $this->_redirect('*/*');

            return;
        }

        $data = $this->getRequest()->getPostValue();
        if ($this->dataHelper->isEnableTermsAndConditions() && !isset($data['terms'])) {
            $this->messageManager->addErrorMessage(__('You have to agree with term and conditions.'));
            $this->_redirect('*/*');

            return;
        }

        $customer = $this->customerSession->getCustomer();
        $data['customer_id'] = $customer->getId();
        $signUpConfig = $this->dataHelper->getAffiliateAccountSignUp();
        $data['group_id'] = $signUpConfig['default_group'];

        if (isset($data['referred_by'])) {
            $data['parent'] = $this->dataHelper->getAffiliateByEmailOrCode(trim($data['referred_by']));
            $data['parent_email'] = trim($data['referred_by']);
        }
        $data['status'] = $signUpConfig['admin_approved'] ? Status::NEED_APPROVED : Status::ACTIVE;
        $data['email_notification'] = $signUpConfig['default_email_notification'];

        try {
            $account->addData($data)->save();
            $messageSuccess = __('Congratulations! You have successfully registered.');
            if ($account->getStatus() == Status::NEED_APPROVED) {
                $messageSuccess = __('Congratulations! You have successfully registered. We will review your affiliate account and inform you once it\'s approved!');
            }

            $this->messageManager->addSuccessMessage($messageSuccess);
            $this->_redirect('*/*');

            return;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the Account.'));
        }

        $this->_redirect('*/*/signup');

        return;
    }
}
