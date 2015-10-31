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
	const API_ENDPOINT = 'local.helostore.com/index.php?dispatch=adls_api';

	const ERROR_INVALID_TOKEN = 400;


	protected $context;
	protected $caller;
	protected $errors = array();
	protected $messages = array();
	protected $tries = 0;
	protected $maxTries = 3;
	protected $data = array();
	protected $settings = array();

	public function __construct($context = '', $localRequest = array())
	{
		$trace = debug_backtrace(false);
		$this->caller = array_shift($trace);
		$this->context = $context;
		$this->data = $this->getData();
		if (!empty($context)) {
			$this->request($context, $this->data);
		}
		if (!empty($localRequest)) {
			$this->handleLocalRequest($localRequest);
		}
	}

	public function request($context, $data)
	{
		$token = fn_get_storage_data('helostore_token');

		if ($context != LicenseClient::CONTEXT_AUTHENTICATION) {
			if (empty($token) || strlen($token) < 10) {
				if ($this->refreshToken()) {
					$data['token'] = fn_get_storage_data('helostore_token');
				}
			} else {
				$data['token'] = $token;
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
	public function getData($addonName = null)
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
			$caller = $this->caller;
			if (!empty($caller) && !empty($caller['file'])) {
				// ughhh, workaround to handle symlinks!
				$callerPath = str_replace(array('\\', '/'), '/', $caller['file']);
				$relativePath = substr($callerPath, strrpos($callerPath, '/app/') + 1);
				$dirs = explode('/', $relativePath);
				array_shift($dirs);
				array_shift($dirs);
				$addonName = array_shift($dirs);
			}
		}

		if (!empty($addonName)) {
			$settings = Registry::get('addons.' . $addonName);
			$settings = is_array($settings) ? $settings : array();
			$this->settings = $settings;
			$scheme = SchemesManager::getScheme($addonName);
			if (!empty($scheme)) {
				if (method_exists($scheme, 'getVersion')) {
					$settings['version'] = $scheme->getVersion();
				}
			}
			$data['product'] = array(
				'name' => $addonName,
				'license' => isset($settings['license']) ? $settings['license'] : '',
				'version' => isset($settings['version']) ? $settings['version'] : '',
				'status' => isset($settings['status']) ? $settings['status'] : '',
			);
			$data['email'] = $settings['email'];
		}

		return $data;
	}

	public function getErrors()
	{
		return $this->errors;
	}
	private function refreshToken()
	{
		aa('Refreshing');
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

		$previousSettings = Registry::get('addons.' . $addonName);
		$settings = Settings::instance()->getValues($addonName, Settings::ADDON_SECTION, false);
		if (1 or $previousSettings['license'] != $settings['license']) {
			$this->activate($addonName, $settings);
		}


//
//
//		$is_snapshot_correct = fn_check_addon_snapshot($_REQUEST['id']);
//
//		if (!$is_snapshot_correct) {
//			$status = false;
//
//		} else {
//			$status = fn_update_addon_status($_REQUEST['id'], $_REQUEST['status']);
//		}
//
//		if ($status !== true) {
//			Tygh::$app['ajax']->assign('return_status', $status);
//		}
//		Registry::clearCachedKeyValues();
	}

	public function activate($addonName, $settings)
	{
		$data = $this->getData($addonName);
		$data['product']['license'] = $settings['license'];
		$response = $this->request(LicenseClient::CONTEXT_ACTIVATE, $data);
		aa($response,1);
		$status = 'D';
	}
}

endif;