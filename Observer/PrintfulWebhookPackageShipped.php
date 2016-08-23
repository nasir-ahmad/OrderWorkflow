<?php

namespace DIT\OrderWorkflow\Observer;

use Magento\Framework\Event\ObserverInterface;

class PrintfulWebhookPackageShipped implements ObserverInterface {


	public function __construct(
		\DIT\OrderWorkflow\Model\Shipstation\Api $ssApi
	){
		$this->_ssApi = $ssApi;
	}

	public function execute(\Magento\Framework\Event\Observer $observer)
	{

		$order = $observer->getOrder();
		$shipment = $observer->getShipment();
		$data = $observer->getResponseData();

		//Send order information to Shipstation via API
		if ($orderId = $order->getData('shipstation_order_id'){
			$this->_ssApi->post('orders/markasshipped', array(), array(
				'orderId' => $orderId,
				'trackingNumber' => $post['shipment']['tracking_number'],
				'carrierCode'=> $post['shipment']['carrier'],
				'title'=> $post['shipment']['service'],
				'notifyCustomer'=> true,
				'notifySalesChannel'=> true
			));
		}

		return $this;
	}
}