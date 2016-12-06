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
use HeloStore\ADLS\LicenseManager;
use HeloStore\ADLS\Logger;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/* Hooks */
function fn_adls_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
	fn_adls_process_order($order_info, $status_to);
}

function fn_adls_delete_order($orderId)
{
	$manager = LicenseManager::instance();
	$licenses = $manager->getOrderLicenses($orderId);
	foreach ($licenses as $license) {
		$manager->deleteLicense($license['license_id']);
	}
}
function fn_adls_get_order_info(&$order, $additional_data)
{
	$productManager = \HeloStore\ADLS\ProductManager::instance();
	foreach ($order['products'] as $i => &$product) {
		if (fn_is_adls_product($product)) {
			$storeProduct = $productManager->getProductById($product['product_id']);
			$product['license'] = LicenseManager::instance()->getOrderLicense($order['order_id'], $product['item_id']);
//			aa($storeProduct);
//			aa($product['license'],1);
			if ($productManager->isPaidSubscription($storeProduct)) {
			}
		}
	}
	unset($product);
}

function fn_adls_generate_cart_id(&$_cid, $extra, $only_selectable)
{
	// Exclude domain names from cid because we don't want to generated new cart item id each time we update a domain
	$optionTypes = fn_adls_get_product_option_types();
	$optionTypes = array_keys($optionTypes);
	$excludeOptionIds = db_get_fields('SELECT option_id FROM ?:product_options WHERE adls_option_type IN (?a)', $optionTypes);


	// Grab values of excluded options
	$excludedValues = array();
	if (!empty($extra['product_options']) && is_array($extra['product_options'])) {

		// Try to select all options (including Globals)
		Registry::set('runtime.skip_sharing_selection', true);

		foreach ($extra['product_options'] as $k => $v) {
			if ($only_selectable == true && ((string) intval($v) != $v || db_get_field("SELECT inventory FROM ?:product_options WHERE option_id = ?i", $k) != 'Y')) {
				continue;
			}
			$excludedValues[] = $v;
		}

		Registry::set('runtime.skip_sharing_selection', false);
	}
	if (!empty($excludedValues)) {
		$_cid = array_diff($_cid, $excludedValues);
	}
}
/* /Hooks */

function fn_adls_process_order($order_info, $orderStatus)
{
	$manager = LicenseManager::instance();
	$orderId = $order_info['order_id'];
	$userId = $order_info['user_id'];
	$errors = array();
	$success = true;
	$paidStatuses = array('P');
	$isPaidStatus = in_array($orderStatus, $paidStatuses);

	foreach ($order_info['products'] as $product) {
		$productId = $product['product_id'];
		$itemId = $product['item_id'];

		if (!fn_is_adls_product($product)) {
			continue;
		}

		$options = fn_adls_get_product_options($product);

		$productionDomain = (!empty($options['domain']) ? $options['domain'] : '');
		$developmentDomains = (!empty($options['dev_domain']) ? $options['dev_domain'] : '');
		$domains = array();
		if (!empty($productionDomain)) {
			$domains[] = array(
				'name' => $productionDomain,
				'type' => License::DOMAIN_TYPE_PRODUCTION
			);
		}

		if (!empty($developmentDomains)) {
			$developmentDomains = is_array($developmentDomains) ? $developmentDomains : array($developmentDomains);
			foreach ($developmentDomains as $developmentDomain) {
				$domains[] = array(
					'name' => $developmentDomain,
					'type' => License::DOMAIN_TYPE_DEVELOPMENT
				);
			}
		}
		$licenseId = $manager->existsLicense($productId, $itemId, $orderId, $userId);
		$notificationState = (AREA == 'A' ? 'I' : 'K');

		if ($isPaidStatus) {
			if (!empty($licenseId)) {
				foreach ($domains as $domain) {
					if ($manager->inactivateLicense($licenseId, $domain['name'])) {
						fn_set_notification('N', __('notice'), __('adls.order_licenses_inactivated'), $notificationState);
					}
				}
			} else {
				$licenseId = $manager->createLicense($productId, $itemId, $orderId, $userId);
				if ($licenseId) {
					fn_set_notification('N', __('notice'), __('adls.order_licenses_created'), $notificationState);

					if (!empty($domains)) {
						$manager->updateLicenseDomains($licenseId, $domains);
					}
				} else {
					$success = false;
					$errors += $manager->getErrors();
				}
			}
		} else {
			foreach ($domains as $domain) {
				if ($manager->disableLicense($licenseId, $domain['name'])) {
					fn_set_notification('N', __('notice'), __('adls.order_licenses_disabled'), $notificationState);
				}
			}
		}

	}

	if (!empty($errors)) {
		foreach ($errors as $error) {
			fn_set_notification('E', __('error'), __($error), 'K');
		}

	}

	return $success;
}

function fn_adls_get_product_option_types()
{
	$types = array(
		'domain' => 'Single domain',
		'dev_domain' => 'Development domain',
		'domains' => 'Multiple domains',
	);

	return $types;
}
function fn_adls_get_product_options($product)
{
	if (empty($product['product_options'])) {
		return array();
	}
	$options = array();
	foreach ($product['product_options'] as $opt) {
		$optionId = $opt['option_id'];
		$type = (string) db_get_field('SELECT adls_option_type FROM ?:product_options WHERE option_id = ?i', $optionId);

		$value = $opt['value'];
		if (!empty($type) && !empty($value)) {
			if (isset($options[$type])) {
				$tmp = $options[$type];
				$options[$type] = array();
				$options[$type][] = $tmp;
				$options[$type][] = $value;
			} else {
				$options[$type] = $value;
			}
		}
	}

	return $options;
}
function fn_is_adls_product($product)
{
	$productId = $product['product_id'];
	$productType = !empty($product['product_type']) ? $product['product_type'] : db_get_field('SELECT product_type FROM ?:products WHERE product_id = ?i', $productId);

	return in_array($productType, array(ADLS_PRODUCT_TYPE_ADDON, ADLS_PRODUCT_TYPE_THEME));
}

function fn_adls_get_license_status_label($status)
{
	if ($status == License::STATUS_INACTIVE) {
		$label = 'adls.license_status_inactive';
	} else if ($status == License::STATUS_ACTIVE) {
		$label = 'adls.license_status_active';
	} else if ($status == License::STATUS_DISABLED) {
		$label = 'adls.license_status_disabled';
	} else {
		$label = 'adls.license_status_unknown';
	}

	return __($label);
}

function fn_adls_log_type_is_error($code)
{
	return Logger::instance()->isError($code);
}
function fn_adls_log_type_is_warning($code)
{
	return Logger::instance()->isWarning($code);
}
function fn_adls_log_type_is_log($code)
{
	return Logger::instance()->isLog($code);
}
function fn_adls_log_type_is_success($code)
{
	return Logger::instance()->isSuccess($code);
}
function fn_adls_get_log_type($code)
{
	return Logger::instance()->getLogTypeLabel($code);
}