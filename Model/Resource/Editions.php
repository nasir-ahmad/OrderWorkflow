<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Model\Resource;

class Editions extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	//Required to use custom PRIMARY key
	protected $_isPkAutoIncrement = false;
	
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dit_orderworkflow_editions', 'order_item_id');
    }
}
