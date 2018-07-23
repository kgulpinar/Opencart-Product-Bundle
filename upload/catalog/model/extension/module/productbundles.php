<?php 
class ModelExtensionModuleProductbundles extends Model {

    public function cleanTheArray($data = array()) {
        if (!isset($data['name'])) $data['name'] = array();
        if (!isset($data['description'])) $data['description'] = array();
        if (!isset($data['image'])) $data['image'] = '';
        if (!isset($data['slug'])) $data['slug'] = '';
        if (!isset($data['status'])) $data['status'] = '1';
        if (!isset($data['discount_type'])) $data['discount_type'] = '1';
        if (!isset($data['discount_value'])) $data['discount_value'] = '0';
        if (!isset($data['sort_order'])) $data['sort_order'] = '0';
        if (!isset($data['products'])) $data['products'] = array();
        if (!isset($data['products_show'])) $data['products_show'] = array();
        if (!isset($data['categories_show'])) $data['categories_show'] = array();
        if (!isset($data['date_available'])) $data['date_available'] = date('Y-m-d');
        
        return $data;
    }
    
    public function getBundle($bundle_id = 0) {
        $query = "SELECT * FROM `" . DB_PREFIX . "pb_bundles` WHERE 
            `id`='".$this->db->escape($bundle_id)."' 
            AND `store_id`='" . $this->config->get('config_store_id') . "'
            AND `status`= '1' 
            AND `date_available` <= NOW()
            LIMIT 1";
        
        $query = $this->db->query($query);
        
        if ($query->num_rows) {
            return $this->filterBundles(array(0 => $query->row));
        } else {
            return false;
        }
		
    }
    
    public function filterBundles($results = array()) {
        $this->load->model('catalog/product');
        
        foreach ($results as $index => $result) {
            $products = json_decode($result['products'], true);
            $unset = false;
            
            foreach ($products as $product_id) {
                $product_info = $this->model_catalog_product->getProduct($product_id);
                
                if (($product_info && $product_info['quantity']<=0) || empty($product_info)) {
                    unset($results[$index]);
                    $unset = true;
                    break;
                }
            }
            
            if (!$unset) {
                $results[$index]['products'] = $products;
                $results[$index]['name'] = json_decode($result['name'], true);
                $results[$index]['description'] = json_decode($result['description'], true);
            }
        }

        return $results;
    }
    

    public function getBundles($data = array()) {
        
        $query = "SELECT * FROM `" . DB_PREFIX . "pb_bundles` WHERE 
            `store_id`='" . $this->config->get('config_store_id') . "' 
            AND `products` != ''
            AND `status`= '1' 
            AND `date_available` <= NOW()
        ";
        
        if (!empty($data['product_id'])) {
            $query .= ' AND `products_show` LIKE \'%"' . $data['product_id'] . '"%\'';
        }
        
        if (!empty($data['category_id'])) {
            $query .= ' AND `categories_show` LIKE \'%"' . $data['category_id'] . '"%\'';
        }
        
        if (!empty($data['order'])) {
            if ($data['order'] == 'random') {
                $query .= " ORDER BY RAND()";
            }
        } else {
           $query .= " ORDER BY `sort_order` ASC"; 
        }
        
        
        if (!empty($data['limit'])) {
            $data['start'] = $data['limit'] * ($data['page']-1);

            $query .= " LIMIT " . $data['start'] . ", " . $data['limit'];
        }

        $query = $this->db->query($query);

        if ($query->num_rows) {
            return $this->filterBundles($query->rows);
        } else {
            return array();
        }
        
    }
    
    public function getTotalBundles($data = array()) {
        $query = "SELECT * FROM `" . DB_PREFIX . "pb_bundles` WHERE 
            `store_id`='" . $this->config->get('config_store_id') . "' 
            AND `products` != ''
            AND `status`= '1' 
            AND `date_available` <= NOW()
        ";
        
        if (!empty($data['product_id'])) {
            $query .= ' AND `products_show` LIKE \'%"' . $data['product_id'] . '"%\'';
        }
        
        if (!empty($data['category_id'])) {
            $query .= ' AND `categories_show` LIKE \'%"' . $data['category_id'] . '"%\'';
        }
        
        if (!empty($data['order'])) {
            if ($data['order'] == 'random') {
                $query .= " ORDER BY RAND()";
            }
        } else {
           $query .= " ORDER BY `sort_order` ASC"; 
        }

        $query = $this->db->query($query);

        if ($query->num_rows) {
            return count($this->filterBundles($query->rows));
        } else {
            return 0;
        }
    }
	
}