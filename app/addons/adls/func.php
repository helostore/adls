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
use HeloStore\ADLS\Utils;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/* Hooks */

function fn_adls_get_orders_post($params, &$orders)
{
    foreach ($orders as &$order) {

        $queryLicense = db_quote('SELECT id FROM ?:adls_licenses WHERE orderId = ?i', $order['order_id']);
        $query = db_quote('SELECT GROUP_CONCAT(DISTINCT name) AS domains FROM ?:adls_license_domains WHERE licenseId IN (?p)', $queryLicense);
        $order['domains'] = db_get_field($query);
        if (!empty($order['domains'])) {
            $order['domains'] = explode(',', $order['domains']);
        }

        $queryDetails = db_quote('SELECT product_id FROM ?:order_details WHERE order_id = ?i', $order['order_id']);
        $query = db_quote('SELECT GROUP_CONCAT(DISTINCT product) AS products FROM ?:product_descriptions WHERE product_id IN (?p) AND lang_code = ?s', $queryDetails, CART_LANGUAGE);
        $order['products'] = db_get_field($query);
        if (!empty($order['products'])) {
            $order['products'] = explode(',', $order['products']);
        }
    }

    unset($order);
}
function fn_adls_place_order($orderId, $action, $orderStatus, $cart, $auth)
{
    foreach ($cart['products'] as $itemId => $item) {
        if (!fn_is_adls_product($item)) {
            continue;
        }

        // The cart/order item id changed (probably because domain changed), we should update it in our tables as well
        if (!empty($item['prev_cart_id'])) {
            $oldItemId = $item['prev_cart_id'];
            if ($oldItemId != $itemId) {
                $query = db_quote('
					UPDATE ?:adls_licenses SET orderItemId = ?s WHERE
					orderId = ?i
					AND orderItemId = ?i
					',
                    $itemId
                    , $orderId
                    , $oldItemId
                );
                db_query($query);
            }
        }
    }
    return false;

}
function fn_adls_change_order_status($status_to, $status_from, $orderInfo, $force_notification, $order_statuses, $place_order)
{
    fn_adls_process_order($orderInfo, $status_to, $status_from);
}

function fn_adls_delete_order($orderId)
{
    $manager = LicenseManager::instance();
    $licenseRepository = \HeloStore\ADLS\LicenseRepository::instance();
    $licenses = $manager->getOrderLicenses($orderId);
    foreach ($licenses as $license) {
        $licenseRepository->delete($license['id']);
    }
}
function fn_adls_get_order_info(&$order, $additional_data)
{
    $productManager = \HeloStore\ADLS\ProductManager::instance();
    // CS-Cart bug: this gets called on non-existing orders as well?!
    if (empty($order['products'])) {
        return;
    }
    foreach ($order['products'] as $i => &$product) {
        if (fn_is_adls_product($product)) {
            $storeProduct = $productManager->getProductById($product['product_id']);
            $product['license'] = LicenseManager::instance()->getOrderLicense($order['order_id'], $product['item_id']);
            if ($productManager->isPaidSubscription($storeProduct)) {
            }
        }
    }
    unset($product);
}

function fn_adls_get_additional_information(&$product, $product_data)
{
    foreach ($product['selected_options'] as $optionId => $optionValue) {

        $option = db_get_row("SELECT * FROM ?:product_options WHERE option_id = ?i", $optionId);
        if (!fn_adls_is_product_option_domain($option)) {
            continue;
        }
        $domainType = $option['adls_option_type'];

        $result = Utils::validateHostname($optionValue, $domainType);

        if ($result !== true) {
            unset($product['selected_options'][$optionId]);
            $message = __('adls.order_license_domain_update_failed', array('[domain]' => $optionValue));
            foreach ($result as $value) {
                $message .= '<br> - ' . $value;
            }
            fn_set_notification('E', __('error'), $message, 'I');
        }
    }
}

/**
 * Mirror main product's options in required product's options (domains)
 *
 * @param $requiredProductId
 * @param $requiredProductAmount
 * @param $mainProduct
 * @param $bufferRequiredProducts
 *
 * @throws Exception
 */
function fn_adls_required_products_pre_add_to_cart($requiredProductId, $requiredProductAmount, $mainProduct, &$bufferRequiredProducts)
{
    if (!fn_is_adls_product($mainProduct)) {
        return;
    }

    if (empty($mainProduct['product_options'])) {
        return;
    }

    if (!isset($bufferRequiredProducts[$requiredProductId])) {
        throw new Exception('Required product not found in buffer');
    }
    $requiredProduct = $bufferRequiredProducts[$requiredProductId];
    $mainProductId = $mainProduct['product_id'];
    if (empty($requiredProduct['product_options'])) {
        $requiredProduct['product_options'] = array();
    }

    $defaultMainProductOptions = fn_get_product_options($requiredProductId);
    $defaultMainProductDomainsOptions = Utils::extractDomainsFromProductOptions($defaultMainProductOptions);
    foreach ($defaultMainProductDomainsOptions as $option) {
        $mainProductOptionId = $option['product_option_id'];
        $value = $mainProduct['product_options'][$mainProductOptionId];
        $bufferRequiredProducts[$requiredProductId]['product_options'][$mainProductOptionId] = $value;
    }
}

/* /Hooks */

function fn_adls_validate_product_options($product_options)
{

    foreach ($product_options as $optionId => $optionValue) {

        $option = db_get_row("SELECT * FROM ?:product_options WHERE option_id = ?i", $optionId);
        if (!fn_adls_is_product_option_domain($option)) {
            continue;
        }
        $domainType = $option['adls_option_type'];
        if ($domainType == 'domain') {
            $domainType = License::DOMAIN_TYPE_PRODUCTION;
        }
        if ($domainType == 'dev_domain') {
            $domainType = License::DOMAIN_TYPE_DEVELOPMENT;
        }

        $result = Utils::validateHostname($optionValue, $domainType);
        if ($result !== true) {
            unset($product_options[$optionId]);
            $message = __('adls.order_license_domain_update_failed', array('[domain]' => $optionValue));
            foreach ($result as $value) {
                $message .= '<br> - ' . $value;
            }
            fn_set_notification('E', __('error'), $message, 'I');
        }
    }
}

function fn_adls_process_order($orderInfo, $orderStatus, $statusFrom = null)
{
    if (defined('ORDER_MANAGEMENT')) {
        return false;
    }

    $manager = LicenseManager::instance();
    $orderId = $orderInfo['order_id'];
    $userId = $orderInfo['user_id'];
    $errors = array();
    $success = true;
    $paidStatuses = array('P');
    $isPaidStatus = in_array($orderStatus, $paidStatuses);

    foreach ($orderInfo['products'] as $product) {
        $productId = $product['product_id'];
        $itemId = $product['item_id'];

        if (!fn_is_adls_product($product)) {
            continue;
        }

        $licenseId = $manager->existsLicense($productId, $itemId, $orderId, $userId);
        $notificationState = (AREA == 'A' ? 'I' : 'K');

        if ($isPaidStatus) {

            $domainOptions = Utils::filterDomainProductOptions($product['product_options']);

            if (!empty($licenseId)) {
                Utils::updateLicenseDomainsFromProductOptions($licenseId, $domainOptions);

                // If there were any disabled licenses, inactive them, so they can become usable
                $domains = $manager->getLicenseDomains($licenseId);
                if (!empty($domains)) {
                    foreach ($domains as $domain) {
                        if ($manager->inactivateLicense($licenseId, $domain['name'])) {
                            fn_set_notification('N', __('notice'), __('adls.order_licenses_inactivated'), $notificationState);
                        }
                    }
                } else {
                    if ($manager->inactivateLicense($licenseId)) {
                        fn_set_notification('N', __('notice'), __('adls.order_licenses_inactivated'), $notificationState);
                    }
                }

            } else {
                $licenseId = $manager->createLicense($productId, $itemId, $orderId, $userId);
                if ($licenseId) {
                    fn_set_notification('N', __('notice'), __('adls.order_licenses_created'), $notificationState);

                    Utils::updateLicenseDomainsFromProductOptions($licenseId, $domainOptions);
                } else {
                    $success = false;
                    $errors += $manager->getErrors();
                }
            }
        } else {
            if (!defined('ORDER_MANAGEMENT')) {
                $domains = $manager->getLicenseDomains($licenseId);
                if (!empty($domains)) {
                    foreach ($domains as $domain) {
                        if ($manager->disableLicense($licenseId, $domain['name'])) {
                            fn_set_notification('N', __('notice'), __('adls.order_licenses_disabled'), $notificationState);
                        }
                    }

                } else {
                    if ($manager->disableLicense($licenseId)) {
                        fn_set_notification('N', __('notice'), __('adls.order_licenses_disabled'), $notificationState);
                    }
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

function fn_adls_is_product_option_domain($option)
{
    if (empty($option) || empty($option['adls_option_type'])) {
        return false;
    }

    $domainTypes = array(License::DOMAIN_TYPE_PRODUCTION, License::DOMAIN_TYPE_DEVELOPMENT);

    if (in_array($option['adls_option_type'], $domainTypes)) {
        return true;
    }

    return false;
}
function fn_adls_get_product_option_types()
{
    $types = array(
        License::DOMAIN_TYPE_PRODUCTION => 'Production domain',
        License::DOMAIN_TYPE_DEVELOPMENT => 'Development domain',
    );

    return $types;
}
function fn_adls_get_product_options($product)
{
    if (empty($product['product_options'])) {
        return array();
    }
    $options = array();
    foreach ($product['product_options'] as $k => $opt) {
        $optionId = $opt['option_id'];
        $type = db_get_field('SELECT adls_option_type FROM ?:product_options WHERE option_id = ?i', $optionId);
        if (!empty($type)) {
            $options[$k] = $opt;
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

function fn_adls_license_is_inactive($status)
{
    return $status == License::STATUS_INACTIVE;
}
function fn_adls_license_is_disabled($status)
{
    return $status == License::STATUS_DISABLED;
}
function fn_adls_license_is_active($status)
{
    return $status == License::STATUS_ACTIVE;
}

function fn_adls_get_license_statuses()
{
    return LicenseManager::instance()->getLicenseStatuses();
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

/**
 * Get ADLS related option IDs
 *
 * @return array
 */
function fn_adls_get_options_ids()
{
    $optionTypes = fn_adls_get_product_option_types();
    $optionTypes = array_keys($optionTypes);
    $optionIds = db_get_fields('SELECT option_id FROM ?:product_options WHERE adls_option_type IN (?a)', $optionTypes);

    return $optionIds;
}

function fn_adls_adls_subscriptions_post_fail(\HeloStore\ADLS\Subscription\Subscription $subscription, $product, $orderInfo)
{
    $statusFrom = $orderInfo['prev_status'];
    $statusTo = $orderInfo['status'];
}
function fn_adls_adls_subscriptions_post_begin(\HeloStore\ADLS\Subscription\Subscription $subscription, $product, $orderInfo)
{
    $statusFrom = $orderInfo['prev_status'];
    $statusTo = $orderInfo['status'];
}
function fn_adls_adls_subscriptions_post_resume(\HeloStore\ADLS\Subscription\Subscription $subscription, $product, $orderInfo)
{
    $statusFrom = $orderInfo['prev_status'];
    $statusTo = $orderInfo['status'];
}
function fn_adls_adls_subscriptions_post_suspend(\HeloStore\ADLS\Subscription\Subscription $subscription)
{
//    $statusFrom = $orderInfo['prev_status'];
//    $statusTo = $orderInfo['status'];
}