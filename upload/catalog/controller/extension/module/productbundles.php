<?php  
class ControllerExtensionModuleProductbundles extends Controller {
	private $moduleName;
    private $callModel;
    private $modulePath;
    private $moduleModel;
    private $currency_code;
    private $data = array();
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Config Loader
        $this->config->load('isenselabs/productbundles');
        
        /* Fill Main Variables - Begin */
        $this->moduleName       = $this->config->get('productbundles_name');
        $this->callModel        = $this->config->get('productbundles_model');
        $this->modulePath       = $this->config->get('productbundles_path');
        /* Fill Main Variables - End */

        // Load Model
        $this->load->model($this->modulePath);

        // Model Instance
        $this->moduleModel      = $this->{$this->callModel};
        
        $this->event->unregister("*/before", $this->modulePath . "/customUrlFunctionality");

        /* Module-specific declarations - Begin */
        $this->load->language($this->modulePath);

        $language_strings = $this->language->load($this->modulePath);
        foreach ($language_strings as $code => $languageVariable) {
			$this->data[$code] = $languageVariable;
		}

        // Multi-Store
        $this->load->model('setting/store');
        // Product
        $this->load->model('catalog/product');
        // Settings
        $this->load->model('setting/setting');
        // Images
        $this->load->model('tool/image');

        // Variables
        $this->data['moduleName'] 		= $this->moduleName;
        $this->data['modulePath']       = $this->modulePath;
        $this->data['cart_url']         = $this->url->link('checkout/cart');
        $this->data['language_id']      = $this->config->get('config_language_id');
        $this->data['CloseButton']      = true;
        /* Module-specific declarations - End */
        
        if (!empty($this->session->data['currency'])) {
            $this->currency_code = $this->session->data['currency'];
        } else {
            $this->currency_code = $this->config->get('config_currency');
        }
        
    }
    	
	public function index($setting) {
        
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');	
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['moduleData']         = $this->config->get($this->moduleName);
        $picture_width                    = isset($this->data['moduleData']['WidgetWidth']) ? $this->data['moduleData']['WidgetWidth'] : '100';
		$picture_height                   = isset($this->data['moduleData']['WidgetHeight']) ? $this->data['moduleData']['WidgetHeight'] : '100';		

        $bundles                          = array();
		
        $filter = array();
        
        if (isset($this->request->get['product_id'])) {
            $p_id = $this->request->get['product_id'];
            
            $filter['product_id'] = $p_id;
        }
        
        if ((isset($this->request->get['path'])) && (!isset($this->request->get['product_id']))) {
            $category = (explode("_", $this->request->get['path']));
			if (isset($category[1])) {
				$cat_id = end($category);
            } else {
				$cat_id = $this->request->get['path'];
            }
            
            $filter['category_id'] = $cat_id;
        }
        
        if (isset($this->data['moduleData']['WidgetLimit'])) {
            $filter['limit'] = $this->data['moduleData']['WidgetLimit'];
        }
        
        if (isset($this->data['moduleData']['DisplayType'])) {
            $filter['order'] = $this->data['moduleData']['DisplayType'];   
            
            if (!isset($filter['product_id']) && !isset($filter['category_id']) && $this->data['moduleData']['ShowRandomBundles'] == 'yes') {
                $filter['order'] = 'random';
            }
        }
        
        $filter['page'] = 1;
        
        $bundles = $this->moduleModel->getBundles($filter);
        
        $this->data['bundles'] = array();
        
        if ($bundles) {
            
            foreach ($bundles as $index => $bundle) {
                $total_price = 0;
				$total_price_no_taxes = 0;
                
                $this->data['bundles'][$bundle['id']] = array();
                $this->data['bundles'][$bundle['id']]['id'] = $bundle['id'];
                $this->data['bundles'][$bundle['id']]['products'] = array();
                $this->data['bundles'][$bundle['id']]['product_options'] = 'false';
                
                foreach ($bundle['products'] as $product_id) {	
                    $product_info = $this->model_catalog_product->getProduct($product_id);
    
                    if (!isset($this->data['bundles'][$bundle['id']]['products'][$product_id])) {

                        if ($product_info['image']) {
                            $image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
                        } else {
                            $image = false;
                        }

                        if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                            $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                        } else {
                            $price = false;
                        }

                        if ((float)$product_info['special']) {
                            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                            
                            $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                            $total_price_no_taxes += $product_info['special'];						
                        } else {
                            $special = false;

                            $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                            $total_price_no_taxes += $product_info['price'];
                        }

                        $product_options = $this->model_catalog_product->getProductOptions($product_id);
                        if (!empty($product_options)) {
                            $this->data['bundles'][$bundle['id']]['product_options'] = 'true';
                        }
                        
                        $this->data['bundles'][$bundle['id']]['products'][$product_id] = array(
                            'product_id' => $product_id,
                            'quantity'	 => 1,
                            'thumb'   	 => $image,
                            'name'    	 => $product_info['name'],
                            'price'   	 => $price,
                            'special' 	 => $special,
                            'href'    	 => $this->url->link('product/product', 'product_id=' . $product_id)
                        );
                        
                    } else {
                        if ((float)$product_info['special']) {
                            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                            
                            $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                            $total_price_no_taxes += $product_info['special'];						
                        } else {
                            $special = false;

                            $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                            $total_price_no_taxes += $product_info['price'];
                        }
                        
                        $this->data['bundles'][$bundle['id']]['products'][$product_id]['quantity'] += 1;
                    }
                } // end foreach ($bundle['products'] as $product_id)
                
                if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation'] == 'no') {
                    if ($bundle['discount_type'] == '1') {
                        $discount_value = $bundle['discount_value'];
                    } else if ($bundle['discount_type'] == '2') {
                        $percentage = $bundle['discount_value']/100;
                        $discount_value = $percentage*$total_price;
                    }
                } else {
                    if ($bundle['discount_type'] == '1') {
                        $discount_value = $bundle['discount_value'];
                    } else if ($bundle['discount_type'] == '2') {
                        $percentage = $bundle['discount_value']/100;
                        $discount_value = $percentage*$total_price_no_taxes;
                    }
                }
                
                if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
					foreach ($bundle['products'] as $result) {
						$product_info = $this->model_catalog_product->getProduct($result);
						if ((float)$product_info['special']) {
							$ratio = $total_price_no_taxes / $product_info['special'];
						} else {
							$ratio = $total_price_no_taxes / $product_info['price'];
						}
						
						$tax_rates = $this->tax->getRates((float)$discount_value / $ratio, $product_info['tax_class_id']);
						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 'P') {
								$total_price -= $tax_rate['amount'];
							}
						}
					}
				}
					
                $final_price = $total_price - $discount_value;
                $this->data['bundles'][$bundle['id']]['discount_value'] = $discount_value;
				$this->data['bundles'][$bundle['id']]['total_price'] = $this->currency->format($total_price, $this->currency_code);
				$this->data['bundles'][$bundle['id']]['discount_value'] = $this->currency->format($discount_value, $this->currency_code);
				$this->data['bundles'][$bundle['id']]['final_price'] = $this->currency->format($final_price, $this->currency_code);
                
                if  (!empty($bundle['name'][$this->config->get('config_language_id')])) {
					$this->data['bundles'][$bundle['id']]['name'] = $bundle['name'][$this->config->get('config_language_id')];
				} else {
					$this->data['bundles'][$bundle['id']]['name'] = $this->language->get('view_bundle');
				}
                
                if (!empty($bundle['description'][$this->config->get('config_language_id')])) {
					$this->data['bundles'][$bundle['id']]['description'] = $bundle['description'][$this->config->get('config_language_id')];
				} else {
					$this->data['bundles'][$bundle['id']]['description'] = $this->language->get('view_bundle');
				}
                
                $this->data['bundles'][$bundle['id']]['description'] = utf8_substr(strip_tags(html_entity_decode($this->data['bundles'][$bundle['id']]['description'], ENT_QUOTES, 'UTF-8')), 0, 150) . '..';
                
                $this->data['bundles'][$bundle['id']]['url'] = $this->url->link($this->modulePath . '/view', 'bundle_id=' . $bundle['id'], 'SSL');

                
            } // end foreach ($bundles as $index => $bundle)
            
        }
        
        $this->data['listing_url'] = $this->url->link($this->modulePath . '/listing', '', 'SSL');
        
        $this->document->addStyle('catalog/view/theme/default/stylesheet/'.$this->moduleName.'/'.$this->moduleName.'.css');
        return $this->load->view($this->modulePath.'/'.$this->moduleName, $this->data);
	}
    
    public function add_bundle_to_cart() {
		$json = array();

		if (isset($this->request->get['bundle_id'])) {
			$bundle_id   = $this->request->get['bundle_id'];
            $bundle      = $this->moduleModel->getBundle($bundle_id);
            $bundle      = is_array($bundle) ? current($bundle) : array();

            if ($bundle) {
                foreach ($bundle['products'] as $product_id) {
                    $this->cart->add($product_id, 1);
                }

                $json['success'] = true;
			} else {
				$json['error'] = true;
			}
		} else {
			$json['error'] = true;	
		}
		
		echo json_encode($json);
        exit;
	}
    
	public function add_bundle_to_cart_options() {
        $json = array();

        if (isset($this->request->post) && $this->request->post['products']) {
		
            $products = explode("_", $this->request->post['products']); // Explode products
            
            if (isset($this->request->post['option'])) {  // Product Options
                $option = $this->request->post['option'];
            } else {
                $option = array();	
            }
        
            foreach ($products as $key=>$p) { // Check for empty but required product options
                $product_options = $this->model_catalog_product->getProductOptions($p);
                    foreach ($product_options as $product_option) {
                        if ($product_option['required'] && empty($option[$key][$product_option['product_option_id']])) {
                            if (empty($json['error']['option'][$product_option['product_option_id']])) {
                                $json['error']['option'][$product_option['product_option_id']] = array();
                            }
                            $json['error']['option'][$product_option['product_option_id']][] = array(
                                'message' => sprintf($this->language->get('error_required'), $product_option['name']),
                                'key' 	 => $key 
                            );
                    }
                }
            }
            
            if (!$json) {
                foreach ($products as $key=>$p) {
                    $p_option = $p_option = !empty($option[$key]) ? $option[$key] : array();
                    $this->cart->add($p, 1, $p_option, "");
                }
                $json['success'] = true;
            }

		} else {
			//echo "ERROR 2!";	
		}
		
		echo json_encode($json);
        exit;
	}
    
    public function show_bundle_options() {		
		$this->data['moduleData']               = $this->config->get('productbundles');
		$this->data['cart_url']                 = $this->url->link('checkout/cart');

		if (isset($this->request->get['bundle_id'])) {
			$bundle_id                          = $this->request->get['bundle_id'];
			$this->data['bundle_products']      = "";
            $picture_width						= isset($this->data['moduleData']['WidgetWidth']) ? $this->data['moduleData']['WidgetWidth'] : '128';
			$picture_height						= isset($this->data['moduleData']['WidgetHeight']) ? $this->data['moduleData']['WidgetHeight'] : '128';
            
            $bundle = $this->moduleModel->getBundle($bundle_id);
            $bundle = is_array($bundle) ? current($bundle) : array();
            
            if ($bundle) {

                foreach ($bundle['products'] as $index => $result) {
                    
                    if ($index != 0) { $this->data['bundle_products'] .= "_"; }
				    $this->data['bundle_products'] .= $result;
                    
                    $product_info = $this->model_catalog_product->getProduct($result);

                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
                    } else {
                        $image = false;
                    }

                    if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                    } else {
                        $price = false;
                    }

                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                    } else {
                        $special = false;
                    }

                    if ($this->config->get('config_review_status')) {
                        $rating = (int)$product_info['rating'];
                    } else {
                        $rating = false;
                    }

                    $product_options = $this->model_catalog_product->getProductOptions($product_info['product_id']);
                    $this->data['options'] = array();

                    foreach ($this->model_catalog_product->getProductOptions($product_info['product_id']) as $option) { 
                        if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') { 
                            $option_value_data = array();
                            foreach ($option['product_option_value'] as $option_value) {
                                if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
                                    if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
                                        $option_price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                                    } else {
                                        $option_price = 0;
                                    }

                                    $option_value_data[] = array(
                                        'product_option_value_id' => $option_value['product_option_value_id'],
                                        'option_value_id'         => $option_value['option_value_id'],
                                        'name'                    => $option_value['name'],
                                        'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
                                        'price'                   => $option_price,
                                        'price_prefix'            => $option_value['price_prefix']
                                    );
                                }
                            }

                            $this->data['options'][] = array(
                                'product_option_id' => $option['product_option_id'],
                                'option_id'         => $option['option_id'],
                                'name'              => $option['name'],
                                'type'              => $option['type'],
                                'option_value'      => $option_value_data,
                                'required'          => $option['required']
                            );					
                        } elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
                            $this->data['options'][] = array(
                                'product_option_id' => $option['product_option_id'],
                                'option_id'         => $option['option_id'],
                                'name'              => $option['name'],
                                'type'              => $option['type'],
                                'option_value'      => $option['value'],
                                'required'          => $option['required']
                            );						
                        }
                    }

                    $this->data['products'][] = array(
                        'product_id' => $product_info['product_id'],
                        'thumb'   	 => $image,
                        'name'    	 => $product_info['name'],
                        'price'   	 => $price,
                        'special' 	 => $special,
                        'rating'     => $rating,
                        'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
                        'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
                        'options'    => $this->data['options']
                    );

                }
                
            }
            
		}

        echo $this->load->view($this->modulePath.'/productbundles_options', $this->data);
        exit;
       
	}
	
    public function listing() {
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['moduleData']         = $this->config->get('productbundles');
		$picture_width                    = !empty($this->data['moduleData']['ListingPictureWidth']) ? $this->data['moduleData']['ListingPictureWidth'] : '100';
		$picture_height                   = !empty($this->data['moduleData']['ListingPictureHeight']) ? $this->data['moduleData']['ListingPictureHeight'] : '100';
        
		$this->document->setTitle($this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')]);
		$this->document->setDescription($this->data['moduleData']['MetaDescription'][$this->config->get('config_language_id')]);
		$this->document->setKeywords($this->data['moduleData']['MetaKeywords'][$this->config->get('config_language_id')]);

		$this->data['heading_title'] = (!empty($this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')]) ? $this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')] : $this->language->get('listing_heading_title'));
		
        $this->data['breadcrumbs']      = array();
		$this->data['breadcrumbs'][]    = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home'),			
            'separator' => false
        );
        $this->data['breadcrumbs'][]    = array(
            'text'      => $this->language->get('text_breadcrumb'),
            'href'      => $this->url->link($this->modulePath.'/listing'),
            'separator' => $this->language->get('text_separator')
        );

		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else { 
			$page = 1;
		}
        
        if (isset($this->data['moduleData']['ListingLimit'])) {
			$limit = $this->data['moduleData']['ListingLimit'];
		} else { 
			$limit = 10;
		}
		
        $bundles                    = array();
        $filter                     = array(
            'limit' => $limit,
            'page'  => $page
        );
        
        $bundles                    = $this->moduleModel->getBundles($filter);
        $total_bundles              = $this->moduleModel->getTotalBundles($filter);

        $this->data['bundles'] = array();
        
        if ($bundles) {
            foreach ($bundles as $index => $bundle) {
                $total_price = 0;
				$total_price_no_taxes = 0;

                $this->data['bundles'][$bundle['id']] = array();
                $this->data['bundles'][$bundle['id']]['id'] = $bundle['id'];
                $this->data['bundles'][$bundle['id']]['products'] = array();
                $this->data['bundles'][$bundle['id']]['product_options'] = 'false';
                
                foreach ($bundle['products'] as $product_id) {	
                    $product_info = $this->model_catalog_product->getProduct($product_id);
    
                    if (!isset($this->data['bundles'][$bundle['id']]['products'][$product_id])) {

                        if ($product_info['image']) {
                            $image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
                        } else {
                            $image = false;
                        }

                        if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                            $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                        } else {
                            $price = false;
                        }

                        if ((float)$product_info['special']) {
                            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                            
                            $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                            $total_price_no_taxes += $product_info['special'];						
                        } else {
                            $special = false;

                            $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                            $total_price_no_taxes += $product_info['price'];
                        }

                        $product_options = $this->model_catalog_product->getProductOptions($product_id);
                        if (!empty($product_options)) {
                            $this->data['bundles'][$bundle['id']]['product_options'] = 'true';
                        }
                        
                        $this->data['bundles'][$bundle['id']]['products'][$product_id] = array(
                            'product_id' => $product_id,
                            'quantity'	 => 1,
                            'thumb'   	 => $image,
                            'name'    	 => $product_info['name'],
                            'price'   	 => $price,
                            'special' 	 => $special,
                            'href'    	 => $this->url->link('product/product', 'product_id=' . $product_id)
                        );
                        
                    } else {
                        if ((float)$product_info['special']) {
                            $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                            
                            $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                            $total_price_no_taxes += $product_info['special'];						
                        } else {
                            $special = false;

                            $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                            $total_price_no_taxes += $product_info['price'];
                        }
                        
                        $this->data['bundles'][$bundle['id']]['products'][$product_id]['quantity'] += 1;
                    }
                } // end foreach ($bundle['products'] as $product_id)
                
                if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation'] == 'no') {
                    if ($bundle['discount_type'] == '1') {
                        $discount_value = $bundle['discount_value'];
                    } else if ($bundle['discount_type'] == '2') {
                        $percentage = $bundle['discount_value']/100;
                        $discount_value = $percentage*$total_price;
                    }
                } else {
                    if ($bundle['discount_type'] == '1') {
                        $discount_value = $bundle['discount_value'];
                    } else if ($bundle['discount_type'] == '2') {
                        $percentage = $bundle['discount_value']/100;
                        $discount_value = $percentage*$total_price_no_taxes;
                    }
                }
                
                if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
					foreach ($bundle['products'] as $result) {
						$product_info = $this->model_catalog_product->getProduct($result);
						if ((float)$product_info['special']) {
							$ratio = $total_price_no_taxes / $product_info['special'];
						} else {
							$ratio = $total_price_no_taxes / $product_info['price'];
						}
						
						$tax_rates = $this->tax->getRates((float)$discount_value / $ratio, $product_info['tax_class_id']);
						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 'P') {
								$total_price -= $tax_rate['amount'];
							}
						}
					}
				}
					
                $final_price = $total_price - $discount_value;
                $this->data['bundles'][$bundle['id']]['discount_value'] = $discount_value;
				$this->data['bundles'][$bundle['id']]['total_price'] = $this->currency->format($total_price, $this->currency_code);
				$this->data['bundles'][$bundle['id']]['discount_value'] = $this->currency->format($discount_value, $this->currency_code);
				$this->data['bundles'][$bundle['id']]['final_price'] = $this->currency->format($final_price, $this->currency_code);
                
                if (!empty($bundle['name'][$this->config->get('config_language_id')])) {
					$this->data['bundles'][$bundle['id']]['name'] = $bundle['name'][$this->config->get('config_language_id')];
				} else {
					$this->data['bundles'][$bundle['id']]['name'] = $this->language->get('view_bundle');
				}
                
                if (!empty($bundle['description'][$this->config->get('config_language_id')])) {
					$this->data['bundles'][$bundle['id']]['description'] = $bundle['description'][$this->config->get('config_language_id')];
				} else {
					$this->data['bundles'][$bundle['id']]['description'] = $this->language->get('view_bundle');
				}
                
                $this->data['bundles'][$bundle['id']]['description'] = utf8_substr(strip_tags(html_entity_decode($this->data['bundles'][$bundle['id']]['description'], ENT_QUOTES, 'UTF-8')), 0, 150) . '..';
                
                $this->data['bundles'][$bundle['id']]['url'] = $this->url->link($this->modulePath . '/view', 'bundle_id=' . $bundle['id'], 'SSL');

                
            } // end foreach ($bundles as $index => $bundle)
            
        }
        
		$url = '';
        
        $total                    = $total_bundles;
		$pagination               = new Pagination();
		$pagination->total        = $total;
		$pagination->page         = $page;
		$pagination->limit        = $limit;
		$pagination->url          = $this->url->link($this->modulePath.'/listing', $url.'&page={page}');
		$this->data['pagination'] = $pagination->render();
		$this->data['results']    = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));
			
		if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing')) {
			$this->document->addStyle('catalog/view/theme/'. $this->getConfigTemplate() . '/stylesheet/'.$this->moduleName.'/productbundles.css');
		} else {
			$this->document->addStyle('catalog/view/theme/default/stylesheet/'.$this->moduleName.'/productbundles.css');
		}
		
		$this->data['column_left']				            = $this->load->controller('common/column_left');
		$this->data['column_right']				            = $this->load->controller('common/column_right');
		$this->data['content_top']				            = $this->load->controller('common/content_top');
		$this->data['content_bottom']				        = $this->load->controller('common/content_bottom');
		$this->data['footer']						        = $this->load->controller('common/footer');
		$this->data['header']						        = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view($this->modulePath.'/productbundles_listing', $this->data));
	}
    
    public function view() {
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_breadcrumb'),
			'href' => $this->url->link($this->modulePath.'/listing')
		);
		
		$this->data['moduleData']         = $this->config->get($this->moduleName);
		
      	$this->data['heading_title']      = $this->language->get('heading_title');
		$picture_width                    = isset($this->data['moduleData']['ViewWidth']) ? $this->data['moduleData']['ViewWidth'] : '100';
		$picture_height                   = isset($this->data['moduleData']['ViewHeight']) ? $this->data['moduleData']['ViewHeight'] : '100';		
		$bundle_id                        = (isset($this->request->get['bundle_id']) && !empty($this->request->get['bundle_id'])) ? $this->request->get['bundle_id'] : 0;
		
        $bundle_data                      = $this->moduleModel->getBundle($bundle_id);
        $bundle_data                      = is_array($bundle_data) ? current($bundle_data) : array();
		
		if (!empty($bundle_data)) {
            $total_price = 0;
            $total_price_no_taxes = 0;

            $this->data['bundle'] = array();
            $this->data['bundle']['id'] = $bundle_data['id'];
            $this->data['bundle']['products'] = array();
            $this->data['bundle']['product_options'] = 'false';

            foreach ($bundle_data['products'] as $product_id) {	
                $product_info = $this->model_catalog_product->getProduct($product_id);

                if (!isset($this->data['bundle']['products'][$product_id])) {

                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
                    } else {
                        $image = false;
                    }

                    if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);
                    } else {
                        $price = false;
                    }

                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);

                        $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                        $total_price_no_taxes += $product_info['special'];						
                    } else {
                        $special = false;

                        $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                        $total_price_no_taxes += $product_info['price'];
                    }

                    $product_options = $this->model_catalog_product->getProductOptions($product_id);
                    if (!empty($product_options)) {
                        $this->data['bundle']['product_options'] = 'true';
                    }

                    $this->data['bundle']['products'][$product_id] = array(
                        'product_id' => $product_id,
                        'quantity'	 => 1,
                        'thumb'   	 => $image,
                        'name'    	 => $product_info['name'],
                        'price'   	 => $price,
                        'special' 	 => $special,
                        'href'    	 => $this->url->link('product/product', 'product_id=' . $product_id)
                    );

                } else {
                    if ((float)$product_info['special']) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->currency_code);

                        $total_price += $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
                        $total_price_no_taxes += $product_info['special'];						
                    } else {
                        $special = false;

                        $total_price += $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                        $total_price_no_taxes += $product_info['price'];
                    }

                   $this->data['bundle']['products'][$product_id]['quantity'] += 1;
                }
            } // end foreach ($bundle['products'] as $product_id)

            if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation'] == 'no') {
                if ($bundle_data['discount_type'] == '1') {
                    $discount_value = $bundle_data['discount_value'];
                } else if ($bundle_data['discount_type'] == '2') {
                    $percentage = $bundle_data['discount_value']/100;
                    $discount_value = $percentage*$total_price;
                }
            } else {
                if ($bundle_data['discount_type'] == '1') {
                    $discount_value = $bundle_data['discount_value'];
                } else if ($bundle_data['discount_type'] == '2') {
                    $percentage = $bundle_data['discount_value']/100;
                    $discount_value = $percentage*$total_price_no_taxes;
                }
            }

            if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
                foreach ($bundle_data['products'] as $result) {
                    $product_info = $this->model_catalog_product->getProduct($result);
                    if ((float)$product_info['special']) {
                        $ratio = $total_price_no_taxes / $product_info['special'];
                    } else {
                        $ratio = $total_price_no_taxes / $product_info['price'];
                    }

                    $tax_rates = $this->tax->getRates((float)$discount_value / $ratio, $product_info['tax_class_id']);
                    foreach ($tax_rates as $tax_rate) {
                        if ($tax_rate['type'] == 'P') {
                            $total_price -= $tax_rate['amount'];
                        }
                    }
                }
            }
					
            $final_price = $total_price - $discount_value;
            $this->data['bundle']['discount_value'] = $discount_value;
            $this->data['bundle']['total_price'] = $this->currency->format($total_price, $this->currency_code);
            $this->data['bundle']['discount_value'] = $this->currency->format($discount_value, $this->currency_code);
            $this->data['bundle']['final_price'] = $this->currency->format($final_price, $this->currency_code);

            if (!empty($bundle_data['name'][$this->config->get('config_language_id')])) {
                $this->data['bundle']['name'] = $bundle_data['name'][$this->config->get('config_language_id')];
                $this->data['heading_title']  = $bundle_data['name'][$this->config->get('config_language_id')];
            } else {
                $this->data['bundle']['name'] = $this->language->get('view_bundle');
                $this->data['heading_title'] = $this->language->get('view_bundle');
            }

            if (!empty($bundle_data['description'][$this->config->get('config_language_id')])) {
                $this->data['bundle']['description'] = $bundle_data['description'][$this->config->get('config_language_id')];
            } else {
                $this->data['bundle']['description'] = $this->language->get('view_bundle');
            }
            
            $this->data['bundle']['description'] = html_entity_decode($this->data['bundle']['description'], ENT_QUOTES, 'UTF-8');

            $this->data['bundle']['url'] = $this->url->link($this->modulePath . '/view', 'bundle_id=' . $bundle_data['id'], 'SSL');
                
			$this->document->setTitle($this->data['heading_title']);

			$this->data['breadcrumbs'][] = array(
				'text' => $this->data['heading_title'],
				'href' => $this->url->link($this->modulePath.'/view', 'bundle_id='.$bundle_id)
			);

			if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing')) {
				$this->document->addStyle('catalog/view/theme/'.$this->getConfigTemplate() . '/stylesheet/'.$this->moduleName.'/'.$this->moduleName.'.css');
			} else {
				$this->document->addStyle('catalog/view/theme/default/stylesheet/'.$this->moduleName.'/'.$this->moduleName.'.css');
			}
	
			$this->data['column_left']				    = $this->load->controller('common/column_left');
			$this->data['column_right']				    = $this->load->controller('common/column_right');
			$this->data['content_top']				    = $this->load->controller('common/content_top');
			$this->data['content_bottom']				= $this->load->controller('common/content_bottom');
			$this->data['footer']						= $this->load->controller('common/footer');
			$this->data['header']						= $this->load->controller('common/header');

			$this->response->setOutput($this->load->view($this->modulePath.'/productbundles_view', $this->data));
		} else {
			$this->data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link($this->modulePath.'/view', 'bundle_id=' . $bundleNumber)
			);

			$this->document->setTitle($this->language->get('text_error'));
        
			$this->data['heading_title']        = $this->language->get('text_error');
			$this->data['text_error']           = $this->language->get('text_error');
			$this->data['button_continue']      = $this->language->get('button_continue');
			$this->data['continue']             = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$this->data['column_left']          = $this->load->controller('common/column_left');
			$this->data['column_right']         = $this->load->controller('common/column_right');
			$this->data['content_top']          = $this->load->controller('common/content_top');
			$this->data['content_bottom']       = $this->load->controller('common/content_bottom');
			$this->data['footer']               = $this->load->controller('common/footer');
			$this->data['header']               = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $this->data));
		}
	}
    
    /* Helper functions - Begin */
	protected function getConfigTemplate(){
		return  $this->config->get($this->config->get('config_theme') . '_directory');
	}
    /* Helper functions - End */
 
    /*
    * Event for SEO URLs (1/2)
    * catalog/asterisk/before
    */
    public function customUrlFunctionality($eventRoute) {
        if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

        if (isset($this->request->get['_route_'])) {

            $parts = explode('/', $this->request->get['_route_']);

            $ProductBundles = $this->config->get('productbundles');
            $pbSeoSlug = isset($ProductBundles['SeoURL']) ? $ProductBundles['SeoURL'] : array('bundles');

            $parts = array_filter($parts);

            foreach ($pbSeoSlug as $pb_slug) { 
                if (count($parts) == 1 && ($parts[0] == $pb_slug)) {
                    $this->request->get['route'] = 'extension/module/productbundles/listing';
                    return new Action($this->request->get['route']);
                }

                if (count($parts) == 2 && ($parts[0] == $pb_slug) && !empty($parts[1])) {
                    $bundle_check = $this->db->query("SELECT `id` from `" . DB_PREFIX ."pb_bundles` WHERE `slug`='" . $this->db->escape($parts[1]) . "' LIMIT 1");
                    if ($bundle_check->num_rows) {
                       $this->request->get['route'] = 'extension/module/productbundles/view';
                       $this->request->get['bundle_id'] = $bundle_check->row['id'];
                       return new Action($this->request->get['route']); 
                    }

                }
            }
        }      
    }
    
    /*
    * Event for SEO URLs (1/2)
    * catalog/asterisk/before
    */
    public function rewrite($link) {
        
        $url_info = parse_url(str_replace('&amp;', '&', $link));

        $url = '';

		$data = array();

        if (!empty($url_info['query'])) {
            parse_str($url_info['query'], $data);

            $ProductBundles = $this->config->get('productbundles');
            $pbSeoSlug = isset($ProductBundles['SeoURL'][$this->config->get('config_language_id')]) ? $ProductBundles['SeoURL'][$this->config->get('config_language_id')] : 'bundles';
            if (isset($data['route']) && $data['route'] == 'extension/module/productbundles/listing') {
                $url .= '/'.$pbSeoSlug;
            }

            if (isset($data['route']) && isset($data['bundle_id']) && $data['route'] == 'extension/module/productbundles/view') {
                $this->config->load('isenselabs/productbundles');
                $call_model = $this->config->get('productbundles_model');
                $module_path = $this->config->get('productbundles_path');
                $this->load->model($module_path);

                $bundle_data = $this->$call_model->getBundle($data['bundle_id']);
                $bundle_data = is_array($bundle_data) ? current($bundle_data) : array();

                if (!empty($bundle_data) && !empty($bundle_data['slug'])) {
                    $url .= '/'.$pbSeoSlug.'/'.$bundle_data['slug'];
                    unset($data['bundle_id']);
                }
            }
            
            if ($url) {
                unset($data['route']);

                $query = '';

                if ($data) {
                    foreach ($data as $key => $value) {
                        $query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
                    }

                    if ($query) {
                        $query = '?' . str_replace('&', '&amp;', trim($query, '&'));
                    }
                }

                return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
            } else {
                return $link;
            }
        } else {
            return $link;
        }
    }
    
    /*
    * Event for SEO URLs (1/2)
    * catalog/view/common/menu/before
    */
    public function injectCatalogMenuItem($eventRoute, &$data) {
        $ProductBundles = $this->config->get('productbundles');

        if ((isset($ProductBundles)) && ($ProductBundles['Enabled']== 'yes') && ($ProductBundles['MainLinkEnabled']== 'yes') && (!empty($ProductBundles['LinkTitle'][$this->config->get('config_language_id')]))) {
            
            foreach ($data['categories'] as $order => $category) {
                $data['categories'][$order]['sort_order'] = $order+1;
            }
            
            $data['categories'][] = array(
                'name'     => $ProductBundles['LinkTitle'][$this->config->get('config_language_id')],
                'children' => array(),
                'column'   => 1,
                'sort_order' => $ProductBundles['LinkSortOrder'],
                'href'     => $this->url->link('extension/module/productbundles/listing')
            );

            if (!function_exists('cmpCategoriesOrder')) {
                function cmpCategoriesOrder($a, $b) {
                    if ($a['sort_order'] == $b['sort_order']) {
                        return 0;
                    }
                    return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
                }
            }

            uasort($data['categories'], 'cmpCategoriesOrder');
        }
    }
    
}