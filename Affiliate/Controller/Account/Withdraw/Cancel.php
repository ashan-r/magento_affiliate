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

namespace Mageplaza\Affiliate\Controller\Account\Withdraw;

use Mageplaza\Affiliate\Controller\Account;

/**
 * Class Cancel
 * @package Mageplaza\Affiliate\Controller\Account\Withdraw
 */
class Cancel extends Account
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            $this->withdrawFactory->create()->load($id)->cancel();
            $this->messageManager->addSuccessMessage(__('The withdraw has been canceled successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the request.'));
        }

        $this->_redirect('affiliate/account/withdraw');

        return;
    }
}
