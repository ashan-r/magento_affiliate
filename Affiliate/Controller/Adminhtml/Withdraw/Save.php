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

namespace Mageplaza\Affiliate\Controller\Adminhtml\Withdraw;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\Affiliate\Controller\Adminhtml\Withdraw;
use Mageplaza\Affiliate\Model\Withdraw\Status;
use Mageplaza\Affiliate\Model\WithdrawFactory;

/**
 * Class Save
 * @package Mageplaza\Affiliate\Controller\Adminhtml\Withdraw
 */
class Save extends Withdraw
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param WithdrawFactory $withdrawFactory
     * @param Date $dateFilter
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        WithdrawFactory $withdrawFactory,
        Date $dateFilter
    )
    {
        $this->_dateFilter = $dateFilter;
        parent::__construct($context, $resultPageFactory, $coreRegistry, $withdrawFactory);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost('withdraw');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $data = $this->_filterData($data);
            $withdraw = $this->_initWithdraw();
            $withdraw->setData($data);

            $redirectBack = $this->getRequest()->getParam('id') ? 'edit' : 'create';

            if ($this->getRequest()->getParam('back')) {
                $withdraw->setStatus(Status::COMPLETE);
            }
            try {
                $withdraw->save();

                $this->messageManager->addSuccess(__('The Withdraw has been saved.'));
                $this->_getSession()->setAffiliateWithdrawData(false);
                $this->_getSession()->unsetData('withdraw_customer_id');

                $resultRedirect->setPath('affiliate/*/');

                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Withdraw.'));
            }
            $this->_getSession()->setAffiliateWithdrawData($data);

            $resultRedirect->setPath('affiliate/*/' . $redirectBack, ['_current' => true]);

            return $resultRedirect;
        }
        $resultRedirect->setPath('affiliate/*/');

        return $resultRedirect;
    }

    /**
     * filter values
     *
     * @param array $data
     *
     * @return array
     */
    protected function _filterData($data)
    {
        $inputFilter = new \Zend_Filter_Input(['requested_at' => $this->_dateFilter,], [], $data);
        $data = $inputFilter->getUnescaped();

        return $data;
    }
}
