<?php 
class ModelExtensionModuleProductbundles extends Model {

  	public function install() {
		// Noting here for now
  	} 
  
  	public function uninstall() {
		// Nothing here for now
  	}
    
    // Database initializations
    public function initDb() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pb_bundles` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `name` text NOT NULL,
            `description` text NOT NULL,
            `image` varchar(255) NOT NULL DEFAULT '',
            `slug` text NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `discount_type` tinyint(10) NOT NULL DEFAULT '1',
            `discount_value` decimal(15,2),
            `sort_order` int(11) NOT NULL DEFAULT '0',
            `products` text NOT NULL,
            `products_show` text NOT NULL,
            `categories_show` text NOT NULL,
            `store_id` int(11) UNSIGNED NOT NULL,
            `date_available` datetime NOT NULL,
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`id`)
        )");
    }
    
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
        if (!isset($data['store_id'])) $data['store_id'] = 0;
        if (!isset($data['date_available'])) $data['date_available'] = date('Y-m-d');
        
        return $data;
    }
    
    public function addBundle($data = array()) {
        
        $data = $this->cleanTheArray($data);
        
        $query = $this->db->query("INSERT INTO `" . DB_PREFIX . "pb_bundles` SET 
            `name` = '" . $this->db->escape(json_encode($data['name'])) . "',
            `description` = '" . $this->db->escape(json_encode($data['description'])) . "',
            `image` = '" . $this->db->escape($data['image']) . "',
            `slug` =  '" . $this->db->escape($data['slug']) . "',
            `status` = '" . $this->db->escape($data['status']). "',
            `discount_type` = '" . $this->db->escape($data['discount_type']). "',
            `discount_value` = '" . $this->db->escape($data['discount_value']) . "',
            `sort_order` = '" . $this->db->escape($data['sort_order']) . "',
            `products` = '" . $this->db->escape(json_encode($data['products'])) . "',
            `products_show` = '" . $this->db->escape(json_encode($data['products_show'])) . "',
            `categories_show` = '" . $this->db->escape(json_encode($data['categories_show'])) . "',
            `store_id` = '" . $this->db->escape($data['store_id']) . "',
            `date_available` = '" . $this->db->escape($data['date_available']) . "',
            `date_added` = NOW(),
            `date_modified` = NOW()
        ");
        
        return true;
    }
    
    public function editBundle($data = array()) {
        
        $query = $this->db->query("UPDATE `" . DB_PREFIX . "pb_bundles` SET 
            `name` = '" . $this->db->escape(json_encode($data['name'])) . "',
            `description` = '" . $this->db->escape(json_encode($data['description'])) . "',
            `image` = '" . $this->db->escape($data['image']) . "',
            `slug` =  '" . $this->db->escape($data['slug']) . "',
            `status` = '" . $this->db->escape($data['status']). "',
            `discount_type` = '" . $this->db->escape($data['discount_type']). "',
            `discount_value` = '" . $this->db->escape($data['discount_value']) . "',
            `sort_order` = '" . $this->db->escape($data['sort_order']) . "',
            `products` = '" . $this->db->escape(json_encode($data['products'])) . "',
            `products_show` = '" . $this->db->escape(json_encode($data['products_show'])) . "',
            `categories_show` = '" . $this->db->escape(json_encode($data['categories_show'])) . "',
            `date_available` = '" . $this->db->escape($data['date_available']) . "',
            `date_modified` = NOW()
            WHERE `id` = '" . $this->db->escape($data['id']) . "'
        ");
        
        return true;
    }
    
    public function getBundle($bundle_id = 0) {
        $query = "SELECT * FROM `" . DB_PREFIX . "pb_bundles` WHERE `id`='".$this->db->escape($bundle_id)."' LIMIT 1";
        
        $query = $this->db->query($query);
        
        if ($query->num_rows) {
            return $query->row;  
        } else {
            return false;
        }
		
    }
    
    public function deleteBundle($bundle_id = 0) {
        $query = "DELETE FROM `" . DB_PREFIX . "pb_bundles` WHERE `id`='".$this->db->escape($bundle_id)."'";
        
        $query = $this->db->query($query);
        
        return true;
    }
    
    public function getTotalBundles($data = array()) {
        $query = "SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "pb_bundles` WHERE 1=1";
        
        $query .= " AND `store_id`='" . $this->db->escape($data['store_id']) . "'";
        
        if (!empty($data['filter_name'])) {
            $query .= " AND `name` LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !='*') {
            $query .= " AND `status` = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_discount'])) {
            $query .= " AND `discount_value` = '" . $this->db->escape($data['filter_discount']) . "'";
        }
        
        if (!empty($data['filter_product_id'])) {
            $query .= " AND `products` LIKE '%\"" . $this->db->escape($data['filter_product_id']) . "\"%'";
        }

		$query = $this->db->query($query);
        
		return $query->row['count']; 
    }
    
    public function getBundles($data = array()) {
        $query = "SELECT * FROM `" . DB_PREFIX . "pb_bundles` WHERE 1=1";
        
        $query .= " AND `store_id`='" . $this->db->escape($data['store_id']) . "'";
        
        if ($data['page']) {
			$start = ($data['page'] - 1) * $data['limit'];
		}
        
        if (!empty($data['filter_name'])) {
            $query .= " AND `name` LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !='*') {
            $query .= " AND `status` = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (!empty($data['filter_discount'])) {
            $query .= " AND `discount_value` = '" . $this->db->escape($data['filter_discount']) . "'";
        }
        
        if (!empty($data['filter_product_id'])) {
            $query .= " AND `products` LIKE '%\"" . $this->db->escape($data['filter_product_id']) . "\"%'";
        }
        
        $query .= "ORDER BY `id` DESC LIMIT ".$start.", ". $data['limit'];
        
        $query = $this->db->query($query);

        return $query->rows;
    }
	
}