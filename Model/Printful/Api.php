<?php
/**
 * This class helps to use the Printful API
 *
 * Requires PHP version 5, JSON and CURL extensions
 *
 * @package Printful
 * @version 1.0
 * @copyright 2014 Idea Bits LLC
 */
 
namespace DIT\OrderWorkflow\Model\Printful;

class Api {
	private $key = 'xf9dno90-7z69-fewc:sw19-lmmko4zrs9xf';
	private $lastResponseRaw;
	private $lastResponse;

	const API_URL = 'https://api.theprintful.com/';
	const USER_AGENT = 'Printful API PHP Library 1.0';

	/**
	 * @param string $key Printful Store API key
	 * @throws PrintfulException if the library failed to initialize
	 */
	public function __construct(){
		if(!function_exists('json_decode') || !function_exists('json_encode')){
			throw new PrintfulException('PHP JSON extension is required for the Printful API library to work!');
		}
		if(!function_exists('curl_init') ){
			throw new PrintfulException('PHP CURL extension is required for the Printful API library to work!');
		}
	}

	/**
	 * Returns total available item count from the last request if it supports paging (e.g order list) or null otherwise.
	 *
	 * @return int|null Item count
	 */
	public function getItemCount(){
		return isset($this->lastResponse['paging']['total']) ? $this->lastResponse['paging']['total'] : null;
	}

	/**
	 * Perform a GET request to the API
	 * @param string $path Request path (e.g. 'orders' or 'orders/123')
	 * @param array $params Additional GET parameters as an associative array
	 * @return mixed API response
	 * @throws PrintfulApiException if the API call status code is not in the 2xx range
	 * @throws PrintfulException if the API call has failed or the response is invalid
	 */
	public function get($path, $params = array()){
		return $this->request('GET', $path, $params);
	}

	/**
	 * Perform a DELETE request to the API
	 * @param string $path Request path (e.g. 'orders' or 'orders/123')
	 * @param array $params Additional GET parameters as an associative array
	 * @return mixed API response
	 * @throws PrintfulApiException if the API call status code is not in the 2xx range
	 * @throws PrintfulException if the API call has failed or the response is invalid
	 */
	public function delete($path, $params = array()){
		return $this->request('DELETE', $path, $params);
	}

	/**
	 * Perform a POST request to the API
	 * @param string $path Request path (e.g. 'orders' or 'orders/123')
	 * @param array $data Request body data as an associative array
	 * @param array $params Additional GET parameters as an associative array
	 * @return mixed API response
	 * @throws PrintfulApiException if the API call status code is not in the 2xx range
	 * @throws PrintfulException if the API call has failed or the response is invalid
	 */
	public function post($path, $data = array(), $params = array()){
		return $this->request('POST', $path, $params, $data);
	}
	/**
	 * Perform a PUT request to the API
	 * @param string $path Request path (e.g. 'orders' or 'orders/123')
	 * @param array $data Request body data as an associative array
	 * @param array $params Additional GET parameters as an associative array
	 * @return mixed API response
	 * @throws PrintfulApiException if the API call status code is not in the 2xx range
	 * @throws PrintfulException if the API call has failed or the response is invalid
	 */
	public function put($path, $data = array(), $params = array()){
		return $this->request('PUT', $path, $params, $data);
	}

	/**
	 * Return raw response data from the last request
	 * @return string|null Response data
	 */
	public function getLastResponseRaw(){
		return $this->lastResponseRaw;
	}
	/**
	 * Return decoded response data from the last request
	 * @return array|null Response data
	 */
	public function getLastResponse(){
		return $this->lastResponse;
	}

	/**
	 * Internal request implementation
	 */
	private function request($method, $path, array $params = array(), $data = null){

		$this->lastResponseRaw = null;
		$this->lastResponse = null;

		$url = trim($path,'/');

		if(!empty($params)){
			$url .= '?'.http_build_query($params);
		}

		$curl = curl_init(self::API_URL.$url);

		curl_setopt($curl, CURLOPT_USERPWD, $this->key);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 3);

		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);

		curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);

		if($data !==null){
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		$this->lastResponseRaw = curl_exec($curl);

		$errno = curl_errno($curl);
		$error = curl_error($curl);
		curl_close($curl);

		if ($errno) throw new PrintfulException('CURL: '.$error, $errno);

		$this->lastResponse = $response = json_decode($this->lastResponseRaw, true);

		if(!isset($response['code'], $response['result'])){
			throw new PrintfulException('Invalid API response');
		}
		$status = (int)$response['code'];
		if($status < 200 || $status >= 300){
			throw new PrintfulApiException((string)$response['result'], $status);
		}
		return $response['result'];
	}
}

/**
 * Class PrintfulException Generic Printful exception
 */
class PrintfulException extends \Exception {}
/**
 * Class PrintfulException Printful exception returned from the API
 */
class PrintfulApiException extends PrintfulException {}