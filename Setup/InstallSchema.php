<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $table  = $installer->getConnection()
            ->addColumn(
                'order_item_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Order Item ID'
            )
            ->addColumn(
                'order_increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Order Increment ID'
            )
            ->addColumn(
                'title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Title'
            )
            ->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_BITINT,
                null,
                ['nullable' => false],
                'Product ID'
            )
            ->addColumn(
                'image',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Image'
            );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
