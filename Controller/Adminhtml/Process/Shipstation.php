<?php
namespace DIT\OrderWorkflow\Controller\Adminhtml\Process;


class Shipstation extends \Magento\Backend\App\Action {
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\DIT\OrderWorkflow\Model\Cron $cron
    ){
		$this->_cron = $cron;

        parent::__construct($context);
    }

	public function execute(){
		$this->_cron->shipstationImportOrder();

		printf('<p><a href="%s">&lt; Go Back</a></p>', $_SERVER['HTTP_REFERER']);
	}
}
