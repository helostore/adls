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
use HeloStore\ADLS\Logger;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
// header("HTTP/1.1 404 Not Found"); exit;
$app = new LicenseServer();
$response = array();
$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
$exception = null;

Logger::instance()->dump('API call: ' . $mode);

try {
    $requestData = $_REQUEST;
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $json = file_get_contents('php://input');
        $requestData = json_decode($json, true);
    }
	$response = $app->handleRequest($requestData, $_SERVER, array(
        'controller' => $controller,
        'mode' => $mode,
    ));

	Logger::instance()->log($requestData, $_SERVER, Logger::OBJECT_TYPE_API, $mode, $response);
    $httpCode = 200;
    http_response_code($httpCode);
//	if (defined('WS_DEBUG')) {
//		$response['request'] = $_REQUEST;
//		$e = new \Exception();
//		$response['trace'] = $e->getTraceAsString();
//	}
} catch (\Exception $e) {
//    $httpCode = 412;
//    $httpCode = 200;
//    header($protocol . ' ' . $httpCode . ' ' . $e->getMessage() . ' ' . $e->getCode());

    $response['code'] = $e->getCode();
    $response['message'] = $e->getMessage();

    $exception = $e;
	Logger::instance()->error(
		$_REQUEST,
		$_SERVER,
		Logger::OBJECT_TYPE_API,
		$mode,
		$e->getMessage() . ' ' . $e->getCode(),
		nl2br($e->getTraceAsString())
	);
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
		$log['exceptionMessage'] = $exception->getMessage();
		$log['exceptionTrace'] = $exception->getTraceAsString();
	}
	ws_log_file($log, 'var/log/adls.log');
}
$response = json_encode($response);
//header('Content-Type: application/json');

echo $response;
exit;
