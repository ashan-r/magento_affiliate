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

namespace Mageplaza\Affiliate\Model\Payment\Methods;

use Mageplaza\Affiliate\Model\Payment\Methods;

/**
 * Class Offline
 * @package Mageplaza\Affiliate\Model\Payment\Methods
 */
class Offline extends Methods
{
    /**
     * @inheritdoc
     */
    public function getMethodDetail()
    {
        return [
            'offline_address' => [
                'type'  => 'textarea',
                'label' => __('Address'),
                'name'  => 'offline_address'
            ]
        ];
    }
}