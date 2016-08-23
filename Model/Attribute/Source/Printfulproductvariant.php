<?php
namespace DIT\OrderWorkflow\Model\Attribute\Source;

class Printfulproductvariant extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource {


	public function __construct(
		\DIT\OrderWorkflow\Model\Printful\Api $pfApi
	){
		$this->_pfApi = $pfApi;
	}

    const VALUE_YES = 1;

    const VALUE_NO = 0;

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
			$cacheFile = BP . '/var/cache/printful/product_variants.json';
			@mkdir(dirname($cacheFile));

			//Save into local cache instead of calling API every time
			if (!file_exists($cacheFile)){
				$products = $this->_pfApi->get('products');
				$options = [];
				foreach($products['result'] as $product){
					$options[] = $this->_pfApi->get('products/' . $product['id'])['result'];
				}
				file_put_contents($cacheFile, json_encode($options));
			}

			// Load from local cache
			$this->_options[] = array('label' => __('-- Please Select --'), 'value' => 0);
			foreach(json_decode(file_get_contents($cacheFile), true) as $row){
				$product = $row['product'];
				$variants = $row['variants'];
				foreach($variants as $variant){
					$this->_options[] = array('label' => $product['type'] . ' - ' . $variant['name'] . ' - $' . $variant['price'], 'value' => $variant['id']);
				}
			}
        }

        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }
}
