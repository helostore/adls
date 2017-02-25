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

use HeloStore\ADLS\License;
use HeloStore\ADLS\LicenseClient;
use HeloStore\ADLS\LicenseManager;
use HeloStore\ADLS\LicenseServer;
use HeloStore\ADLS\Logger;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'verify') {
        return array (CONTROLLER_STATUS_REDIRECT, '');
    }
}

if ($mode == 'logs') {
	$logger = \HeloStore\ADLS\Logger::instance();
	$params = $_REQUEST;

	if (!empty($params['self_exclude'])) {
		$params['exclude_ips'] = array(
			'188.166.76.129'
		);
	}
    $params['limit'] = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
	list($logs, $result) = $logger->get($params);

	if (!empty($params['id'])) {

		echo '<pre>' . var_export($logs, 1) . '</pre>';
		exit;
	}

	\Tygh\Registry::get('view')->assign('result', $result);
	\Tygh\Registry::get('view')->assign('logs', $logs);


}

if ($mode == 'update_logs_info') {

	$entries = db_get_array('SELECT id, ip, country, hostname, server FROM ?:adls_logs');
    $i = 0;

    $logger = Logger::instance();
    $countries = array();
	foreach ($entries as $entry) {
        $update = array();
		$entry['server'] = @json_decode($entry['server'], true);
		if (empty($entry['ip']) && !empty($entry['server']['REMOTE_ADDR'])) {
			$update['ip'] = $entry['ip'] = $entry['server']['REMOTE_ADDR'];

		}
		if (!empty($entry['ip'])) {
            if (empty($entry['country'])) {
                $country = $logger->getCountryCodeByIp($entry['ip']);
                if (!empty($country)) {
                    $update['country'] = $country;
                    if (!in_array($country, $countries)) {
                        $countries[] = $country;
                    }
                }
            }
            if (empty($entry['hostname'])) {
                $hostname = gethostbyaddr($entry['ip']);
                if ($hostname != $entry['ip']) {
                    $update['hostname'] = $hostname;
                }
            }
		}

        if (!empty($update)) {
            db_query('UPDATE ?:adls_logs SET ?u WHERE id = ?i', $update, $entry['id']);
            $i++;
        }
	}
    fn_print_die('Updated ' . $i . ' items' . (!empty($countries) ? '; countries: ' . implode(', ', $countries) : '') );
}
if ($mode == 'test_order_process') {
    //	$productId = 2;
    $orderId = 95;
//	$userId = 2;
    $orderStatus = 'P';
//
    $order_info = fn_get_order_info($orderId);
    fn_adls_process_order($order_info, $orderStatus);
    exit;
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
			'email' => 'sergiu@ophmisu.com',
			'password' => 'curacao',
			'license' => 'LEK7-N42B-M9HW-TY5P',
			'info' => '',
			'version' => '0.1.1',
			'name' => 'AutoImage Lite',
			'code' => 'autoimage_lite',
		),
		'email' => 'sergiu@ophmisu.com',
		'password' => 'curacao',
		'token' => 'MAGICefeb3ead48c7d3529f91b86adc8e3a1ef31b2f1f75c69f8856463cca50af450f82f03a2829546af407cbab3adf56877106b5ebd69ffb6f2dcf48d332a69',
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
						'email' => 'sergiu@ophmisu.com',
						'password' => 'curacao',
						'license' => 'LEK7-N42B-M9HW-TY5P',
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




	exit;
}


if ($mode == 'fix_orphaned_licenses') {
    $licenses = db_get_array('SELECT * FROM ?:adls_licenses');
    foreach ($licenses as $license) {
        $item = db_get_row('SELECT * FROM ?:order_details WHERE item_id = ?s AND order_id = ?i AND product_id = ?i',
            $license['orderItemId']
            , $license['orderId']
            , $license['productId']
        );
        if (empty($item)) {
            $manager = LicenseManager::instance();
            $license = $manager->getOrderLicense($license['orderId'], $license['orderItemId']);
            if (!empty($license)) {
                aa('Deleted orphan license #' . $license['id']);
                $manager->deleteLicense($license['id']);
            }
        }
    }

    exit;
}
if ($mode == 'fix_domain_product_option_ids') {
    $licenses = db_get_array('SELECT * FROM ?:adls_licenses');
    foreach ($licenses as $license) {
//        $domains = db_get_array('SELECT * FROM ?:adls_license_domains WHERE license_id = ?i', $license['license_id']);
        $order = fn_get_order_info($license['orderId']);
        foreach ($order['products'] as $item) {

            $options = array();
            foreach ($item['product_options'] as $option) {
                $type = $option['adls_option_type'];
                if ($type == 'domain') {
                    $type = License::DOMAIN_TYPE_PRODUCTION;
                }
                if ($type == 'dev_domain') {
                    $type = License::DOMAIN_TYPE_DEVELOPMENT;
                }
                if (empty($type)) {
                    continue;
                }
                if (!isset($options[$type])) {
                    $options[$type] = array();
                }

                $options[$type][] = $option;
            }

            $domains = array();
            foreach ($item['license']['domains'] as $domain) {
                $type = $domain['type'];
                if (!isset($domains[$type])) {
                    $domains[$type] = array();
                }

                $domains[$type][] = $domain;
            }

            foreach ($domains as $type => $_domains) {
                foreach ($_domains as $domain) {
                    $option = array_shift($options[$type]);
                    if (!empty($option)) {
                        $query = db_quote('UPDATE ?:adls_license_domains SET productOptionId = ?i WHERE id = ?i', $option['option_id'], $domain['id']);
                        aa($query);
                        db_query($query);
                    }
                }

            }
        }
    }
    exit;
}

