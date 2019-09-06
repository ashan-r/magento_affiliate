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

use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Affiliate\Controller\Account;

/**
 * Class Withdrawpost
 * @package Mageplaza\Affiliate\Controller\Account
 */
class Withdrawpost extends Account
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $account = $this->dataHelper->getCurrentAffiliate();
        if (!$account || !$account->getId() || !$account->isActive()) {
            $this->messageManager->addNoticeMessage(__('An error occur. Please contact us.'));

            $this->_redirect('*/*');

            return;
        }
        $customer = $this->customerSession->getCustomer();

        $data = $this->getRequest()->getPostValue();
        $data['customer_id'] = $customer->getId();
        $data['account_id'] = $account->getId();

        $this->customerSession->setWithdrawFormData($data);
        $withdraw = $this->withdrawFactory->create();
        $withdraw->addData($data)->setAccount($account);

        try {
            $this->checkWithdrawAmount($withdraw);
            $withdraw->save();
            $this->messageManager->addSuccessMessage(__('Your request has been sent successfully. We will review your request and inform you once it\'s approved!'));
            $this->customerSession->setWithdrawFormData(false);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\RuntimeException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the request.'));
        }

        $this->_redirect('*/*/withdraw');

        return;
    }

    /**
     * @param $withdraw
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkWithdrawAmount($withdraw)
    {
        $minBalance = $this->dataHelper->getWithdrawMinimumBalance();
        if ($minBalance && $withdraw->getAccount()->getBalance() < $minBalance) {
            throw new LocalizedException(__('Your balance is not enough for request withdraw.'));
        }

        $min = $this->dataHelper->getWithdrawMinimum();
        if ($min && $withdraw->getAmount() < $min) {
            throw new LocalizedException(__('The withdraw amount have to equal or greater than %1', $this->dataHelper->formatPrice($min)));
        }

        $max = $this->dataHelper->getWithdrawMaximum();
        if ($max && $withdraw->getAmount() > $max) {
            throw new LocalizedException(__('The withdraw amount have to equal or less than %1', $this->dataHelper->formatPrice($max)));
        }

        return $this;
    }
}
