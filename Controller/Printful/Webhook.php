<?php
namespace DIT\OrderWorkflow\Controller\Printful;
use Exception;

class Webhook extends \Magento\Framework\App\Action\Action {

    public function __construct(
	    \Magento\Backend\App\Action\Context $context,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
		\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
		\Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
		\Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
    ){
        parent::__construct($context);

		$this->_orderFactory = $orderFactory;
		$this->_invoiceService = $invoiceService;
		$this->_invoiceSender = $invoiceSender;
		$this->_shipmentFactory = $shipmentFactory;
		$this->_shipmentSender = $shipmentSender;
		$this->_trackFactory = $trackFactory;
		$this->_eventManager = $context->getEventManager();
    }


    /**
     * Get order details
     *
     * @param string $orderId order id
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder($orderId)
    {
        //$order \Magento\Sales\Model\Order
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
        if (!$order->getIncrementId()) {
            throw new Exception("Order '{$orderId}' does not exist.");
        }
        return $order;
    }


	private function _saveTransaction($order, $obj){
		// Save the invoice to the order
		$transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
			->addObject($obj)
			->addObject($order);

		return $transaction->save();
	}

	private function _packageShippedEvent($post){
		$order = $this->_getOrder($post['order']['external_id']);

		/**
		 * Create invoice if required
		 *
		 */
		if ($order->canInvoice()) {
			$invoice = $this->_invoiceService->prepareInvoice($order);

			// Make sure there is a qty on the invoice
			if (!$invoice->getTotalQty()) {
				throw new Exception('You can\'t create an invoice without products.');
			}

			$invoice->addComment('Issued by Printful webhook package_shipped event.');

			// Register as invoice item
			$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
			$invoice->register();

			$this->_saveTransaction($order, $invoice);

			// Magento\Sales\Model\Order\Email\Sender\InvoiceSender
			$this->_invoiceSender->send($invoice);

			$order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
				->setIsCustomerNotified(true)
				->save();
		}

		/**
		  * Create shipment if required
		  *
		  */
		if ($order->canShip()){
			$shipment = $this->_shipmentFactory->create($order);

			if (!$shipment->getTotalQty()) {
				throw new Exception('You can\'t create an shipment without products.');
			}

			$shipment->addComment('Issued by Printful webhook package_shipped event.');
			$shipment->register();
			$this->_saveTransaction($order, $shipment);
		}

		/**
		 * Add tracking number to the existing shipment
		 *
		 */
		foreach($order->getShipmentsCollection() as $shipment){
			$track = $this->_trackFactory()->create()->addData(
				array(
					'number' => $post['shipment']['tracking_number'],
					'carrier_code'=> $post['shipment']['carrier'],
					'title'=> $post['shipment']['service']
				)
			);

			$shipment->addTrack($track);
			$shipment->save();

			$this->_eventManager->dispatch('printful_webhook_package_shipped', [
				'order' => $order,
				'shipment' => $shipment,
				'response_data' => $post
			]);
		}
	}


	public function execute(){

		$post = json_decode(file_get_contents("php://input"), true);
		try {
			switch($post['type']){
				case 'package_shipped':
					//$this->_packageShippedEvent($post['data']);
					$this->_eventManager->dispatch('printful_webhook_package_shipped', [
						'order' => $order,
						'response_data' => $post['data']
					]);
					
					die('Received package_shipped request.');
				break;

				default:
					die('Invalid request.');
				break;

			}
		} catch(Exception $ex){
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 503);
		}
	}
}
