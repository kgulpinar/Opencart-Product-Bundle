<?php
class ModelExtensionTotalProductBundlesTotal extends Model {
 
    private $languagePath = 'extension/total/productbundlestotal';

	public function getTotal($totals) {
        $this->load->model('catalog/product');

        $this->config->load('isenselabs/productbundles');
        $call_model = $this->config->get('productbundles_model');
        $module_path = $this->config->get('productbundles_path');
        $this->load->model($module_path);
        
		$total                    = &$totals['total'];
		$taxes                    = &$totals['taxes'];
		$total_data               = &$totals['totals'];
		
		$cartProducts             = $this->cart->getProducts();
		$cartProductsFlat         = array();
		$cartProductsQuantities   = array();
		$taxClasses               = array();
		$matchingBundles          = array();
        $bundle_names             = array();
        
		foreach ($cartProducts as $product) {
			$cartProductsFlat[] = $product['product_id'];
			if (empty($cartProductsQuantities[$product['product_id']])) {
				$cartProductsQuantities[$product['product_id']] = $product['quantity'];
			} else {
				$cartProductsQuantities[$product['product_id']] += $product['quantity'];
			}
			
			$taxClasses[$product['product_id']] = $product['tax_class_id'];
		}
		
        $bundles = $this->$call_model->getBundles();
		usort($bundles, array($this, 'cmp'));
		
		$setting = $this->config->get('productbundles');
		
		if (!empty($setting['Enabled']) && ($setting['Enabled'] == 'yes') && !empty($bundles)) {
            
            $discountsApply = (isset($setting['MultipleBundles']) && ($setting['MultipleBundles']=='yes')) ? true : false;
            
            foreach ($bundles as $bundle) {
				if (array_diff($bundle['products'], $cartProductsFlat) === array()) {
					$bundleQuantities = array();
					
                    foreach($bundle['products'] as $product_id) {
						if (empty($bundleQuantities[$product_id])) {
							$bundleQuantities[$product_id] = 1;
						} else {
							$bundleQuantities[$product_id]++;
						}
					}
					
					for(;;) {
						foreach($bundleQuantities as $product_id=>$quantity) {
							if (!isset($cartProductsQuantities[$product_id]) || ($quantity > $cartProductsQuantities[$product_id])) {
								continue 3;
							}
						}
						
						foreach($bundleQuantities as $product_id=>$quantity) {
							$cartProductsQuantities[$product_id] -= $quantity;
						}
						
						if (!array_key_exists($bundle['id'], $matchingBundles)) {
							$matchingBundles[$bundle['id']] = array();
							$matchingBundles[$bundle['id']][] = $bundle;
						} else if ($discountsApply) {
							$matchingBundles[$bundle['id']][] = $bundle;
						}
					}
				}
			}
			
			if (!empty($matchingBundles)) {
				$this->language->load($this->languagePath);
				
				$grandTotal = 0;
				foreach ($matchingBundles as $bundle) {
					$taxClassesUnique = array();
					foreach ($bundle as $bndl) {
						foreach ($bndl['products'] as $product) {
							$taxClassesUnique[] = $taxClasses[$product];
						}
					}	
					$taxClassesUnique = array_unique($taxClassesUnique);
                    
					foreach($bundle as $instance) {

                        $total_price = 0;
                        $total_price_no_taxes = 0;
                        foreach ($instance['products'] as $product_id) {	
                            $product_info = $this->model_catalog_product->getProduct($product_id);
                            if ((float)$product_info['special']) {
                                $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                                $total_price_no_taxes += $product_info['special'];						
                            } else {
                                $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                                $total_price_no_taxes += $product_info['price'];
                            }
                        }
                        
                        if (!empty($setting['DiscountTaxation']) && $setting['DiscountTaxation']=='no') {
                            if ($instance['discount_type'] == '1') {
                                $discount_value = $instance['discount_value'];
                            } else if ($instance['discount_type'] == '2') {
                                $percentage = $instance['discount_value']/100;
                                $discount_value = $percentage*$total_price;
                            }
                        } 
                        
						if (isset($setting['DiscountTaxation']) && $setting['DiscountTaxation']=='yes') {
                            if ($instance['discount_type'] == '1') {
                                $discount_value = $instance['discount_value'];
                            } else if ($instance['discount_type'] == '2') {
                                $percentage = $instance['discount_value']/100;
                                $discount_value = $percentage*$total_price_no_taxes;
                            }
                            
							foreach ($taxClassesUnique as $taxClassId) {
								$tax_rates = $this->tax->getRates((float)$discount_value, $taxClassId);
								foreach ($tax_rates as $tax_rate) {
									if ($tax_rate['type'] == 'P') {
										$taxes[$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
									}
								}
							}
						}
                        
                        if (!empty($instance['name'][$this->config->get('config_language_id')])) {
                            $bundle_names[] = $instance['name'][$this->config->get('config_language_id')];
                        }
						$grandTotal += (float)$discount_value;
					}
				}
				
				$total_data[] = array(
					'code'       => 'productbundlestotal',
					'title'      => $this->language->get('entry_title') . (!empty($bundle_names) ? ' - ' . implode($bundle_names, ', ') : ''),
					'text'       => $this->currency->format(-$grandTotal, $this->config->get('config_currency')),
					'value'      => -$grandTotal,
					'sort_order' => $this->config->get('productbundlestotal_sort_order')
				);
		
				$total -= (float)$grandTotal;
				if ($total < 0) {
					$total = 0;
				}
			}
            
		}
		
	}
	
	private function cmp($a, $b) {
		if ($a == $b) {
             return 0;
		}
		
		return (count($a['products']) > count($b['products'])) ? -1 : 1;
	}	
}