<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */
namespace DIT\OrderWorkflow\Block\Adminhtml\Items\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('dit_orderworkflow_items_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Item'));
    }
}
