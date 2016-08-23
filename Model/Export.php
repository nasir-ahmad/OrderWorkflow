<?php
/**
 * Copyright © 2015 DIT. All rights reserved.
 */

namespace DIT\OrderWorkflow\Model;

use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Filesystem\DirectoryList;
use DIT\OrderWorkflow\Model\EditionsFactory;

class Export extends \Magento\Framework\Model\AbstractModel
{

	protected $_orderFactory;
	protected $_productFactory;
	protected $_dirList;
	protected $_editionsFactory;

	public function __construct(
		OrderFactory $orderFactory,
		ProductFactory $productFactory,
		DirectoryList $dirList,
		EditionsFactory $editionsFactory
	){
		$this->_orderFactory = $orderFactory;
		$this->_productFactory = $productFactory;
		$this->_dirList = $dirList;
		$this->_editionsFactory = $editionsFactory;
	}

	protected function _getFileName($product){
		$orientation = (strtolower($product->getAttributeText('orientation')) == 'landscape') ? 'H' : 'V';
		$width = $product->getAttributeText('width');
		$height = $product->getAttributeText('height');

		return $width . 'x' . $height . '_' . $orientation . '.csv';
	}

	protected function _updateEdition($product, $qty){
		//Generate inventory qty as item_qty/total_stock_qty format (for an ex. 8/100)
		//It's used only for limitededitions.com
		$edition = array();

		for ($i=1; $i <= $qty; $i++){
			$edition[] = ($product->getData('edition_number') + $i) . '/' . $product->getData('stock_qty');
		}

		$product->setEditionNumber($product->getEditionNumber() + $qty);
		$product->getResource()->saveAttribute($product, 'edition_number');

		return $edition;
	}


	/**
	  * Generates CSV for each order item
	  * Create record in DB
	  */
	public function invoke($order){
		//if (!$orderId){
		//	$orderId = 7;
		//}

		//$order = $this->_orderFactory->create()->load($order->getId());

		if ($order->getId()){	//Only for LimitedEditions.com website
			foreach($order->getAllItems() as $item){
				//Create batch files based on products orientation, width, height
				$product = $this->_productFactory->create()->load($item->getProductId());
				$fileName = $this->_getFileName($product);
				$filePath = $this->_dirList->getPath('var') . '/export/workflow/orders/' . $fileName;

				//Create container directory if required
				@mkdir(dirname($filePath));

				try {
					$editionModel = $this->_editionsFactory->create()->load($item->getId());

					if (!$editionModel->getId()){

						//Prepare the data
						$titles = array();
						$csvRows = array();
						$edition = $this->_updateEdition($product, $item->getQtyInvoiced());

						foreach($edition as $e){
							$titles[] = $product->getData('art_title') . ' ' . $e;
							$csvRows[] =  array(
								$order->getIncrementId(),
								$product->getId(),
								$product->getData('art_image'),
								$product->getData('art_title') . ' ' . $e,
							);
						}

						//Export as CSV
						$fp = fopen($filePath, 'a');
						if (!filesize($filePath)){
							//Write header
							fputcsv($fp, array(
								'order_id',
								'product_id',
								'art_image',
								'art_title',
							));
						}
						foreach($csvRows as $row){
							fputcsv($fp, $row);
						}
						fclose($fp);

						//Save in DB
						$editionModel->setId($item->getId())
								->setTitle(serialize($titles))
								->setImage($product->getData('art_image'))
								->setOrderIncrementId($order->getIncrementId())
								->setProductId($product->getId())
								->setEdition(serialize($edition))
								->setWidth($product->getAttributeText('width'))
								->setHeight($product->getAttributeText('height'))
								->setOrientation($product->getAttributeText('orientation'))
								->save();
					}
				} catch (exception $ex){

				}
			}
		}
	}
}
