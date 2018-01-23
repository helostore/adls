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
use HeloStore\ADLS\LicenseRepository;
use HeloStore\ADLS\Logger;
use HeloStore\ADLS\ReleaseLinkRepository;
use HeloStore\ADLS\ReleaseManager;
use HeloStore\ADLS\ReleaseRepository;
use HeloStore\ADLS\Utils;
use HeloStore\ADLSS\Subscription;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;
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
	    $prevItemId = null;
	    if (!empty($item['prev_cart_id'])) {
		    $oldItemId = $item['prev_cart_id'];
	    }
	    if (!empty($item['original_product_data']) && !empty($item['original_product_data']['cart_id'])) {
		    $oldItemId = $item['original_product_data']['cart_id'];
	    }
	    if ( empty( $oldItemId ) ) {
		    continue;
	    }

        // The cart/order item id changed (probably because domain changed), we should update it in our tables as well
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

    return false;

}

/**
 * @param $status_to
 * @param $status_from
 * @param $orderInfo
 * @param $force_notification
 * @param $order_statuses
 * @param $place_order
 *
 * @throws Exception
 */
function fn_adls_change_order_status($status_to, $status_from, $orderInfo, $force_notification, $order_statuses, $place_order)
{
	fn_adls_process_order($orderInfo, $status_to, $status_from);
}

function fn_adls_delete_order($orderId)
{
    $manager = LicenseManager::instance();
    $licenseRepository = LicenseRepository::instance();
    $licenses = $manager->getOrderLicenses($orderId);
	$releaseLinkRepository = ReleaseLinkRepository::instance();
	/** @var License $license */
	foreach ($licenses as $license) {
		if ( ! empty( $license->getUserId() ) && ! empty( $license->getId() ) ) {
			list ($links, ) = $releaseLinkRepository->find( array(
				'userId' => $license->getUserId(),
				'licenseId' => $license->getId(),
			));
			foreach ( $links as $link ) {
				$releaseLinkRepository->removeLink( $link );
			}
		}

        $licenseRepository->delete($license->getId());
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

/**
 * @param $orderInfo
 * @param $orderStatus
 * @param null $statusFrom
 *
 * @return bool
 * @throws Exception
 */
function fn_adls_process_order($orderInfo, $orderStatus, $statusFrom = null)
{
    if (defined('ORDER_MANAGEMENT')) {
        return false;
    }

    $manager = LicenseManager::instance();
	$licenseRepository = LicenseRepository::instance();
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

	    if ( ! empty( $product['subscription'] ) && !$product['subscription']->isNew()) {
		    $license = $licenseRepository->findOneBySubscription($product['subscription']);
		    $licenseId = $license->getId();
	    } else {
		    $licenseId = $manager->existsLicense($productId, $itemId, $orderId, $userId);
	    }

//	    $freeSubscription = \HeloStore\ADLS\ProductManager::instance()->isFreeSubscription($storeProduct['adls_subscription_id']);
//	    $paidSubscription = \HeloStore\ADLS\ProductManager::instance()->isPaidSubscription($storeProduct['adls_subscription_id']);
	    // @TODO if it's a free product, don't create license

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

                // @TODO move this into an option per product, eg. "This product generates license keys"
                // if is sidekick, don't generate license
                $isSidekick = ($productId == 5);
                $hasSubscription = ! empty($product['subscription']) || ! empty($orderInfo['adls_subscription_setup_pending']);

	            if (!$isSidekick && $hasSubscription ) {
		            $licenseId = $manager->createLicense($productId, $itemId, $orderId, $userId);
		            if ($licenseId) {
			            fn_set_notification('N', __('notice'), __('adls.order_licenses_created'), $notificationState);

			            Utils::updateLicenseDomainsFromProductOptions($licenseId, $domainOptions);
		            } else {
			            $success = false;
			            $errors += $manager->getErrors();
		            }
	            }
            }
            // If it's not a subscription-based product, but license-based
            $hasNoSubscription = empty( $product['subscription'] ) && empty($orderInfo['adls_subscription_setup_pending']);
	        if ( $hasNoSubscription ) {
		        ReleaseManager::instance()->addUserLinks(
			        $userId,
			        $productId,
			        $licenseId
		        );
	        }

        } else {
            if (!defined('ORDER_MANAGEMENT')) {
	            $manager->doDisableLicense( $licenseId );
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

/**
 * @param Subscription $subscription
 * @param $product
 * @param $orderInfo
 *
 * @throws Exception
 */
function fn_adls_adls_subscriptions_post_begin(Subscription $subscription, $product, $orderInfo)
{
    // Assign most recent product release to subscription, to be used when querying for subscription's releases because the latest release might be out of subscriptions start/end range.
    if (!$subscription->getProductId()) {
        return;
    }
	$subscriptionLicenseId = $subscription->getLicenseId();

	$license = $subscription->getLicense();
	if ( empty( $license ) ) {
		$license = LicenseRepository::instance()->findOneBySubscription( $subscription );
	}
	// On migration, the license already exists, but it's not yet assigned to the newly created subscription
	if ( empty( $license ) && !empty($product['license'])) {
		$license = $product['license'];
	}

	if ( !empty( $license ) && empty( $subscriptionLicenseId ) ) {
		$subscription->setLicense( $license );
		SubscriptionRepository::instance()->update( $subscription );
	}

	if ( empty( $license ) ) {
		throw new \Exception( 'Could not find license for subscription #' . $subscription->getId() );
	}

	ReleaseManager::instance()->addUserLinks(
		$subscription->getUserId(),
		$subscription->getProductId(),
		$license->getId(),
		$subscription->getId(),
		$subscription->getStartDate(),
		$subscription->getEndDate()
	);
}

/**
 * @param Subscription $subscription
 * @param $product
 * @param $orderInfo
 *
 * @throws Exception
 */
function fn_adls_adls_subscriptions_post_resume(Subscription $subscription, $product, $orderInfo)
{
	fn_adls_adls_subscriptions_post_begin( $subscription, $product, $orderInfo );
}

function fn_adls_adls_subscriptions_post_suspend(Subscription $subscription)
{
	$license = LicenseRepository::instance()->findOneBySubscription( $subscription );
	if ( ! empty( $license ) ) {
//		list ($links, ) = ReleaseLinkRepository::instance()->find( array(
//			'userId' => $subscription->getUserId(),
//			'licenseId' => $license->getId(),
//			'subscriptionId' => $subscription->getId(),
//		));
//		foreach ( $links as $link ) {
//			ReleaseLinkRepository::instance()->removeLink( $link );
//		}
		LicenseManager::instance()->doDisableLicense( $license->getId() );
	}
}

function fn_adls_adls_subscriptions_post_fail(Subscription $subscription, $product, $orderInfo)
{
	fn_adls_adls_subscriptions_post_suspend($subscription);
}

function fn_adls_adlss_delete_subscription(Subscription $subscription ) {
	list ($links, ) = ReleaseLinkRepository::instance()->find( array(
		'userId' => $subscription->getUserId(),
		'subscriptionId' => $subscription->getId(),
	));

	foreach ( $links as $link ) {
		ReleaseLinkRepository::instance()->removeLink( $link );
	}
}
function fn_adls_adlss_get_subscriptions_post(&$items , $params ) {

	if ( empty( $params['extended'] ) ) {
		return;
	}

	/** @var Subscription $subscription */
	foreach ( $items as $subscription ) {
		$domains = $subscription->getExtra( 'license$domains' );
		if ( ! empty( $domains ) ) {
			$domains = explode( ',', $domains );
		} else {
			$domains = array();
		}
		$licenseId = $subscription->getLicenseId();
//		if ( empty( $subscription->getExtra( 'license$id' ) ) ) {
		if ( empty( $licenseId ) ) {
			// This is a newly created subscription, so it's normal to not have a license attached to it yet
			if ( $subscription->isNew() ) {
				continue;
			}
			throw new \Exception( 'Subscription has no license ID (subscription #' . $subscription->getId() . ')' );
		}

		$data = array(
			'id'         => $licenseId,
			'domains'    => $domains,
			'licenseKey' => $subscription->getExtra( 'license$licenseKey' ),
			'status'     => $subscription->getExtra( 'license$status' )
		);

		$license = new License( $data );
		$subscription->setLicense( $license );
	}
}
function fn_adls_adlss_get_subscriptions( &$fields, $table, &$joins, $condition, $sorting, $limit, $params ) {

	if (isset($params['extended'])) {
		// Grab license details
		$joins[] = db_quote('
			LEFT JOIN ?:adls_licenses AS license 
				ON license.orderId = subscription.orderId 
				AND license.userId = subscription.userId
				AND license.productId = subscription.productId
				');
		// @TODO: should have condition to join by `AND license.orderItemId = subscription.itemId`, but the item IDs get desync'ed for some reason (same item ends up with different IDs)
		$fields[] = 'license.licenseKey AS license$licenseKey';
		$fields[] = 'license.status AS license$status';
		$fields[] = 'license.id AS license$id';
		// Grab license domains details
		$joins[] = db_quote('
			LEFT JOIN ?:adls_license_domains AS domains 
				ON domains.licenseId = license.id
				AND domains.name <> ""
				');
		$fields[] = 'GROUP_CONCAT(domains.name) AS license$domains';
	}
}

function fn_adls_format_size($bytes, $precision = 2) {
	return Utils::instance()->toByteString($bytes, $precision);
}

/**
 * @param $productId
 * @param $wasDeleted
 */
function fn_adls_delete_product_post($productId, $wasDeleted) {
	if ( ! empty( $productId ) && true == $wasDeleted ) {
		list ( $releases, ) = ReleaseRepository::instance()->findByProductId($productId);
		if ( ! empty( $releases ) ) {
			foreach ( $releases as $release ) {
				ReleaseRepository::instance()->deleteById($release->getId());
			}
			// @TODO disable licenses, subscriptions, and everything else linked to this product
		}
	}

}

/**
 * @param $product
 * @param $auth
 * @param $params
 */
function fn_adls_gather_additional_product_data_post(&$product, $auth, $params) {

	if ( AREA != 'C' ) {
		return;
	}
	$releaseCount = ReleaseRepository::instance()->countByProductId($product['product_id']);
	if ( empty( $releaseCount ) ) {

//		$product['out_of_stock_actions'] = 'S';
//		$product['tracking'] = 'B';
//		$product['amount'] = 0;
		$product['price'] = 0;
		$product['zero_price_action'] = 'R';
		$product['full_description'] .= '<h2>This product has not been released yet.</h2>';
	}
}

/**
 * @param $product
 * @param $auth
 * @param $preview
 * @param $lang_code
 */
function fn_adls_get_product_data_post(&$product, $auth, $preview, $lang_code) {
    $productId = $product['product_id'];
    $product['compatibility'] = \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->findMinMax($productId);
}
