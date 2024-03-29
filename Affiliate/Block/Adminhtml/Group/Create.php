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

namespace Mageplaza\Affiliate\Block\Adminhtml\Group;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Class Create
 * @package Mageplaza\Affiliate\Block\Adminhtml\Group
 */
class Create extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Create constructor.
     *
     * @param Registry $coreRegistry
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        Context $context,
        array $data = []
    )
    {
        $this->_coreRegistry = $coreRegistry;

        parent::__construct($context, $data);
    }

    /**
     * Initialize Group edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mageplaza_Affiliate';
        $this->_controller = 'adminhtml_group';
        $this->_mode = 'create';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Group'));
        $this->buttonList->remove('delete');
        $this->buttonList->remove('reset');
    }

    /**
     * Retrieve text for header element depending on loaded Group
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('New Group');
    }
}
