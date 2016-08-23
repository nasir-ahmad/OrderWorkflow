<?php
namespace DIT\OrderWorkflow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use DIT\OrderWorkflow\Model\EditionsFactory;

class Data extends AbstractHelper{
	
	protected $_editionsFactory;
	
	public function __construct(EditionsFactory $editionsFactory){
		$this->_editionsFactory = $editionsFactory;
	}
	
	
	/**
	 *
	 * @Nasir: Implement equivalent inventory text in ordered_qty/total_stock)qty format
	 *
	 */
	public function getEditionTitle($orderItem){
		$model = $this->_editionsFactory->create()->load($orderItem->getId());
		$editions = unserialize($model->getTitle());
		
		if ($editions && is_array($editions)){
			return $editions;
		}		
		
		return array();
	}

}