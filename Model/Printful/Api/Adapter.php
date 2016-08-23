<?php
namespace DIT\OrderWorkflow\Model\Printful\Api;

class Adapter {
    public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\DIT\OrderWorkflow\Model\Printful\Api $pfApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product
    ) {
        $this->_storeManager = $storeManager;
        $this->_product = $product;
		$this->_objectManager = $objectManager;
		$this->_pfApi = $pfApi;
    }

	public function createOrder($order){
		/****** Request sample *******/
		/*
		array(
			'recipient' => array(
				'name' => 'John Doe',
				'address1' => '172 W Providencia Ave #105',
				'city' => 'Burbank',
				'state_code' => 'CA',
				'country_code' => 'US',
				'zip' => '91502'
			),
			'items' => array(
				array(
					'variant_id' => 1118,
					'quantity' => 2,
					'name' => 'Grand Canyon T-Shirt', //Display name
					'retail_price' => '29.99', //Retail price for packing slip
					'files' => array(
						array(//Front print
							'url' => 'http://example.com/files/tshirts/shirt_front.ai'
						),
						array(//Back print
							'type' => 'back',
							'url' => 'http://example.com/files/tshirts/shirt_back.ai'
						),
						array(//Mockup image
							'type' => 'preview',
							'url' => 'http://example.com/files/tshirts/shirt_mockup.jpg'
						)
					),
					'options' => array(//Additional options
						array(
							'id' => 'remove_labels',
							'value' => true
						)
					)
				)
			)
		)
		*/
			
		if ($order->getData('printful_is_exported')){
			throw new \Exception('Order ' . $order->getIncrementId() . ' was already exported.');
		}

		//Construct the order data
		$address = $order->getShippingAddress() ? $order->getShippingAddress() : $order->getBillingAddress();
		$orderData = array(
			'external_id' => $order->getIncrementId(),
			'recipient' => array(
				'name' => $address->getName(),
				'address1' => implode(',', $address->getStreet()),
				'city' => $address->getCity(),
				'state_code' => $address->getRegion(),
				'country_code' => $address->getCountryId(),
				'zip' => $address->getPostCode()
			),
			'items' => array()
		);

		//Validate Recipient
		foreach($orderData['recipient'] as $k => $v){
			if (!$v){
				throw new \Exception('Invalid RECIPIENT data.');
			}
		}

		foreach($order->getAllItems() as $item){
			if ($item->getParentItem()){
				continue;
			}

			$productId = $this->_product->getIdBySku($item->getSku());
			$product = $this->_product->load($productId);

			$itemData =  array(
				'variant_id' => $product->getData('printful_variant_id'),
				'quantity' => (int) $item->getQtyInvoiced(),
				'name' => $product->getName(),
				'retail_price' => $item->getPriceInclTax(),
				'files' => array(),
			);

			if ($image = $product->getData('art_image')){
				$filePath = '/master_images_ynp0xTJpNW72BxZTDxOuGju1jiqlKGlWIwC76L1h/' . $image;
				
				if (file_exists(BP . $filePath)){
					$itemData['files'][] = array(
						'url' => $this->_storeManager->getStore()->getBaseUrl() . $filePath
					);
				} else {
					throw new \Exception('Print image file ' . $image . ' was not found (Product ID: ' . $productId . ')');
				}
			}

			$orderData['items'][] = $itemData;

			//Validate order items
			foreach($itemData as $k => $v){
				if (empty($v)){
					throw new \Exception('Invalid ITEMS data: ' . $k . ' found (Product ID: ' . $productId . ')');
				}
			}
		}

		//Send to Printful
		$this->_pfApi->post('orders', $orderData, array('update_existing' => true));

		//Flag order as exported
		return $order->setData('printful_is_exported', 1)->save();
	}
}
