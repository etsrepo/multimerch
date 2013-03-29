<?php
class ModelMultisellerSettings extends Model {
	public function checkDbVersion($version) {
		switch ($version) {
			case "2.2":
				$res = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "ms_comments` LIKE 'parent_id'");
				break;
				
			case "2.3":
				$res = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "ms_product_attribute` LIKE 'attribute_id'");
				break;
		}
		
		if ($res->num_rows)
			return true;
			
		return false;
	}

	public function update($version) {
		if (!$this->checkDbVersion($version)) {
			switch ($version) {
				case "2.4":
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_seller CHANGE `nickname` `nickname` VARCHAR(255) NOT NULL");
						$this->load->model('user/user_group');
						$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'multiseller/comment');
						$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'multiseller/comment');
					break;
					
				case "2.3":
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_product_attribute CHANGE `option_id` `attribute_id` int(11) NOT NULL");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_product_attribute CHANGE `option_value_id` `attribute_value_id` int(11) NOT NULL");
					$this->db->query("CREATE TABLE `" . DB_PREFIX . "ms_attribute` (`attribute_id` int(11) NOT NULL AUTO_INCREMENT, `attribute_type` int(11) NOT NULL, `number` TINYINT NOT NULL DEFAULT 0, `multilang` TINYINT NOT NULL DEFAULT 0, `required` TINYINT NOT NULL DEFAULT 0, `enabled` TINYINT NOT NULL DEFAULT 1, `sort_order` int(3) NOT NULL, PRIMARY KEY (`attribute_id`)) DEFAULT CHARSET=utf8");
					$this->db->query("CREATE TABLE `" . DB_PREFIX . "ms_attribute_description` (`attribute_id` int(11) NOT NULL, `language_id` int(11) NOT NULL, `name` varchar(128) NOT NULL, `description` TEXT NOT NULL DEFAULT '', PRIMARY KEY (`attribute_id`,`language_id`)) DEFAULT CHARSET=utf8");
					$this->db->query("CREATE TABLE `" . DB_PREFIX . "ms_attribute_value` (`attribute_value_id` int(11) NOT NULL AUTO_INCREMENT, `attribute_id` int(11) NOT NULL, `image` varchar(255) NOT NULL, `sort_order` int(3) NOT NULL, PRIMARY KEY (`attribute_value_id`)) DEFAULT CHARSET=utf8");
					$this->db->query("CREATE TABLE `" . DB_PREFIX . "ms_attribute_value_description` (`attribute_value_id` int(11) NOT NULL,`language_id` int(11) NOT NULL, `attribute_id` int(11) NOT NULL, `name` varchar(128) NOT NULL, PRIMARY KEY (`attribute_value_id`,`language_id`)) DEFAULT CHARSET=utf8");

					$option_ids = implode(',',$this->config->get('msconf_product_options'));
					if (empty($option_ids)) return true;
						
					$res = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option` WHERE  option_id IN ($option_ids)");
					foreach ($res->rows as $option) {
						switch($option["type"]) {
							case "checkbox":
								$type = MsAttribute::TYPE_CHECKBOX;
								break;
		
							case "radio":
								$type = MsAttribute::TYPE_RADIO;
								break;
								
							case "select":
								$type = MsAttribute::TYPE_SELECT;
								break;
								
							default:
								continue;
						}
						
						$this->db->query("INSERT INTO `" . DB_PREFIX . "ms_attribute` (attribute_id, attribute_type, sort_order) VALUES ({$option['option_id']}, $type, {$option['sort_order']})");
						$this->db->query("INSERT INTO `" . DB_PREFIX . "ms_attribute_description` (attribute_id, language_id, name) SELECT option_id, language_id, name FROM `" . DB_PREFIX . "ms_option_description` WHERE option_id = {$option['option_id']}");
						$this->db->query("INSERT INTO `" . DB_PREFIX . "ms_attribute_value` (attribute_value_id, attribute_id, image, sort_order) SELECT option_value_id, option_id, image, sort_order FROM `" . DB_PREFIX . "ms_option_value` WHERE option_id = {$option['option_id']}");
						$this->db->query("INSERT INTO `" . DB_PREFIX . "ms_attribute_value_description` (attribute_value_id, language_id, attribute_id, name) SELECT option_value_id, language_id, option_id, name FROM `" . DB_PREFIX . "ms_option_value_description` WHERE option_id = {$option['option_id']}");
						
						$this->load->model('user/user_group');
						$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'multiseller/attribute');
						$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'multiseller/attribute');
					}

				case "2.2":
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments ADD `parent_id` int(11) DEFAULT NULL AFTER `id`");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments CHANGE `id_product` `product_id` int(11) NOT NULL");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments CHANGE `id_customer` `customer_id` int(11) DEFAULT NULL");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments ADD `user_id` int(11) DEFAULT NULL AFTER `customer_id`");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments CHANGE `name` `name` varchar(128) NOT NULL DEFAULT ''");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments CHANGE `create_time` `create_time` int(11) NOT NULL");
					$this->db->query("ALTER TABLE " . DB_PREFIX . "ms_comments CHANGE `display` `display` tinyint(1) NOT NULL");
					
				default:
					break;
			}
		}
	}

	public function createTable() {
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_commission` (
             `commission_id` int(11) NOT NULL AUTO_INCREMENT,
        	PRIMARY KEY (`commission_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_commission_rate` (
             `rate_id` int(11) NOT NULL AUTO_INCREMENT,
             `rate_type` int(11) NOT NULL,
			 `commission_id` int(11) NOT NULL,
			 `flat` DECIMAL(15,4),
			 `percent` DECIMAL(15,2),
        	PRIMARY KEY (`rate_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
        
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_seller_group` (
             `seller_group_id` int(11) NOT NULL AUTO_INCREMENT,
			 `commission_id` int(11) DEFAULT NULL,
        	PRIMARY KEY (`seller_group_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
        		
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_seller_group_description` (
             `seller_group_description_id` int(11) NOT NULL AUTO_INCREMENT,
			 `seller_group_id` int(11) NOT NULL,
			 `name` VARCHAR(32) NOT NULL DEFAULT '',
             `description` TEXT NOT NULL DEFAULT '',
			 `language_id` int(11) DEFAULT NULL,
        	PRIMARY KEY (`seller_group_description_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_product` (
             `product_id` int(11) NOT NULL,
             `seller_id` int(11) DEFAULT NULL,
             `number_sold` int(11) NOT NULL DEFAULT '0',
			 `product_status` TINYINT NOT NULL,
			 `product_approved` TINYINT NOT NULL,
        	PRIMARY KEY (`product_id`)) default CHARSET=utf8";
        $this->db->query($sql);
        
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_seller` (
             `seller_id` int(11) NOT NULL AUTO_INCREMENT,
             `nickname` VARCHAR(32) NOT NULL DEFAULT '',
             `company` VARCHAR(32) NOT NULL DEFAULT '',
             `website` VARCHAR(2083) NOT NULL DEFAULT '',
             `description` TEXT NOT NULL DEFAULT '',
			 `country_id` INT(11) NOT NULL DEFAULT '0',
			 `avatar` VARCHAR(255) DEFAULT NULL,
			 `paypal` VARCHAR(255) DEFAULT NULL,
			 `date_created` DATETIME NOT NULL,
			 `seller_status` TINYINT NOT NULL,
			 `seller_approved` TINYINT NOT NULL,
			 `product_validation` tinyint(4) NOT NULL DEFAULT '1',
			 `seller_group` int(11) NOT NULL DEFAULT '1',
			 `commission_id` int(11) DEFAULT NULL,
        	PRIMARY KEY (`seller_id`)) default CHARSET=utf8";
        $this->db->query($sql);
        
		$createTable = "
			CREATE TABLE " . DB_PREFIX . "ms_comments (
	         `id` int(11) NOT NULL AUTO_INCREMENT,
	         `parent_id` int(11) DEFAULT NULL,
	         `product_id` int(11) NOT NULL,
	         `seller_id` int(11) DEFAULT NULL,
	         `customer_id` int(11) DEFAULT NULL,
			 `user_id` int(11) DEFAULT NULL,
	         `name` varchar(128) NOT NULL DEFAULT '',
	         `email` varchar(128) NOT NULL DEFAULT '',
	         `comment` text NOT NULL,
	         `display` tinyint(1) NOT NULL DEFAULT 1,
	         `create_time` int(11) NOT NULL,
	    	PRIMARY KEY (`id`)) default CHARSET=utf8";
        $this->db->query($createTable);
	
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_balance` (
             `balance_id` int(11) NOT NULL AUTO_INCREMENT,
             `seller_id` int(11) NOT NULL,
             `order_id` int(11) DEFAULT NULL,
             `product_id` int(11) DEFAULT NULL,
             `withdrawal_id` int(11) DEFAULT NULL,
             `balance_type` int(11) DEFAULT NULL,
             `amount` DECIMAL(15,4) NOT NULL,
             `balance` DECIMAL(15,4) NOT NULL,
             `description` TEXT NOT NULL DEFAULT '',
			 `date_created` DATETIME NOT NULL,
			 `date_modified` DATETIME DEFAULT NULL,
        	PRIMARY KEY (`balance_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
	
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_order_product_data` (
             `order_product_data_id` int(11) NOT NULL AUTO_INCREMENT,
             `order_id` int(11) NOT NULL,
             `product_id` int(11) NOT NULL,
             `seller_id` int(11) DEFAULT NULL,
             `store_commission_flat` DECIMAL(15,4) NOT NULL,
             `store_commission_pct` DECIMAL(15,4) NOT NULL,
             `seller_net_amt` DECIMAL(15,4) NOT NULL,
        	PRIMARY KEY (`order_product_data_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
        
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_withdrawal` (
             `withdrawal_id` int(11) NOT NULL AUTO_INCREMENT,
             `seller_id` int(11) NOT NULL,
             `amount` DECIMAL(15,4) NOT NULL,
             `withdrawal_method_id` int(11) DEFAULT NULL,
             `withdrawal_method_data` TEXT NOT NULL DEFAULT '',
			 `withdrawal_status` TINYINT NOT NULL,
             `currency_id` int(11) NOT NULL,
             `currency_code` VARCHAR(3) NOT NULL,
             `currency_value` DECIMAL(15,8) NOT NULL,
			 `description` TEXT NOT NULL DEFAULT '',
             `processed_by` int(11) DEFAULT NULL,
			 `date_created` DATETIME NOT NULL,
			 `date_processed` DATETIME DEFAULT NULL,
        	PRIMARY KEY (`withdrawal_id`)) default CHARSET=utf8";
        	
        $this->db->query($sql);
/*
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_request_seller` (
             `request_seller_id` int(11) NOT NULL AUTO_INCREMENT,
			 `request_id` int(11) NOT NULL,
             `seller_id` int(11) NOT NULL,
			 `request_type` TINYINT NOT NULL,
        	PRIMARY KEY (`request_seller_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);

		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_request_product` (
             `request_product_id` int(11) NOT NULL AUTO_INCREMENT,
			 `request_id` int(11) NOT NULL,
             `product_id` int(11) NOT NULL,
			 `request_type` TINYINT NOT NULL,
        	PRIMARY KEY (`request_product_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);

		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_request` (
             `request_id` int(11) NOT NULL AUTO_INCREMENT,
			 `request_status` TINYINT NOT NULL,
			 `resolution_type` TINYINT DEFAULT NULL,
             `processed_by` int(11) DEFAULT NULL,
			 `date_created` DATETIME NOT NULL,
			 `date_processed` DATETIME DEFAULT NULL,
             `message_created` TEXT NOT NULL DEFAULT '',
             `message_processed` TEXT NOT NULL DEFAULT '',
        	PRIMARY KEY (`request_id`)) default CHARSET=utf8";
        
		// ms_seller_group - table with seller groups
        $this->db->query($sql);
*/
		
		
		// ms_criteria - criterias table
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_criteria` (
             `criteria_id` int(11) NOT NULL AUTO_INCREMENT,
			 `criteria_type` TINYINT NOT NULL,
			 `range_id` int(11) NOT NULL,
        	PRIMARY KEY (`criteria_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		// ms_range_int - int criteria range table
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_range_int` (
             `range_id` int(11) NOT NULL AUTO_INCREMENT,
			 `from` int(11) NOT NULL,
			 `to` int(11) NOT NULL,
        	PRIMARY KEY (`range_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		// ms_range_decimal - decimal criteria range table
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_range_decimal` (
             `range_id` int(11) NOT NULL AUTO_INCREMENT,
			 `from` DECIMAL(15,4) NOT NULL,
			 `to` DECIMAL(15,4) NOT NULL,
        	PRIMARY KEY (`range_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		// ms_range_periodic - periodic criteria range table
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_range_date` (
             `range_id` int(11) NOT NULL AUTO_INCREMENT,
			 `from` DATETIME,
			 `to` DATETIME NOT NULL,
        	PRIMARY KEY (`range_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		// ms_seller_group_criteria - table, which connects concrete commissions for criterias in the seller groups
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_seller_group_criteria` (
             `seller_group_criteria_id` int(11) NOT NULL AUTO_INCREMENT,
			 `commission_id` int(11) NOT NULL,
			 `criteria_id` int(11) NOT NULL,
        	PRIMARY KEY (`seller_group_criteria_id`)) default CHARSET=utf8";
        
        $this->db->query($sql);
		
		
		// new attributes
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_attribute` (
			`attribute_id` int(11) NOT NULL AUTO_INCREMENT,
			`attribute_type` int(11) NOT NULL,
			`number` TINYINT NOT NULL DEFAULT 0,
			`multilang` TINYINT NOT NULL DEFAULT 0,
			`required` TINYINT NOT NULL DEFAULT 0,
			`enabled` TINYINT NOT NULL DEFAULT 1,
			`sort_order` int(3) NOT NULL,
			PRIMARY KEY (`attribute_id`)
			) DEFAULT CHARSET=utf8";
		$this->db->query($sql);

		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_attribute_description` (
			 `attribute_id` int(11) NOT NULL,
			 `language_id` int(11) NOT NULL,
			 `name` varchar(128) NOT NULL,
			 `description` TEXT NOT NULL DEFAULT '',
			 PRIMARY KEY (`attribute_id`,`language_id`)
			) DEFAULT CHARSET=utf8";
		$this->db->query($sql);

		$sql = " 
			CREATE TABLE `" . DB_PREFIX . "ms_attribute_value` (
			 `attribute_value_id` int(11) NOT NULL AUTO_INCREMENT,
			 `attribute_id` int(11) NOT NULL,
			 `image` varchar(255) NOT NULL,
			 `sort_order` int(3) NOT NULL,
			 PRIMARY KEY (`attribute_value_id`)
			) DEFAULT CHARSET=utf8";
		$this->db->query($sql);
		
		$sql = "
			CREATE TABLE `" . DB_PREFIX . "ms_attribute_value_description` (
			 `attribute_value_id` int(11) NOT NULL,
			 `language_id` int(11) NOT NULL,
			 `attribute_id` int(11) NOT NULL,
			 `name` varchar(128) NOT NULL,
			 PRIMARY KEY (`attribute_value_id`,`language_id`)
			) DEFAULT CHARSET=utf8";		
		$this->db->query($sql);
		
		$sql = "
			CREATE TABLE " . DB_PREFIX . "ms_product_attribute (
			 `product_id` int(11) NOT NULL,
			 `attribute_id` int(11) NOT NULL,
			 `attribute_value_id` int(11) NOT NULL,
        	PRIMARY KEY (`product_id`,`attribute_id`,`attribute_value_id`)) default CHARSET=utf8";
        $this->db->query($sql);		
		
	}
	
	public function addData() {
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_commission () VALUES()");
		$commission_id = $this->db->getLastId();
		
		$rate_type = MsCommission::RATE_SALE;
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_commission_rate (rate_type, commission_id, flat, percent) VALUES($rate_type, $commission_id, 0,0)");
        $rate_id = $this->db->getLastId();
        
		$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_group (commission_id) VALUES($commission_id)");
        $seller_group_id = $this->db->getLastId();
        
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		foreach ($languages as $code => $language) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ms_seller_group_description SET seller_group_id = '" . (int)$seller_group_id . "', language_id = '" . (int)$language['language_id'] . "', name = 'Default', description = 'Default seller group'");
		}
	}
	
	// ToDo: drop databases
	public function dropTable() {
		$sql = "DROP TABLE IF EXISTS
				`" . DB_PREFIX . "ms_product`,
				`" . DB_PREFIX . "ms_seller`,
				`" . DB_PREFIX . "ms_order_product_data`,
				`" . DB_PREFIX . "ms_withdrawal`,
				`" . DB_PREFIX . "ms_comments`,
				`" . DB_PREFIX . "ms_balance`,
				`" . DB_PREFIX . "ms_seller_group`,
				`" . DB_PREFIX . "ms_seller_group_description`,
				`" . DB_PREFIX . "ms_seller_group_criteria`,
				`" . DB_PREFIX . "ms_commission_rate`,
				`" . DB_PREFIX . "ms_commission`,
				`" . DB_PREFIX . "ms_criteria`,
				`" . DB_PREFIX . "ms_range_int`,
				`" . DB_PREFIX . "ms_range_decimal`,
				`" . DB_PREFIX . "ms_range_date`,
				`" . DB_PREFIX . "ms_attribute`,
				`" . DB_PREFIX . "ms_attribute_description`,
				`" . DB_PREFIX . "ms_attribute_value`,
				`" . DB_PREFIX . "ms_attribute_value_description`,
				`" . DB_PREFIX . "ms_product_attribute`";
				
		$this->db->query($sql);
	}
}