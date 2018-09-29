<?php
class ControllerExtensionFeedLicorneGoogleSitemap extends Controller {

	const
		CONFIG_STATUS = 'feed_licorne_google_sitemap_status',
		CONFIG_MAX_EXEC_TIME_NAME = 'feed_licorne_google_sitemap_max_execution_time',
		CONFIG_COL_CAT_PROD_NAME = 'feed_licorne_google_sitemap_collect_category_products',
		CONFIG_COL_MAN_PROD_NAME = 'feed_licorne_google_sitemap_collect_manufacturer_products',
		CONFIG_USE_FILESYSTEM_CACHE_NAME = 'feed_licorne_google_sitemap_use_filesystem_cache',
		CONFIG_SITEMAP_CACHE_PATH = 'feed_licorne_google_sitemap_sitemap_cache_path',
		CONFIG_TOKEN_NAME = 'feed_licorne_google_sitemap_token',
		CONFIG_ADD_IMAGE_URL = 'feed_licorne_google_sitemap_add_image_url';

	private $token;

	public function index()
	{
		if ($this->config->get(self::CONFIG_STATUS))
		{
			$output = $this->config->get(self::CONFIG_USE_FILESYSTEM_CACHE_NAME)
				? $this->getCachedSitemap($this->getSitemapStoragePath()) : false;

			$this->token = $this->config->get(self::CONFIG_TOKEN_NAME);

			$this->response->addHeader('Content-type: text/xml');
			$this->response->setOutput($output ? : $this->generate());
		}
	}

	public function generate() {
		$token = $this->token ?: $this->request->get['token'];

		if ($this->config->get(self::CONFIG_STATUS)
				&& strcmp($token, $this->config->get(self::CONFIG_TOKEN_NAME)) == 0)
		{
			$maxExecTime = $this->config->get(self::CONFIG_MAX_EXEC_TIME_NAME);
			ini_set('max_execution_time', ($maxExecTime ?: 1) * 3600);

			$this->load->model('extension/feed/licorne_google_sitemap');

			$configTheme = $this->config->get('config_theme');
			$imageWidth = $this->config->get("theme_{$configTheme}_image_thumb_width");
			$imageHeight = $this->config->get("theme_{$configTheme}_image_thumb_height");

			$data = [];
			$data['generate_image_data'] = $this->config->get(self::CONFIG_ADD_IMAGE_URL);
			$data['products'] = $this->getProducts($imageWidth, $imageHeight, $data['generate_image_data']);
			$data['manufacturers'] = $this->getManufacturers(
					$this->config->get(self::CONFIG_COL_MAN_PROD_NAME)
				);

			$categories = [];
			$data['categories'] = $this->getCategories(0,'',$categories,
					$this->config->get(self::CONFIG_COL_CAT_PROD_NAME)
				);
			$data['informations'] = $this->getInformations();

			$output = $this->load->view('extension/feed/licorne_google_sitemap', $data);

			if ($this->config->get(self::CONFIG_USE_FILESYSTEM_CACHE_NAME))
				file_put_contents($this->getSitemapStoragePath(), $output);

			return $output;
		}

		trigger_error('Invalid token string', E_USER_WARNING);
	}

	private function getSitemapStoragePath()
	{
		return $this->config->get(self::CONFIG_SITEMAP_CACHE_PATH);
	}

	protected function getCachedSitemap($path)
	{
		if (file_exists($path))
		{
			if ($this->config->get(self::CONFIG_USE_FILESYSTEM_CACHE_NAME))
			{
				return file_get_contents($path);
			}

			unlink($path);
		}

		return false;
	}

	protected function getProducts($imageWidth, $imageHeight, $generateImage = false)
	{
		$this->load->model('tool/image');
		$products = $this->model_extension_feed_licorne_google_sitemap
			->getProducts();

		foreach ($products as &$product)
		{
			$product['url'] =
				$this->url->link('product/product', ['product_id' => $product['product_id']], true);
			$product['date_modified'] = strtotime($product['date_modified']);

			if ($generateImage && $product['image'])
			{
				$product['image_url'] = $this->model_tool_image->resize(
						$product['image'],
						$imageWidth,
						$imageHeight
					);
			}
		}

		return $products;
	}

	protected function getManufacturers($pinProducts = false)
	{
		$manufacturers = $this->model_extension_feed_licorne_google_sitemap
			->getManufacturers();

		foreach($manufacturers as &$manufacturer)
		{
			$manufacturer['url'] =
				$this->url->link(
					'product/manufacturer/info',
					['manufacturer_id' => $manufacturer['manufacturer_id']], true
				);

			if ($pinProducts)
			{
				$manufacturer['products'] =
					$this->model_extension_feed_licorne_google_sitemap->getProducts(
						['manufacturer_id' => $manufacturer['manufacturer_id']]
					);

				foreach ($manufacturer['products'] as &$product)
				{
					$params = [
						'manufacturer_id' => $manufacturer['manufacturer_id'],
						'product_id' => $product['product_id']
					];
					$product['url'] =
						$this->url->link('product/product', $params, true);
				}
			}
		}

		return $manufacturers;
	}

	protected function getCategories($parentId, $currentPath = '', array &$cats = [], $pinProducts = false)
	{
		$this->load->model('catalog/category');

		$categories = $this->model_catalog_category->getCategories($parentId);

		foreach ($categories as $category)
		{
			$newPath = !$currentPath
				? $category['category_id'] : $currentPath . '_' . $category['category_id'];

			$cats[$newPath] = $category;
			$cats[$newPath]['url'] = $this->url->link('product/category', 'path=' . $newPath, true);

			if ($pinProducts)
			{
				$cats[$newPath]['products'] =
					$this->model_extension_feed_licorne_google_sitemap->getProducts(
						['category_id' => $category['category_id']]
					);

				foreach ($cats[$newPath]['products'] as &$product)
				{
					$params = ['path' => $newPath, 'product_id' => $product['product_id']];
					$product['url'] = $this->url->link('product/product', $params, true);
				}
			}

			$this->getCategories($category['category_id'], $newPath, $cats, $pinProducts);
		}

		return $cats;
	}

	protected function getInformations()
	{
		$this->load->model('catalog/information');

		$informations = $this->model_catalog_information->getInformations();

		foreach ($informations as &$information)
		{
			$params = ['information_id' => $information['information_id']];
			$information['url'] = $this->url->link('information/information', $params, true);
		}

		return $informations;
	}
}
