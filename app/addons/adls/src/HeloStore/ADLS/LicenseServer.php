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

class LicenseServer
{
	public function __construct()
	{
	}

	public function handleRequest($request)
	{
		$response = array(
			'code' => LicenseClient::CODE_ERROR_ALIEN,
			'message' => '99 problems',
		);

		$context = !empty($request['context']) ? $request['context'] : '';
		if ($context == LicenseClient::CONTEXT_UPDATE_CHECK) {
			$response = $this->checkUpdates($request);
		} else if ($context == LicenseClient::CONTEXT_AUTHENTICATION) {
			$response = $this->authenticate($request);
		} else if ($this->authorize($request)) {
			if ($context == LicenseClient::CONTEXT_ACTIVATE) {
				$response = $this->activate($request);
			} else if ($context == LicenseClient::CONTEXT_DEACTIVATE || $context == LicenseClient::CONTEXT_UNINSTALL) {
				$response = $this->deactivate($request);
			} else if ($context == LicenseClient::CONTEXT_UPDATE_REQUEST) {
				$response = $this->updateRequest($request);
			} else if ($context == LicenseClient::CONTEXT_UPDATE_DOWNLOAD) {
				$response = $this->downloadRequest($request);
			}
		} else {
			$response = array(
				'code' => LicenseClient::CODE_SUCCESS,
				'message' => '',
			);
			// log installs/uninstalls/everything
		}

		return $response;
	}

	public function activate($request)
	{
		$vars = $this->requireRequestVariables($request, array('product.license', 'server.hostname','email'));
		$response = array();
		$manager = LicenseManager::instance();

		$license = $manager->getLicenseByKey($vars['product.license']);

		if (empty($license)) {
			throw new \Exception('Invalid license or domain', LicenseClient::CODE_ERROR_INVALID_LICENSE_OR_DOMAIN);
		}

		if ($manager->isActivateLicense($license['license_id'], $vars['server.hostname'])) {
			$response['code'] = LicenseClient::CODE_SUCCESS;
			$response['message'] = 'License is already activated for specified domain.';
		} else if (!$manager->activateLicense($license['license_id'], $vars['server.hostname'])) {
			throw new \Exception('Unable to activate license for specified domain', LicenseClient::CODE_ERROR_INVALID_TOKEN);
		} else {
			$response['code'] = LicenseClient::CODE_SUCCESS;
			$response['message'] = 'Your license is now <b>active</b>!';
		}

		return $response;
	}


	public function deactivate($request)
	{
		$vars = $this->requireRequestVariables($request, array('product.license', 'server.hostname' ,'email'));
		$manager = LicenseManager::instance();
		$license = $manager->getLicenseByKey($vars['product.license']);

		$response = array();
		$response['code'] = LicenseClient::CODE_SUCCESS;
		$response['message'] = '';
		if (empty($license)) {
			return $response;
		}

		if (!$manager->isActivateLicense($license['license_id'], $vars['server.hostname'])) {
			return $response;
		}
		$manager->deactivateLicense($license['license_id'], $vars['server.hostname']);

		return $response;
	}

	public function authorize($request)
	{
		$vars = $this->requireRequestVariables($request, array(
			'email',
			'token',
			'server.hostname'
		));

		$userInfo = db_get_row('SELECT user_id, email, password, last_login FROM ?:users WHERE email = ?s', $vars['email'], 'C');
		if (empty($userInfo)) {
			throw new \Exception('I cannot find you in my records. Please use your customer email at HELOstore.', LicenseClient::CODE_ERROR_INVALID_CUSTOMER_EMAIL);
		}
		$challengeToken = $this->bakeToken($userInfo['user_id'], $userInfo['email'], $userInfo['password'], $userInfo['last_login']);
		if ($challengeToken != $vars['token']) {
			throw new \Exception('Invalid or expired token', LicenseClient::CODE_ERROR_INVALID_TOKEN);
		}

		return true;
	}

	public function requireRequestVariables($request, $keys)
	{
		// if main credentials are missing, seek them within products settings
		if (!empty($request['products']) && (empty($request['email']) || empty($request['password']))) {
			foreach ($request['products'] as $product) {
				if (!empty($product['email']) && !empty($product['password'])) {
					$request['email'] = $product['email'];
					$request['password'] = $product['password'];
					break;
				}
			}
		}
		ws_log_file($request, 'var/log/debug.log');

		$vars = array();
		foreach ($keys as $key) {
			$a = null;
			$b = null;
			if (strstr($key, '.') !== false) {
				$key = explode('.', $key);
				$a = $key[0];
				$b = $key[1];
			} else {
				$a = $key;
			}

			$pass = true;
			if ($a !== null) {
				if (empty($request[$a])) {
					$pass = false;
				} else {
					if ($b !== null) {
						if (empty($request[$a][$b])) {
							$pass = false;
						}
					}
				}
			}
			$key = $b === null ? $a : $b;
			$vk = $b === null ? $a : "$a.$b";
			if ($pass) {
				$vars[$vk] = $b === null ? $request[$a] : $request[$a][$b];
			} else {
				static $codes = array(
					'email' => LicenseClient::CODE_ERROR_MISSING_EMAIL,
					'password' => LicenseClient::CODE_ERROR_MISSING_PASSWORD,
					'product.license' => LicenseClient::CODE_ERROR_MISSING_LICENSE,
					'server.hostname' => LicenseClient::CODE_ERROR_MISSING_DOMAIN,
					'token' => LicenseClient::CODE_ERROR_MISSING_TOKEN
				);
				$code = isset($codes[$key]) ? $codes[$key] : LicenseClient::CODE_ERROR_ALIEN;
				$codeName = LicenseClient::getCodeName($code);

				throw new \Exception(__($codeName), $code);
			}
		}

		return $vars;
	}
	public function authenticate($request)
	{
		$vars = $this->requireRequestVariables($request, array('email', 'password'));

		$userInfo = db_get_row('SELECT user_id, email, password, salt, last_login FROM ?:users WHERE email = ?s LIMIT 0,1', $vars['email']);
		if (empty($userInfo)) {
			throw new \Exception('Your email/password combination is incorrect, sorry mate.', LicenseClient::CODE_ERROR_INVALID_CREDENTIALS_COMBINATION);
		}

		$challengeHash = fn_generate_salted_password($vars['password'], $userInfo['salt']);
		if ($challengeHash != $userInfo['password']) {
			throw new \Exception('Your email/password combination is incorrect, sorry matey.', LicenseClient::CODE_ERROR_MISMATCH_CREDENTIALS_COMBINATION);
		}

		$token = $this->bakeToken($userInfo['user_id'], $userInfo['email'], $userInfo['password'], $userInfo['last_login']);
		$response = array(
			'code' => LicenseClient::CODE_SUCCESS,
			'token' => $token,
		);

		return $response;

	}

	public function bakeToken($userId, $email, $challengeHash, $lastTokenDate)
	{
		$expirationTime = 10;
		$expirationDate = $lastTokenDate + $expirationTime;

		// token time expired, update new expiration time (implicitly a new token will be baked)
		if (TIME > $expirationDate) {
			$lastTokenDate = TIME;
			db_query('UPDATE ?:users SET last_login = ?s WHERE user_id = ?i', $lastTokenDate, $userId);
		}

		$token = hash('sha512', $email . $challengeHash . $lastTokenDate);

		return $token;
	}

	public function checkUpdates($request)
	{
		$response = array(
			'code' => LicenseClient::CODE_SUCCESS,
		);
		if (empty($request) || empty($request['products'])) {
			return $response;
		}
		$customerProducts = $request['products'];
		$productManager = ProductManager::instance();
		$storeProducts = $productManager->getStoreProducts();
		$response['updates'] = $productManager->checkUpdates($customerProducts, $storeProducts);

		return $response;
	}

	public function updateRequest($request)
	{
		$response = array(
			'code' => LicenseClient::CODE_SUCCESS,
		);
		if (empty($request) || empty($request['products'])) {
			return $response;
		}
		$customerProducts = $request['products'];
		$productManager = ProductManager::instance();

		$productManager->validateUpdateRequest($customerProducts);

		$storeProducts = $productManager->getStoreProducts();
		$response['updates'] = $productManager->checkUpdates($customerProducts, $storeProducts, true);

		return $response;
	}

	public function downloadRequest($request)
	{
		$response = array(
			'code' => LicenseClient::CODE_ERROR_ALIEN,
		);
		if (empty($request) || empty($request['product'])) {
			return $response;
		}
		$customerProduct = $request['product'];
		$path = DIR_ROOT . '/var/releases/autoimage_lite-v0.1.2.zip';

		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 'Off');
		if (!is_file($path)) {
			$response['code'] = LicenseClient::CODE_ERROR_UPDATE_INVALID_REMOTE_PATH;
			return $response;
		}
		$size  = filesize($path);
		$file = @fopen($path,"rb");
		if (empty($file)) {
			$response['code'] = LicenseClient::CODE_ERROR_UPDATE_FAILED_REMOTE_FILE_OPEN;
			return $response;
		}
		$pathInfo = pathinfo($path);
		$filename = $pathInfo['filename'] . '.' . $pathInfo['extension'];
		$isAttachment = false;

		// set the headers, prevent caching
		header("Pragma: public");
		header("Expires: -1");
		header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");

		// set appropriate headers for attachment or streamed file
		if ($isAttachment) {
			header("Content-Disposition: attachment; filename=\"$filename\"");
		} else {
			header('Content-Disposition: inline;');
		}
		header("Content-Type: application/zip");

		//check if http_range is sent by browser (or download manager)
		if (isset($_SERVER['HTTP_RANGE'])) {
			list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			if ($size_unit == 'bytes') {
				//multiple ranges could be specified at the same time, but for simplicity only serve the first range
				//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
				list($range, $extra_ranges) = explode(',', $range_orig, 2);
			} else {
				$range = '';
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				exit;
			}
		} else {
			$range = '';
		}

		//figure out download piece from range (if set)
		list($seek_start, $seek_end) = explode('-', $range, 2);

		//set start and end based on range (if set), else set defaults
		//also check for invalid ranges.
		$seek_end   = (empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)),($size - 1));
		$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

		//Only send partial content header if downloading a piece of the file (IE workaround)
		if ($seek_start > 0 || $seek_end < ($size - 1))
		{
			header('HTTP/1.1 206 Partial Content');
			header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$size);
			header('Content-Length: '.($seek_end - $seek_start + 1));
		}
		else
			header("Content-Length: $size");

		header('Accept-Ranges: bytes');

		set_time_limit(0);
		fseek($file, $seek_start);

		while(!feof($file))
		{
			print(@fread($file, 1024*8));
			ob_flush();
			flush();
			if (connection_status()!=0)
			{
				@fclose($file);
				exit;
			}
		}

		// file save was a success
		@fclose($file);

		$response['code'] = LicenseClient::CODE_SUCCESS;

		return $response;
	}

}