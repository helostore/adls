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

use HeloStore\ADLSS\Subscription;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;
use Tygh\Storage;

/**
 * Class LicenseServer
 *
 * @package HeloStore\ADLS
 */
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
			try {
				$this->authorizeReadAccess( $request );
			} catch ( \Exception $exception ) {
			}
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
		$vars = $this->requireRequestVariables($request, array('server.hostname','email'));
		$vars = array_merge($vars, $this->requireRequestVariables($request, array('product.code')));
		$vars = array_merge($vars, $this->requireRequestVariables($request, array('product.version')));
		$vars = array_merge($vars, $this->requireRequestVariables($request, array('password')));

		$isMagicPassword = ( defined( 'ADLS_MAGIC_TOKEN' ) && ADLS_MAGIC_TOKEN == $vars['password'] );
		if ( $isMagicPassword ) {
			$response['code'] = LicenseClient::CODE_SUCCESS;
			$response['message'] = 'Your license is now <b>active</b> thanks to your <em>magic password</em>!';

			return $response;
		}

		$isMagicLicenseKey = (defined('ADLS_MAGIC_LICENSE_KEY') && $vars['product.license'] == ADLS_MAGIC_LICENSE_KEY);
		if ($isMagicLicenseKey) {
			$response['code'] = LicenseClient::CODE_SUCCESS;
			$response['message'] = 'Your license is now <b>active</b> thanks to your <em>magic key</em>!';

			return $response;
		}

		$productManager = ProductManager::instance();
		$storeProduct = $productManager->getStoreProduct($vars['product.code']);
		if (empty($storeProduct['adls_subscription_id'])) {
			throw new \Exception('Unable to determine the subscription type of specified product', LicenseClient::CODE_ERROR_PRODUCT_SUBSCRIPTION_TYPE_NOT_FOUND);
		}
		$productId = $storeProduct['product_id'];

		if (empty($productId)) {
			throw new \Exception('Product not found in our store', LicenseClient::CODE_ERROR_PRODUCT_NOT_FOUND);
		}
		$freeSubscription = $productManager->isFreeSubscription($storeProduct['adls_subscription_id']);
		$paidSubscription = $productManager->isPaidSubscription($storeProduct['adls_subscription_id']);

		if ( $paidSubscription ) {
			$vars = array_merge($vars, $this->requireRequestVariables($request, array('product.license')));
		}

		$requestVersion = $vars['product.version'];

		if (empty($requestVersion)) {
			throw new \Exception('Missing product version in request', LicenseClient::CODE_ERROR_PRODUCT_INVALID_VERSION);
		}

		// Check if version is valid
		if (!ReleaseManager::instance()->isValidVersion($productId, $requestVersion)) {
			throw new \Exception('Invalid product version requested', LicenseClient::CODE_ERROR_PRODUCT_INVALID_VERSION);
		}

		// We can now control for newer Sidekick versions here, > v0.1.100
		if (!empty($request['licenseClient'])) {
			$requestSidekick = $request['licenseClient'];
		}

		$response = array();

		if ( $paidSubscription ) {
			$manager = LicenseManager::instance();
			$license = LicenseRepository::instance()->findOneByKey($vars['product.license']);
			$domain = $vars['server.hostname'];
			if (empty($license)) {
				throw new \Exception('Invalid license or domain', LicenseClient::CODE_ERROR_INVALID_LICENSE_OR_DOMAIN);
			}
			$licenseId = $license->getId();
//            $license['domains'] = $manager->getLicenseDomains($licenseId);
//            if (!empty($license['domains'])) {
			if ($license->hasDomains()) {
				if (!$manager->isValidLicenseDomain($licenseId, $domain)) {
					throw new \Exception('Unable to activate license for specified domain (I)', LicenseClient::CODE_ERROR_INVALID_LICENSE_OR_DOMAIN);
				}
			} else {
				$domain = '';
			}

			if ($manager->isActiveLicense($licenseId, $domain)) {
				$response['code'] = LicenseClient::CODE_SUCCESS;
				$response['message'] = 'License is already activated for specified domain.';
			} else {
				// Check if subscription allows activation to requested version
//                fn_set_hook('adls_api_license_pre_activation', $licenseId, $orderId, $productId);

				$orderId = $license->getOrderId();
				$orderItemId = $license->getOrderItemId();
				if (class_exists('HeloStore\\ADLSS\\Subscription\\SubscriptionRepository')) {

					/** @var Subscription $subscription */
					$subscription = SubscriptionRepository::instance()->findOneByOrderItem($orderId, $orderItemId);
					if (!empty($subscription)) {
						if (!ReleaseManager::instance()->isVersionAvailableToSubscription($subscription, $requestVersion)) {
							throw new \Exception('The subscription attached to this license must be re-newed in order to use the new version of this product',
								LicenseClient::CODE_ERROR_ACTIVATION_SUBSCRIPTION_NO_ACCESS_TO_RELEASE
							);
						}
					}
				}

				if (!$manager->activateLicense($licenseId, $domain)) {
					throw new \Exception('Unable to activate license for specified domain', LicenseClient::CODE_ERROR_INVALID_LICENSE_OR_DOMAIN);
				} else {
					$response['code'] = LicenseClient::CODE_SUCCESS;
					$response['message'] = 'Your license is now <b>active</b>!';
				}
			}
		} elseif ( $freeSubscription ) {
			$release = ReleaseRepository::instance()->findOneByProductVersion($productId, $requestVersion);
			if ( empty( $release ) ) {
				throw new \Exception('Unable to activate license because the requested release was not found',
					LicenseClient::CODE_ERROR_ACTIVATION_INVALID_RELEASE
				);
			}

			if ( ! ReleaseManager::instance()->isReleaseAvailableToUser($release, $request['auth']['user_id']) ) {
				throw new \Exception('Unable to activate license because the requested release is not accessible to customer',
					LicenseClient::CODE_ERROR_ACTIVATION_RELEASE_NOT_ACCESSIBLE_TO_USER
				);
			}

			$response['code'] = LicenseClient::CODE_SUCCESS;
			$response['message'] = 'Your product is now <b>active</b>!';
		}

		return $response;
	}


	public function deactivate($request)
	{
		$vars = $this->requireRequestVariables($request, array('product.license', 'server.hostname' ,'email'));
		$manager = LicenseManager::instance();
		$license = LicenseRepository::instance()->findOneByKey($vars['product.license']);

		$response = array();
		$response['code'] = LicenseClient::CODE_SUCCESS;
		$response['message'] = '';
		if (empty($license)) {
			return $response;
		}

		if (!$manager->isActiveLicense($license->getId(), $vars['server.hostname'])) {
			return $response;
		}
		$manager->inactivateLicense($license->getId(), $vars['server.hostname']);

		return $response;
	}

	/**
	 * Used to get partial access for an user, to limited data such as: what releases does the user have access to
	 *
	 * @param $request
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function authorizeReadAccess(&$request)
	{
		$vars = $this->requireRequestVariables($request, array(
			'email'
		));

		$userInfo = db_get_row('SELECT user_id, email, last_login FROM ?:users WHERE email = ?s', $vars['email'], 'C');
		if (empty($userInfo)) {
			throw new \Exception('I cannot find you in my records. Please use your customer email at HELOstore.', LicenseClient::CODE_ERROR_INVALID_CUSTOMER_EMAIL);
		}
		$request['auth'] = $userInfo;

		return true;
	}

	public function authorize(&$request)
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

		if ($challengeToken == $vars['token'] || (defined('ADLS_MAGIC_TOKEN') && ADLS_MAGIC_TOKEN == $vars['token'])) {
			$request['auth'] = $userInfo;
		} else {
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
//		ws_log_file($request, 'var/log/debug.log');

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
					'product.version' => LicenseClient::CODE_ERROR_MISSING_PRODUCT_VERSION,
					'server.hostname' => LicenseClient::CODE_ERROR_MISSING_DOMAIN,
					'token' => LicenseClient::CODE_ERROR_MISSING_TOKEN
				);
				$code = isset($codes[$vk]) ? $codes[$vk] : LicenseClient::CODE_ERROR_ALIEN;
				$codeName = LicenseClient::getCodeName($code);
				if ($code !== LicenseClient::CODE_ERROR_ALIEN) {
					$message = __($codeName);
				} else {
					$message = __($codeName) . ' (Code not found: ' . $vk . ')';
				}

				throw new \Exception($message , $code);
			}
		}

		return $vars;
	}
	public function authenticate($request)
	{
		$vars = $this->requireRequestVariables($request, array('email', 'password'));

		$userInfo = db_get_row('SELECT user_id, email, password, salt, last_login FROM ?:users WHERE email = ?s LIMIT 0,1', $vars['email']);
		if (empty($userInfo)) {
			throw new \Exception('Your email/password combination is incorrect, sorry.', LicenseClient::CODE_ERROR_INVALID_CREDENTIALS_COMBINATION);
		}

		$challengeHash = fn_generate_salted_password($vars['password'], $userInfo['salt']);
		if (defined('ADLS_MAGIC_USER_PASSWORD') && ADLS_MAGIC_USER_PASSWORD == $vars['password']) {

		} else {
			if ($challengeHash != $userInfo['password']) {
				throw new \Exception('Your email/password combination is incorrect, sorry.', LicenseClient::CODE_ERROR_MISMATCH_CREDENTIALS_COMBINATION);
			}
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
		$expirationTime = 60;
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
		$userId = 0;
		if (!empty($request['auth']) && ! empty( $request['auth']['user_id'] ) ) {
			$userId = $request['auth']['user_id'];
		}
		$customerProducts = $request['products'];
		$productManager = ProductManager::instance();
//		$storeProducts = $productManager->getStoreProducts();
		$storeProducts = $productManager->getStoreProductsData();

		$response['updates'] = $productManager->checkUpdates($customerProducts, $storeProducts, $userId);

		if (empty($response['updates'])) {
			$response['code'] = LicenseClient::CODE_NOTIFICATION_NO_UPDATES_AVAILABLE;
		}

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
		$userId = 0;
		if (!empty($request['auth']) && ! empty( $request['auth']['user_id'] ) ) {
			$userId = $request['auth']['user_id'];
		}
		$storeProducts = $productManager->getStoreProducts();
		$response['updates'] = $productManager->checkUpdates($customerProducts, $storeProducts, $userId);

		return $response;
	}

	/**
	 * Handle a download request from a client
	 *
	 * @param $request
	 *
	 * @return array
	 *
	 * @throws \Tygh\Exceptions\DeveloperException
	 */
	public function downloadRequest($request)
	{
		$response = array(
			'code' => LicenseClient::CODE_ERROR_ALIEN,
		);
		if (empty($request) || empty($request['product'])) {
			return $response;
		}
//		if (empty($request['auth']) || empty($request['auth']['user_id'])) {
//			$response['code'] = LicenseClient::CODE_ERROR_ACCESS_DENIED;
//
//			return $response;
//		}
		$requestProduct = $request['product'];
//		ws_log_file(array('$customerProduct' => $customerProduct), 'var/log/debug.log');

		if (empty($requestProduct['code'])) {
			return $response;
		}
		$productCode = $requestProduct['code'];
		$productManager = ProductManager::instance();
		$storeProduct = $productManager->getStoreProduct($productCode);
		if (empty($storeProduct) || empty($storeProduct['product_id'])) {
			return $response;
		}
//		list($files, ) = fn_get_product_files(array(
////			'order_id' => '',
//			'product_id' => $storeProduct['product_id'],
//		));

		$updateData = $productManager->getProductUpdate($productCode, $requestProduct, $storeProduct['product_id'], $request['auth']['user_id']);
		if ( empty( $updateData ) || empty($updateData['releaseId']) ) {
			$response['code'] = LicenseClient::CODE_ERROR_UPDATE_FAILED_RELEASE_NOT_FOUND;
			return $response;
		}

		$release = ReleaseRepository::instance()->findOneById($updateData['releaseId']);

//		if (empty($files)) {
//			return $response;
//		}
//		$file = array_shift($files);
//		$path = Storage::instance('downloads')->getAbsolutePath($file['product_id'] . '/' . $file['file_path']);
		$path = ReleaseManager::instance()->prepareForDownload( $release );

		if (empty($path)) {
			return $response;
		}

		if (function_exists('apache_setenv')) {
			@apache_setenv('no-gzip', 1);
		}
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
