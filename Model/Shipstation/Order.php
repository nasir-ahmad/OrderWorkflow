<?php
namespace DIT\OrderWorkflow\Model\Shipstation;

class Order {

	private $_storeMapping = array(
		//SS StoreID => Magento StoreID
		178379 => 1,
		178381 => 2,
	);

    public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\DIT\OrderWorkflow\Model\Shipstation\Api $api,
		\Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Catalog\Model\Product $product,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
		\Magento\Sales\Model\Service\InvoiceService $invoiceService,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
		\Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
		\Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender
    ) {
		$this->_orderFactory = $orderFactory;
        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->_quote = $quote;
        $this->_quoteManagement = $quoteManagement;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_orderService = $orderService;
		$this->_storeFactory = $storeFactory;
		$this->_invoiceService = $invoiceService;
		$this->_invoiceSender = $invoiceSender;
		$this->_shipmentFactory = $shipmentFactory;
		$this->_shipmentSender = $shipmentSender;
		$this->_objectManager = $objectManager;
    }

	protected function _getStore($ssOrder){
		$ssStoreId = $ssOrder['advancedOptions']['storeId'];
		$storeId = isset($this->_storeMapping[$ssStoreId]) ? $this->_storeMapping[$ssStoreId] : null;

		if (!$storeId){
			return $this->_storeManager->getStore();
		}

		return $this->_storeFactory->create()->load($storeId);
	}

	protected function _getCustomer($ssOrder){
		//$store = $this->_storeManager->getStore();
		//$websiteId = $this->_storeManager->getStore()->getWebsiteId();

		$store = $this->_getStore($ssOrder);
		$websiteId = $store->getWebsiteId();

        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($ssOrder['customerEmail']);// load customet by email address

        if(!$customer->getEntityId()){
            //If not avilable then create this customer
			$name = explode(' ' , $ssOrder['billTo']['name']);
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setLastname(array_pop($name))
                    ->setFirstname(trim(count($name) ? implode(' ', $name) : '__'))
                    ->setEmail($ssOrder['customerEmail'])
                    ->setPassword($ssOrder['customerEmail'] . '12345');

            $customer->save();
        }

		return $customer;
	}

	protected function _addOrderItem($quote, $ssItem){
		$productId = $this->_product->getIdBySku($ssItem['sku']);
		$product = $this->_product->load($productId);
		$product->setPrice((float) $ssItem['unitPrice']);
		$product->setName($ssItem['name']);

		$quote->addProduct(
			$product,
			(float) $ssItem['quantity']
		);

		return $product;
	}


	protected function _addAddress($quote, $ssAddress, $type){

		if ($type == 'billing'){
			$address = $quote->getBillingAddress();
		} else {
			$address = $quote->getShippingAddress();
		}
		$name = explode(' ', (string) $ssAddress['name']);
		$street = array();
		for($i = 1; $i <= 3; $i++){
			if ($val = $ssAddress['street'.$i]){
				$street[] = $val;
			}
		}

		$data = array(
            'lastname' => array_pop($name),
            'firstname' => trim(count($name) ? implode(' ', $name) : '__'),
			'city' => $ssAddress['city'],
			'street' => implode(', ', $street),
            'country_id' => $ssAddress['country'],
            'region' => $ssAddress['state'],
            'postcode' => $ssAddress['postalCode'],
            'telephone' => $ssAddress['phone'],
            'fax' => null,
            'save_in_address_book' => 1
		);

		/*
		foreach($data as $k => $v){

			if ($k == 'country_id'){
				$data[$k] = 'US';
			} elseif (!trim($v)){
				$data[$k] = '-';
			}
		}
		*/

		$address->addData($data);

		return $address;
	}

	public function create($ssOrder){
        //$store = $this->_storeManager->getStore();
		$store = $this->_getStore($ssOrder);

		//Prepare customer data
		$customer = $this->_getCustomer($ssOrder);

		//Create quote
        $quote=$this->_quote->create();
        $quote->setStore($store);
        $customer = $this->_customerRepository->getById($customer->getEntityId());
        //$quote->setCurrency('USD');
        $quote->assignCustomer($customer); //Assign quote to customer

        //add items in quote
        foreach($ssOrder['items'] as $item){
			$this->_addOrderItem($quote, $item);
        }

        //Set Address to quote
        $billing = $this->_addAddress($quote, $ssOrder['billTo'], 'billing');
		$shipping = $this->_addAddress($quote, $ssOrder['shipTo'], 'shipping');


		//Fix missing mandatory fields when importing orders from ShipStation
		$needToCopyAddress = false;
		$copyAddress = array();
		foreach(array('firstname', 'lastname', 'street', 'city', 'region', 'postcode', 'country_id', 'telephone') as $k){
			$copyAddress[$k] = $shipping->getData($k);
			if (!$billing->getData($k)){
				$needToCopyAddress = true;
			}
		}

		if ($needToCopyAddress){
			$billing->addData($copyAddress);
		}


        // Collect Rates and Set Shipping & Payment Method

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('flatrate_flatrate')
						->setPaymentMethod('checkmo');

        $quote->setPaymentMethod('checkmo');
        //$quote->setInventoryProcessed(false); //affect inventory
        $quote->save(); //Now Save quote and your quote is ready

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'checkmo']);

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $order = $this->_quoteManagement->submit($quote);
        $order->setEmailSent(0);

		//Update shipstation info in the order
		$order->setData('from_shipstation', 1);
		$order->setData('shipstation_order_id', $ssOrder['orderId']);
		$order->save();

		return $order;
	}

	public function getStoreMapping(){
		return $this->_storeMapping;
	}


	public function createInvoice($order, $ssOrder){
		$invoice = $this->_invoiceService->prepareInvoice($order);

		// Make sure there is a qty on the invoice
		if (!$invoice->getTotalQty()) {
			throw new \Magento\Framework\Exception\LocalizedException(
						__('You can\'t create an invoice without products.')
					);
		}

		$invoice->addComment('Issued by OrderWordflow');
		
		if ($notes = $ssOrder['internalNotes']){
			$order->addComment($notes);
		}
		
		// Register as invoice item
		$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
		$invoice->register();

		// Save the invoice to the order
		$transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
			->addObject($invoice)
			->addObject($invoice->getOrder());

		$transaction->save();

		// Magento\Sales\Model\Order\Email\Sender\InvoiceSender
		$this->_invoiceSender->send($invoice);

		$order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
			->setIsCustomerNotified(true)
			->save();

		return $invoice;
	}

	/**
	 * Create shipment
	 *
	 * @param \Magento\Sales\Model\Order\Invoice $invoice
	 * @param array $tracks
	 * @return \Magento\Sales\Model\Order\Shipment|false
	 */


	public function createShipment($order, $ssOrder, $ssShipment){

		foreach($order->getInvoiceCollection() as $invoice){
			if ($order->canShip()){

				$tracks[] = [
					'number' => $ssShipment['trackingNumber'],
					'carrier_code'=> $ssShipment['carrierCode'],
					'title'=> $ssOrder['requestedShippingService'] . ' - ' . strtoupper($ssShipment['serviceCode'])
				];

				$shipment = $this->_shipmentFactory->create(
					$order,
					$invoice->getAllItems(),
					$tracks
				);

				if ($notes = $ssOrder['internalNotes']){
					$shipment->addComment($notes);
				}

				if (!$shipment->getTotalQty()) {
					throw new \Magento\Framework\Exception\LocalizedException(
								__('You can\'t create an shipment without products.')
							);
				}

				$shipment->register();

				$transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
													->addObject($shipment)
													->addObject($order)
													->save();

				$this->_shipmentSender->send($shipment);

				$order->addStatusHistoryComment(__('Notified customer about shipment #%1.', $shipment->getId()))
					->setIsCustomerNotified(true)
					->save();

				return $shipment;

			} else {
				throw new Exception('Unable to create shipment for Magento order ' . $order->getIncrementId());
			}
		}

		return false;
	}


}
