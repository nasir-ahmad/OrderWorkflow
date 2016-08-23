<?php

namespace DIT\OrderWorkflow\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use DIT\OrderWorkflow\Model;

class SalesOrderInvoicePay implements ObserverInterface
{
	public function execute(\Magento\Framework\Event\Observer $observer)
	{		
		/** @var Http $request */
		//$request = $observer->getRequest();
		//$order_id = $request->getParam('order_id');
		
		$invoice = $observer->getInvoice();		
			
		$manager = \Magento\Framework\App\ObjectManager::getInstance();
		$exporter = $manager->create('DIT\OrderWorkflow\Model\Export');
		$exporter->invoke($invoice->getOrder());
		
		return $this;
	}
}