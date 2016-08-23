<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Controller\Adminhtml\Items;

class NewAction extends \DIT\OrderWorkflow\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
