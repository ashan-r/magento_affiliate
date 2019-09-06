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
 * @copyright   Copyright (c) 2018 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Affiliate\Cron;

use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\TransactionFactory;

/**
 * Class CompleteHoldingCommission
 * @package Mageplaza\Affiliate\Cron
 */
class CompleteHoldingCommission
{
    /**
     * @var \Mageplaza\Affiliate\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Mageplaza\Affiliate\Model\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * MpCancelTranexpirydate constructor.
     *
     * @param Data $dataHelper
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Data $dataHelper,
        TransactionFactory $transactionFactory
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_transactionFactory = $transactionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $holdingDay = (int)$this->_dataHelper->getCommissionHoldingDays();
        if (!$this->_dataHelper->isEnabled() || !$holdingDay) {
            return;
        }

        $transactionModel = $this->_transactionFactory->create()->getCollection()
            ->addFieldToFilter('status', 2)
            ->addHoldingDaysToFilter($holdingDay);

        foreach ($transactionModel as $transaction) {
            $transaction->complete();
        }
    }
}