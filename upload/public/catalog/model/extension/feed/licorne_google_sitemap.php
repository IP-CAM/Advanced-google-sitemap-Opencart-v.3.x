<?php
class ModelExtensionFeedLicorneGoogleSitemap extends Model {
    public function getProducts($filter = []) {
    	$where = "WHERE
					p.status = 1
					AND pts.store_id = " . (int)$this->config->get('config_store_id') . "
					AND pd.language_id = ". (int)$this->config->get('config_language_id');

    	foreach($filter as $name => $value)
    	{
    		$where .= " AND {$name} = '" . $this->db->escape($value) . "'";
    	}

    	$sql = "SELECT p.product_id, p.image, pd.name, p.date_modified
				FROM " . DB_PREFIX ."product p
				JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id
				JOIN " . DB_PREFIX . "product_to_store pts ON pts.product_id = p.product_id
				JOIN " . DB_PREFIX . "product_to_category ptc ON ptc.product_id = p.product_id
 				{$where}";

		$query = $this->db->query($sql);

		return $query->rows;
    }

	public function getManufacturers() {
		$sql = "SELECT m.manufacturer_id, m.name
				FROM " . DB_PREFIX ."manufacturer m
				JOIN " . DB_PREFIX . "manufacturer_to_store mts ON m.manufacturer_id = mts.manufacturer_id
				WHERE mts.store_id = " . (int)$this->config->get('config_store_id') . "
				ORDER BY m.name";
		$query = $this->db->query($sql);

		return $query->rows;
    }
}
