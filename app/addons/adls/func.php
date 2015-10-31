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

use HeloStore\ADLS\LicenseManager;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/* Hooks */
function fn_adls_change_order_status($status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order)
{
	$statuses = array('P');
	if (in_array($status_to, $statuses)) {
		if (fn_adls_process_order($order_info)) {

		} else {
			$errors = LicenseManager::instance()->getErrors();
			foreach ($errors as $error) {
				fn_set_notification('E', __('error'), __($error), '', '404');
			}
		}
	}
}
/* /Hooks */

function fn_adls_process_order($order_info)
{
	$manager = LicenseManager::instance();
	$orderId = $order_info['order_id'];
	$userId = $order_info['user_id'];
	$errors = array();

	foreach ($order_info['products'] as $product) {
		$productId = $product['product_id'];
		if (!fn_is_adls_product($product)) {
			continue;
		}
		$options = fn_adls_get_product_options($product);
		$licenseId = $manager->createLicense($productId, $orderId, $userId);
		if ($licenseId) {
			if (!empty($options['domain'])) {
				$domains = array($options['domain']);
				$manager->updateLicenseDomains($licenseId, $domains);
			}
			return true;
		} else {
			$errors = $manager->getErrors();
		}
	}

	return false;
}

function fn_adls_get_product_options($product)
{
	if (empty($product['product_options'])) {
		return array();
	}
	$options = array();
	foreach ($product['product_options'] as $opt) {
		$optionId = $opt['option_id'];
		$type = db_get_field('SELECT adls_option_type FROM ?:product_options WHERE option_id = ?i', $optionId);

		$value = $opt['value'];
		if (!empty($type) && !empty($value)) {
			$options[$type] = $value;
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