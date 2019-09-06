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

namespace Mageplaza\Affiliate\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Affiliate\Model\Campaign;
use Mageplaza\Affiliate\Model\CampaignFactory;

/**
 * Class InlineEdit
 * @package Mageplaza\Affiliate\Controller\Adminhtml\Campaign
 */
class InlineEdit extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonFactory;

    /**
     * @var \Mageplaza\Affiliate\Model\CampaignFactory
     */
    protected $_campaignFactory;

    /**
     * InlineEdit constructor.
     *
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Mageplaza\Affiliate\Model\CampaignFactory $campaignFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        CampaignFactory $campaignFactory,
        Context $context
    )
    {
        $this->_jsonFactory = $jsonFactory;
        $this->_campaignFactory = $campaignFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->_jsonFactory->create();
        $error = false;
        $messages = [];
        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                                            'messages' => [__('Please correct the data sent.')],
                                            'error'    => true,
                                        ]);
        }
        foreach (array_keys($postItems) as $campaignId) {
            /** @var \Mageplaza\Affiliate\Model\Campaign $campaign */
            $campaign = $this->_campaignFactory->create()->load($campaignId);
            try {
                $campaignData = $postItems[$campaignId];//todo: handle dates
                $campaign->addData($campaignData);
                $campaign->save();
            } catch (LocalizedException $e) {
                $messages[] = $this->getErrorWithCampaignId($campaign, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithCampaignId($campaign, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithCampaignId(
                    $campaign,
                    __('Something went wrong while saving the Campaign.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
                                        'messages' => $messages,
                                        'error'    => $error
                                    ]);
    }

    /**
     * @param \Mageplaza\Affiliate\Model\Campaign $campaign
     * @param $errorText
     *
     * @return string
     */
    protected function getErrorWithCampaignId(Campaign $campaign, $errorText)
    {
        return '[Campaign ID: ' . $campaign->getId() . '] ' . $errorText;
    }
}
