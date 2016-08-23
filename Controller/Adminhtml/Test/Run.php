<?php
namespace DIT\OrderWorkflow\Controller\Adminhtml\Test;


class Run extends \Magento\Backend\App\Action {

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
		\DIT\OrderWorkflow\Model\Cron $cron
    ){
		$this->_cron = $cron;

        parent::__construct($context);
    }

	public function execute(){
		$this->_cron->run();	
	}
}
