<?php

namespace DIT\OrderWorkflow\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveBefore implements ObserverInterface {

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		//Automatically set Width and Height attributes based on Size attribute on every product save
		$product = $observer->getProduct();
		
		$size = explode('x', $product->getAttributeText('size'));
		if (count($size) == 2){
			$x = intval(trim($size[0]));
			$y = intval(trim($size[1]));
			
			$width = $x;
			$height = $y;
			switch(strtolower($product->getAttributeText('orientation'))){
				case 'portrait':
					$width = min($x, $y);
					$height = max($x, $y);
					break;
				
				case 'landscape':
					$width = max($x, $y);
					$height = min($x, $y);
					break;
			}
			
			$product->setWidth($width)->setHeight($height);
		}

		return $this;
	}
}