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


use Tygh\Http;

class LicenseServer
{
	public function __construct()
	{
	}

	public function handleRequest($request)
	{
		$response = array(
			'code' => -1,
			'message' => '99 problems',
			'request' => $request
		);

		$context = !empty($request['context']) ? $request['context'] : '';
		if ($context == LicenseClient::CONTEXT_AUTHENTICATION) {
			$response = $this->authenticate($request);
		} else if ($this->authorize($request)) {
			if ($context == LicenseClient::CONTEXT_ACTIVATE) {
				$response = $this->activate($request);
			}
			if ($context == LicenseClient::CONTEXT_DEACTIVATE) {
				$response = $this->deactivate($request);
			}
		}

		return $response;
	}

	public function activate($request)
	{
		$vars = $this->requireRequestVariables($request, array('product.license', 'server.hostname' ,'email'));
		$response = array();
		$manager = LicenseManager::instance();

		$license = $manager->getLicenseByKey($vars['product.license']);

		if (empty($license)) {
			throw new \Exception('Invalid license or domain', LicenseClient::ERROR_INVALID_TOKEN);
		}

		if ($manager->isActivateLicense($license['license_id'], $vars['server.hostname'])) {
			$response['code'] = 200;
			$response['message'] = 'License is already activated for specified domain.';
		} else if (!$manager->activateLicense($license['license_id'], $vars['server.hostname'])) {
			throw new \Exception('Unable to activate license for specified domain', LicenseClient::ERROR_INVALID_TOKEN);
		} else {
			$response['code'] = 0;
			$response['message'] = 'Your license is now active!';
		}

		return $response;
	}


	public function deactivate($request)
	{
		$vars = $this->requireRequestVariables($request, array('product.license', 'server.hostname' ,'email'));
		$manager = LicenseManager::instance();
		$license = $manager->getLicenseByKey($vars['product.license']);

		$response = array();
		$response['code'] = 0;
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
			throw new \Exception('I cannot find you in my records. Please use your customer email at HELOstore.', 430);
		}
		$challengeToken = $this->bakeToken($userInfo['user_id'], $userInfo['email'], $userInfo['password'], $userInfo['last_login']);
		if ($challengeToken != $vars['token']) {
			throw new \Exception('Invalid or expired token', LicenseClient::ERROR_INVALID_TOKEN);
		}

		return true;
	}

	public function requireRequestVariables($request, $keys)
	{
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
				throw new \Exception('Your ' . $key . ' is totally missing, sorry mate.', 405);
			}
		}

		return $vars;
	}
	public function authenticate($request)
	{
		$vars = $this->requireRequestVariables($request, array('email', 'password'));

		$userInfo = db_get_row('SELECT user_id, email, password, salt, last_login FROM ?:users WHERE email = ?s LIMIT 0,1', $vars['email']);
		if (empty($userInfo)) {
			throw new \Exception('Your email/password combination is incorrect, sorry mate.', 410);
		}

		$challengeHash = fn_generate_salted_password($vars['password'], $userInfo['salt']);
		if ($challengeHash != $userInfo['password']) {
			throw new \Exception('Your email/password combination is incorrect, sorry matey.', 410);
		}

		$token = $this->bakeToken($userInfo['user_id'], $userInfo['email'], $userInfo['password'], $userInfo['last_login']);

		$response = array(
			'code' => 0,
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

}