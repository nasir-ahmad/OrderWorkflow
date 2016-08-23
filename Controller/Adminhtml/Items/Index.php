<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Controller\Adminhtml\Items;

class Index extends \DIT\OrderWorkflow\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('DIT_OrderWorkflow::orderworkflow');
        $resultPage->getConfig()->getTitle()->prepend(__('DIT Items'));
        $resultPage->addBreadcrumb(__('DIT'), __('DIT'));
        $resultPage->addBreadcrumb(__('Items'), __('Items'));
        return $resultPage;
    }
}
