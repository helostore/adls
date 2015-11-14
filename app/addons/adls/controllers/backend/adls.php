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

use HeloStore\ADLS\LicenseClient;
use HeloStore\ADLS\LicenseManager;
use HeloStore\ADLS\LicenseServer;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'verify') {
        return array (CONTROLLER_STATUS_REDIRECT, '');
    }
}

if ($mode == 'test') {

/*	$request = array (
		'dispatch' => 'adls_api.update_check',
		'server' =>
			array (
				'hostname' => 'local.helostore.com',
				'ip' => '127.0.0.24',
				'port' => '80',
			),
		'platform' =>
			array (
				'name' => 'CS-Cart',
				'version' => '4.3.4',
				'edition' => 'ULTIMATE',
			),
		'language' => 'en',
		'product' => array (
			'email' => '***REMOVED***',
			'password' => '***REMOVED***',
			'license' => '***REMOVED***',
			'info' => '',
			'version' => '0.1.1',
			'name' => 'AutoImage Lite',
			'code' => 'autoimage_lite',
		),
		'email' => '***REMOVED***',
		'password' => '***REMOVED***',
		'token' => '***REMOVED***',
		'context' => LicenseClient::CONTEXT_UPDATE_DOWNLOAD,
	);

	$server = new LicenseServer();
	$server->handleRequest($request);
	exit;*/

//	$server->checkUpdates(array (
/*	$server->updateRequest(array (
		'dispatch' => 'adls_api.update_check',
		'server' =>
			array (
				'hostname' => 'local.helostore.com',
				'ip' => '127.0.0.24',
				'port' => '80',
			),
		'platform' =>
			array (
				'name' => 'CS-Cart',
				'version' => '4.3.4',
				'edition' => 'ULTIMATE',
			),
		'language' => 'en',
		'products' =>
			array (
				'autoimage_lite' =>
					array (
						'email' => '***REMOVED***',
						'password' => '***REMOVED***',
						'license' => '***REMOVED***',
						'info' => '',
						'version' => '0.1.1',
						'name' => 'AutoImage Lite',
						'code' => 'autoimage_lite',
					),
				'developer' =>
					array (
						'version' => '0.1',
						'name' => 'Developer Tools',
						'code' => 'developer',
					),
				'enhance' =>
					array (
						'version' => '0.1',
						'name' => '',
						'code' => 'enhance',
					),
				'sidekick' =>
					array (
						'version' => '0.1',
						'name' => 'Sidekick',
						'code' => 'sidekick',
					),
			),
		'context' => 'update_check',
	));*/
//	$manager = LicenseManager::instance();
//
//	$productId = 2;
//	$orderId = 15;
//	$userId = 2;
//
//	$order_info = fn_get_order_info($orderId);
//	fn_adls_process_order($order_info);




	exit;
}
if ($mode == 'manage') {

}

if ($mode == 'logs') {
	$logger = \HeloStore\ADLS\Logger::instance();
	$logs = $logger->get($_REQUEST);

	\Tygh\Registry::get('view')->assign('logs', $logs);


}