<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Model;

class Cron extends \Magento\Framework\Model\AbstractModel {
	const DEBUG_MODE = true;

	public function __construct(
		\DIT\OrderWorkflow\Model\Shipstation\Api $ssApi,
		\DIT\OrderWorkflow\Model\Shipstation\Order $ssOrder,
		\DIT\OrderWorkflow\Model\Printful\Api\Adapter $pfApi,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Psr\Log\LoggerInterface $logger
	){
		$this->_ssApi = $ssApi;
		$this->_pfApi = $pfApi;
		$this->_orderFactory = $orderFactory;
		$this->_ssOrder = $ssOrder;
		$this->_logger = $logger;
	}

	private function _log($msg){
		$this->_logger->debug($msg);

		if (self::DEBUG_MODE){
			echo "<pre>" . $msg . "</pre>";
		}

		return $this;
	}

    public function shipstationImportOrder()
    {
		$this->_log('================ Initializing Shipstation Import CRON Job ================');

		$this->_log('Fetching ShipStation orders...');
		$ssOrders = $this->_ssApi->listOrders(array('orderStatus' => 'awaiting_shipment'));

		//Find the list of ShipStation orders that are already available in Magento and ignore those
		$ssOrderIds = array();
		foreach($ssOrders['orders'] as $o){
			$ssOrderIds[] = $o['orderNumber']; //OrderNumber is the Magento increment_id
		}
		$this->_log('Fetched ShipStation Orders ' . implode(', ', $ssOrderIds));

		foreach($ssOrders['orders'] as $ssOrder){
			$this->_log('<<<<< Started import ShipStation order ' . $ssOrder['orderId']);

			//Check whether the order was imported in Magento already
			$existingOrder = $this->_orderFactory->create()->getCollection()
										->addFieldToFilter(array(
											'increment_id',
											'shipstation_order_id',
										), array(
											$ssOrder['orderNumber'],
											$ssOrder['orderId']
										));

			if ($existingOrder->getSize() > 0){
				foreach($existingOrder as $order){
					$this->_log('Already imported. Magento order ID #' . $order->getIncrementId() . '. SS order number ' . $ssOrder['orderNumber'] . ' status = ' . $ssOrder['orderStatus']);
				}
			} else {

				//Import from ShipStation and create the order in Magento if it doesn't exist already
				$this->_log('Importing ShipStation order... ' . $ssOrder['orderId']);
				$this->_log(json_encode($ssOrder));

				try {
					$order = $this->_ssOrder->create($ssOrder);
					if($order->getId()){
						$this->_log('Created Magento order #' . $order->getIncrementId());

						//Update Shipstation order and sync with Magento order number so that it doesn't get exported twice
						$ssOrder['orderNumber'] = $order->getIncrementId();

						//Set default store to Ampers and Sons if it's Manual Order on ShipStation
						$storeMapping = $this->_ssOrder->getStoreMapping();
						if (!in_array($ssOrder['advancedOptions']['storeId'], array_keys($storeMapping))){		//array_keys returns SS store ids and array_values returns Magento store ids
							foreach($storeMapping as $ssStoreId => $mageStoreId){
								$ssOrder['advancedOptions']['storeId'] = $ssStoreId;
								break;
							}
						}

						//$ssOrder['orderKey'] = $order->getIncrementId();	//Don't update "orderKey" as would create a new order in ShipStation

						$this->_log('Requesting ShipStation API to update orderNumber with Order Increment ID #' . $ssOrder['orderNumber']);
						$response = $this->_ssApi->createUpdateOrder($ssOrder);
						$this->_log('API Response: ' . json_encode($response));
						$this->_log('API Request complete.');

						//Make sure the order number was really changed on ShipStation to make sure both systems are in sync
						if ($response['orderNumber'] != $order->getIncrementId()){
							throw new \Exception('Sync Failed. Unable to update order number of ShipStation order ' . $ssOrder['orderId']);
						}

						//Create invoice
						if ($order->canInvoice()){
							$invoice = $this->_ssOrder->createInvoice($order, $ssOrder);
							$this->_log('Created Magento invoice #' . $invoice->getIncrementId());
						} else {
							throw new Exception('Unable to create invoice for Magento order ' . $order->getIncrementId());
						}
					} else {
						throw new \Exception('Unable to import ShipStation order... ' . $ssOrder['orderId']);
					}
				} catch (\Exception $ex){
					$this->_log('ERROR: ' . $ex->getMessage());
				}
			}
			$this->_log('>>>>> Ended ShipStation import of order ' . $ssOrder['orderId']);
		}

		$this->_log('================ CRON execution completed successfully ================');

        return $this;
    }

	public function printfulExportOrders(){

		$this->_log('================ Initializing Printful Export CRON Job ================');

		//Check whether the order was imported in Magento already
		$orderCollection = $this->_orderFactory->create()->getCollection()
									->addFieldToFilter('state', array('in' => array('complete', 'processing')))
									//->addFieldToFilter('increment_id', '000000054')
									->addFieldToFilter('printful_is_exported', array('neq' => 1));

		$orderCollection->getSelect()->limit(10);

		foreach($orderCollection as $order){
			$this->_log('<<<<< Sending export request for order #' . $order->getIncrementId());
			try {
				$response = $this->_pfApi->createOrder($order);
				$this->_log('Received response ' . json_encode($response));
			} catch (\Exception $ex){
				$this->_log('ERROR: ' . $ex->getMessage());
			}
			$this->_log('>>>>> Ended export of order #' . $order->getIncrementId());
		}

		$this->_log('================ CRON execution completed successfully ================');
	}
}