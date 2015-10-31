<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */


namespace HeloStore\ADLS;


use Tygh\Addons\SchemesManager;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Tygh;

if (!class_exists('HeloStore\\ADLS\\LicenseClient')) :


class LicenseClient
{
	const CONTEXT_INSTALL = 'install';
	const CONTEXT_UNINSTALL = 'uninstall';
	const CONTEXT_AUTHENTICATION = 'authentication';
	const CONTEXT_ACTIVATE = 'activate';
	const CONTEXT_DEACTIVATE = 'deactivate';
	const API_ENDPOINT = 'local.helostore.com/index.php?dispatch=adls_api';

	const ERROR_INVALID_TOKEN = 400;
	const ERROR_MISSING_TOKEN = 401;


	public $caller;
	protected $context;
	protected $errors = array();
	protected $messages = array();
	protected $tries = 0;
	protected $maxTries = 3;
	protected $data = array();
	protected $settings = array();

	public function __construct($context = '', $localRequest = array())
	{
//		if (defined('ADLS_SKIP_NOTIFICATION')) {
//			return;
//		}

		$trace = debug_backtrace(false);
		$this->caller = array_shift($trace);
		$this->context = $context;
		$this->setData();

		if (!empty($localRequest)) {
			$this->handleLocalRequest($localRequest);
		} else if (!empty($context)) {
			if ($context == LicenseClient::CONTEXT_ACTIVATE) {
				$this->maybeActivateLicense($this->data['product']['name']);
			} else {
				$this->request($context, $this->data);
			}
		}
	}

	public function request($context, $data)
	{
		if ($context != LicenseClient::CONTEXT_AUTHENTICATION) {
			if ($this->refreshToken()) {
				$data['token'] = fn_get_storage_data('helostore_token');
			}
		}

		$this->messages[] = 'Requesting: '.$context;
		$protocol = (defined('DEVELOPMENT') ? 'http' : 'https');
		$url = $protocol . '://' . self::API_ENDPOINT . '.' . $context;
		$data['context'] = $context;

		$response = Http::get($url, $data);

		$_tmp = json_decode($response, true);
		if (is_array($_tmp)) {
			$response = $_tmp;
		}
		if (!empty($response) && !empty($response['code']) && $response['code'] == LicenseClient::ERROR_INVALID_TOKEN) {
			fn_set_storage_data('helostore_token', '');
		}

		return $response;
	}
	public function getData()
	{
		return $this->data;
	}

	public function setData($addonName = null)
	{
		$data = array();
		$data['server'] = array(
			'hostname' => $_SERVER['SERVER_NAME'],
			'ip' => $_SERVER['SERVER_ADDR'],
			'port' => $_SERVER['SERVER_PORT'],
		);
		$data['platform'] = array(
			'name' => PRODUCT_NAME,
			'version' => PRODUCT_VERSION,
			'edition' => PRODUCT_EDITION,
		);

		if (empty($addonName)) {
			$addonName = self::inferAddonName($this->caller);
		}

		if (!empty($addonName)) {
			$settings = $this->setSettings($addonName);

			$data['product'] = array(
				'name' => $addonName,
				'license' => isset($settings['license']) ? $settings['license'] : '',
				'version' => isset($settings['version']) ? $settings['version'] : '',
				'status' => isset($settings['status']) ? $settings['status'] : '',
			);
			$data['email'] = $settings['email'];
		}
		$this->data = $data;
	}

	public function setSettings($addonName)
	{
		$settings = Registry::get('addons.' . $addonName);
		$settings = is_array($settings) ? $settings : array();
		$scheme = SchemesManager::getScheme($addonName);
		if (!empty($scheme)) {
			if (method_exists($scheme, 'getVersion')) {
				$settings['version'] = $scheme->getVersion();
			}
		}
		$this->settings = $settings;

		return $settings;
	}

	public function getErrors()
	{
		return $this->errors;
	}
	private function refreshToken()
	{

		if ($this->tries >= $this->maxTries) {
			return false;
		}
		$this->tries++;
		$this->messages[] = 'Refreshing token';
		$data = $this->getData();
		if (!empty($this->settings)) {
			$data['password'] = $this->settings['password'];
		}

		$response = $this->request(LicenseClient::CONTEXT_AUTHENTICATION, $data);
		if (!empty($response['token'])) {
			fn_set_storage_data('helostore_token', $response['token']);
			$this->messages[] = 'Received new token';
			$this->tries = 0;
			return true;
		}

		if (!empty($response['message'])) {
			$this->errors[] = $response['message'];
		}
		sleep(5);

		return false;
	}

	public function handleLocalRequest($request)
	{
		$requestedAddon = !empty($request['addon']) ? $request['addon'] : '';
		$addonName = !empty($this->data['product']['name']) ? $this->data['product']['name'] : '';

		if (empty($addonName) || empty($requestedAddon)) {
			return;
		}

		if ($addonName != $requestedAddon) {
			return;
		}

		$this->maybeActivateLicense($addonName, true);
	}

	public function maybeActivateLicense($addonName, $conditioned = false)
	{
		$attempt = false;
		$settings = Settings::instance()->getValues($addonName, Settings::ADDON_SECTION, false);
		if ($conditioned) {
			$previousSettings = Registry::get('addons.' . $addonName);
			if ($previousSettings['license'] != $settings['license']) {
				$attempt = true;
			}
		} else {
			$attempt = true;
		}
		if ($attempt) {
			return $this->activateLicense($addonName, $settings);
		}

		return false;
	}
	public function activateLicense($addonName, $settings)
	{
		$this->setData($addonName);
		$data = $this->getData();
		$data['product']['license'] = $settings['license'];
		$response = $this->request(LicenseClient::CONTEXT_ACTIVATE, $data);
		$code = isset($response['code']) ? intval($response['code']) : -1;
		$message = !empty($response['message']) ? $response['message'] : '';

		if (empty($message)) {
			$message = json_encode($response);
			fn_set_notification('E', __('unknown_error'), $message);
		} else {
			if ($code == 0 || $code == 200) {
				fn_set_notification('S', __('well_done'), $message);
			} else {
				fn_set_notification('E', __('error'), $message . ' (' . $code . ')');
			}
		}
//		define('ADLS_SKIP_NOTIFICATION', true);

		return ($code == 0 || $code == 200);

	}
	public function deactivateLicense($addonName)
	{
		$this->setData($addonName);
		$data = $this->getData();
		$data['product']['license'] = $this->settings['license'];
		$response = $this->request(LicenseClient::CONTEXT_DEACTIVATE, $data);
		$code = isset($response['code']) ? intval($response['code']) : -1;
		$message = !empty($response['message']) ? $response['message'] : '';

		if ($code == 0 || $code == 200) {
			if ($message) {
				fn_set_notification('S', __('well_done'), $message);
			}
		} else {
			fn_set_notification('E', __('error'), $message . ' (' . $code . ')');
		}
//		define('ADLS_SKIP_NOTIFICATION', true);

		return ($code == 0 || $code == 200);

	}

	public static function inferAddonName($caller)
	{
		$addonName = '';
		if (!empty($caller) && !empty($caller['file'])) {
			// ughhh, workaround to handle symlinks!
			$callerPath = str_replace(array('\\', '/'), '/', $caller['file']);
			$relativePath = substr($callerPath, strrpos($callerPath, '/app/') + 1);
			$dirs = explode('/', $relativePath);
			array_shift($dirs);
			array_shift($dirs);
			$addonName = array_shift($dirs);
		}

		return $addonName;
	}
	public static function activate()
	{
		$trace = debug_backtrace(false);
		$caller = array_shift($trace);
		$addonName = self::inferAddonName($caller);

		$client = new LicenseClient();
		$client->setSettings($addonName);
		return $client->maybeActivateLicense($addonName);
	}
	public static function deactivate()
	{
		$trace = debug_backtrace(false);
		$caller = array_shift($trace);
		$addonName = self::inferAddonName($caller);

		$client = new LicenseClient();
		$client->setSettings($addonName);
		return $client->deactivateLicense($addonName);
	}

}

endif;