<?php

namespace DIT\OrderWorkflow\Model\Shipstation;

class Api {
	const API_KEY = 'c39e35671eec494a9034d34adde16ea8';
	const API_SECRET = '3499c0aa483940268954069f4703c235';
	const API_ENDPOINT = 'https://ssapi.shipstation.com';

	protected $_ch;

	public function __construct(
	){
		$this->_ch = curl_init();
		curl_setopt($this->_ch, CURLOPT_USERPWD, self::API_KEY . ':' . self::API_SECRET);
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json'));
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_ch, CURLOPT_HEADER, false);
	}

	public function __destruct(){
		curl_close($this->_ch);
	}

	protected function _getEndpoint($resource, $params){
		$params = count($params) ? '?' . http_build_query($params) : '';

		return self::API_ENDPOINT . '/' . $resource . $params;
	}

	public function post($resource = '', $params = array(), $data = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint($resource, $params));
		curl_setopt($this->_ch, CURLOPT_POST, true);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($data));
		
		return json_decode(curl_exec($this->_ch), true);
	}
	
	public function get($resource = '', $params = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint($resource, $params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
		
		return json_decode(curl_exec($this->_ch), true);
	}

	/* Get the list of orders from ShipStation */
	public function listOrders($params = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint('orders', $params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
		$response = json_decode(curl_exec($this->_ch), true);

		return $response;
	}

	public function listShipments($params = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint('shipments', $params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, true);
		$response = json_decode(curl_exec($this->_ch), true);

		return $response;
	}


	public function createUpdateOrder($data = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint('orders/createorder', $data));
		curl_setopt($this->_ch, CURLOPT_POST, true);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($data));
		$response = json_decode(curl_exec($this->_ch), true);

		return $response;
	}

	public function createUpdateOrders($data = array()){
		curl_setopt($this->_ch, CURLOPT_URL, $this->_getEndpoint('orders/createorders', $data));
		curl_setopt($this->_ch, CURLOPT_POST, true);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($data));
		$response = json_decode(curl_exec($this->_ch), true);

		return $response;
	}
}
?>