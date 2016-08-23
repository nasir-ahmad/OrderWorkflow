<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Model\Resource\Editions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('DIT\OrderWorkflow\Model\Editions', 'DIT\OrderWorkflow\Model\Resource\Editions');
    }
}
