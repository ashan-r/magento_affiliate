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

namespace Mageplaza\Affiliate\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @package Mageplaza\Affiliate\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mageplaza_affiliate_account'),
                'parent_email',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => '255',
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'Email of parent affiliate'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('mageplaza_affiliate_withdraw'),
                'description',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => '255',
                    'nullable' => true,
                    'default'  => '',
                    'comment'  => 'Description transaction of withdraw history'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mageplaza_affiliate_campaign'),
                'apply_discount_on_tax',
                [
                    'type'     => Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'default'  => '0',
                    'comment'  => 'Apply discount on tax'
                ]
            );

            /**
             * Add more column to table  'quote'
             */
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_key', 'text NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'base_affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_commission', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_shipping_commission', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_campaigns', 'text NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_discount_shipping_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'affiliate_base_discount_shipping_amount', 'decimal(12,4) NULL');

            /**
             * Add more column to table  'quote_item'
             */
            $installer->getConnection()->addColumn($installer->getTable('quote_item'), 'affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote_item'), 'base_affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('quote_item'), 'affiliate_commission', 'text(255) NULL');

            /**
             * Add more column to table  'sales_order'
             */
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_shipping_commission', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_earn_commission_invoice_after', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_discount_shipping_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_base_discount_shipping_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_discount_invoiced', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'base_affiliate_discount_invoiced', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_commission_invoiced', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_discount_shipping_invoiced', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_discount_refunded', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'base_affiliate_discount_refunded', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_commission_refunded', 'text(255) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_discount_shipping_refunded', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'affiliate_commission_holding_refunded', 'text(255) NULL');

            /**
             * Add more column to table  'sales_order_item'
             */
            $installer->getConnection()->addColumn($installer->getTable('sales_order_item'), 'affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order_item'), 'base_affiliate_discount_amount', 'decimal(12,4) NULL');
            $installer->getConnection()->addColumn($installer->getTable('sales_order_item'), 'affiliate_commission', 'text(255) NULL');
        }

        $installer->endSetup();
    }
}