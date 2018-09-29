<?php
class ControllerExtensionFeedLicorneGoogleSitemap extends Controller {

	const CONFIG_PREFIX = 'feed_licorne_google_sitemap';

	private $error = array();

	public function index() {
		$this->load->language('extension/feed/licorne_google_sitemap');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting(self::CONFIG_PREFIX, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/licorne_google_sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/feed/licorne_google_sitemap', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true);

		$data = array_merge($data, $this->collectInputData(self::CONFIG_PREFIX));

		$data['data_feed'] = HTTP_CATALOG . 'index.php?route=extension/feed/licorne_google_sitemap';
		$data['clear_cached_sitemap_feed_url'] =
			$this->url->link(
					'extension/feed/licorne_google_sitemap/clearCachedSitemap',
					[
						'user_token' => $this->session->data['user_token'],
					], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/licorne_google_sitemap', $data));
	}

	public function clearCachedSitemap()
	{
		$filename = $this->config->get('feed_licorne_google_sitemap_sitemap_cache_path');

		if (file_exists($filename))
			unlink($filename);
	}

	private function collectInputData($configPrefix)
	{
		$data = [];

		if (isset($this->request->post["{$configPrefix}_status"])) {
			$data["{$configPrefix}_status"] = $this->request->post["{$configPrefix}_status"];
		} else {
			$data["{$configPrefix}_status"] = $this->config->get("{$configPrefix}_status");
		}

		if (isset($this->request->post["{$configPrefix}_add_image_url"])) {
			$data["{$configPrefix}_add_image_url"] =
			$this->request->post["{$configPrefix}_add_image_url"];
		} else {
			$data["{$configPrefix}_add_image_url"] =
			$this->config->get("{$configPrefix}_add_image_url");
		}

		if (isset($this->request->post["{$configPrefix}_collect_manufacturer_products"])) {
			$data["{$configPrefix}_collect_manufacturer_products"] =
				$this->request->post["{$configPrefix}_collect_manufacturer_products"];
		} else {
			$data["{$configPrefix}_collect_manufacturer_products"] =
				$this->config->get("{$configPrefix}_collect_manufacturer_products");
		}

		if (isset($this->request->post["{$configPrefix}_collect_category_products"])) {
			$data["{$configPrefix}_collect_category_products"] =
				$this->request->post["{$configPrefix}_collect_category_products"];
		} else {
			$data["{$configPrefix}_collect_category_products"] =
				$this->config->get("{$configPrefix}_collect_category_products");
		}

		if (isset($this->request->post["{$configPrefix}_use_filesystem_cache"])) {
			$data["{$configPrefix}_use_filesystem_cache"] =
			$this->request->post["{$configPrefix}_use_filesystem_cache"];
		} else {
			$data["{$configPrefix}_use_filesystem_cache"] =
			$this->config->get("{$configPrefix}_use_filesystem_cache");
		}

		if (isset($this->request->post["{$configPrefix}_max_execution_time"])) {
			$data["{$configPrefix}_max_execution_time"] =
			$this->request->post["{$configPrefix}_max_execution_time"];
		} else {
			$data["{$configPrefix}_max_execution_time"] =
			$this->config->get("{$configPrefix}_max_execution_time");
		}

		if (isset($this->request->post["{$configPrefix}_token"])) {
			$data["{$configPrefix}_token"] =
			$this->request->post["{$configPrefix}_token"];
		} else {
			$data["{$configPrefix}_token"] =
			$this->config->get("{$configPrefix}_token")
				?: substr(str_shuffle(MD5(microtime())), 0, 30);
		}

		$data["{$configPrefix}_sitemap_cache_path"] =
			realpath(DIR_APPLICATION . '..'). DIRECTORY_SEPARATOR . 'sitemap.xml';

		return $data;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/feed/licorne_google_sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function unistall()
	{
		$this->load->model('setting/setting');

		$this->model_setting_setting->deleteSetting(self::CONFIG_PREFIX);
	}
}