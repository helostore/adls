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

use HeloStore\ADLS\LicenseServer;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$app = new LicenseServer();
$response = array();
$exception = null;
try {
	$response = $app->handleRequest($_REQUEST);

//	if (defined('WS_DEBUG')) {
//		$response['request'] = $_REQUEST;
//		$e = new \Exception();
//		$response['trace'] = $e->getTraceAsString();
//	}
} catch (\Exception $e) {
	$response['code'] = $e->getCode();
	$response['message'] = $e->getMessage();
	$exception = $e;
//	if (defined('WS_DEBUG')) {
//		$response['request'] = $_REQUEST;
//		$response['trace'] = $e->getTraceAsString();
//	}
}

if (function_exists('ws_log_file')) {
	$log = array(
		'request' => $_REQUEST,
		'response' => $response,
	);
	if (!empty($exception)) {
		$log['exceptionTrace'] = $exception->getTraceAsString();
	}
	ws_log_file($log, 'var/log/adls.log');
}

$response = json_encode($response);
echo $response;
exit;