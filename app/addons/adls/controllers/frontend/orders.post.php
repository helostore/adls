<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use HeloStore\ADLS\License;
use HeloStore\ADLS\LicenseManager;
use HeloStore\ADLS\Release;
use HeloStore\ADLS\Utils;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'details') {

        if (!empty($_REQUEST['order_id']) && !empty($_REQUEST['licenses'])) {
            $orderChanged = false;

            $orderId = intval($_REQUEST['order_id']);
            $licenseManager = LicenseManager::instance();
            $licenseRepository = \HeloStore\ADLS\LicenseRepository::instance();
            $licenses = $licenseManager->getOrderLicenses($orderId);
            $requestLicenses = is_array($_REQUEST['licenses']) ? $_REQUEST['licenses'] : array();
            $requestLicensesIds = array_keys($requestLicenses);

            /** @var License $license */
            foreach ($licenses as $license) {
                $licenseId = $license->getId();
                if (!in_array($licenseId, $requestLicensesIds)) {
                    continue;
                }

                // update domains
                $requestLicense = $requestLicenses[$licenseId];

                if (empty($requestLicense)) {
                    continue;
                }

                if (empty($requestLicense['domains'])) {
                    continue;
                }
                $currentDomains = $licenseManager->getLicenseDomains($licenseId);

                foreach ($currentDomains as $domain) {
                    $domainId = $domain['id'];
                    if (!isset($requestLicense['domains'][$domainId])) {
                        continue;
                    }

                    $newDomainValue = $requestLicense['domains'][$domainId];
                    $newDomainValue = trim($newDomainValue);
                    $oldDomainValue = $domain['name'];
                    if ($newDomainValue == $oldDomainValue) {
                        continue;
                    }

//                    // User wants to delete a domain, so delete the corresponding database entry as well.
//                    if (empty($newDomainValue)) {
//                        $licenseRepository->deleteDomainById($domainId);
//                        continue;
//                    }

                    if ($domain['status'] == License::STATUS_DISABLED) {
                        $message = __('adls.order_license_domain_update_failed_license_is_disabled', array('[domain]' => $newDomainValue));
//                        foreach ($result as $value) {
//                            $message .= '<br> - ' . $value;
//                        }
                        fn_set_notification('E', __('error'), $message, 'K');
                        continue;
                    }

                    if (!empty($newDomainValue)) {
                        $result = Utils::validateHostname($newDomainValue, $domain['type']);
                        if ($result !== true) {
                            $message = __('adls.order_license_domain_update_failed', array('[domain]' => $newDomainValue));
                            foreach ($result as $value) {
                                $message .= '<br> - ' . $value;
                            }
                            fn_set_notification('E', __('error'), $message, 'I');
                            continue;
                        }
                    }

                    $updates = array(
                        'name' => $newDomainValue
                    );

                    $licenseManager->inactivateLicense($licenseId, $oldDomainValue);
                    if (!empty($updates)) {
                        $query = db_quote('UPDATE ?:adls_license_domains SET ?u WHERE licenseId = ?i AND id = ?i', $updates, $licenseId, $domainId);
                        db_query($query);

                        if ($domain['type'] == \HeloStore\ADLS\License::DOMAIN_TYPE_PRODUCTION) {
                            $langVar = 'adls.order_license_production_domain_updated';
                        } else {
                            $langVar = 'adls.order_license_development_domain_updated';
                        }
                        $oldDomainValue = empty($oldDomainValue) ? "nothing" : $oldDomainValue;
                        $newDomainValue = empty($newDomainValue) ? "nothing" : $newDomainValue;
                        $message = __($langVar, array('[old]' => $oldDomainValue, '[new]' => $newDomainValue));
                        fn_set_notification('N', __('notice'), $message, 'K');

                        // update product option value as well
//                        $domain = $licenseManager->getDomainBy(array(
//                            'license_id' => $licenseId,
//                            'domain_id' => $domainId,
//                        ));


                        $extra = db_get_field('SELECT extra FROM ?:order_details WHERE item_id = ?s AND order_id = ?i AND product_id = ?i',
                            $license->getOrderItemId(),
                            $license->getOrderId(),
                            $license->getProductId()
                        );
                        $productOptionId = $domain['productOptionId'];
                        if (!empty($extra) && !empty($productOptionId)) {
                            $extra = unserialize($extra);
                            if (!empty($extra) && is_array($extra)) {
                                $extra['product_options'][$productOptionId] = $newDomainValue;
                                foreach ($extra['product_options_value'] as $k => $extraOption) {
                                    if ($extraOption['option_id'] == $productOptionId) {
                                        $extra['product_options_value'][$k]['value'] = $newDomainValue;
                                        $extra['product_options_value'][$k]['variant_name'] = $newDomainValue;
                                    }
                                }
                                $extra = serialize($extra);
                                db_query('UPDATE ?:order_details SET extra = ?s WHERE item_id = ?i AND order_id = ?i AND product_id = ?i',
                                    $extra,
                                    $license->getOrderItemId(),
                                    $license->getOrderId(),
                                    $license->getProductId()
                                );
                                $orderChanged = true;
                            }
                        }
                    }
                }

            }

            if ($orderChanged) {
//                $order = \Tygh\Tygh::$app['view']->getTemplateVars('order_info');
            }

            return array(CONTROLLER_STATUS_OK, 'orders.details?order_id=' . $_REQUEST['order_id']);
        }
    }
}


if ($mode == 'details') {
    $order = \Tygh\Tygh::$app['view']->getTemplateVars('order_info');

    // We postpone populating the releases up to this point because CS-Cart offers us no control on prioritizing hooks,
    // and the subscriptions addon's hooks are executed before this addon's hooks, but releases are depended on subscriptions!
    // So to avoid duplicating queries, we postpone releases up to this point.
    $releaseRepository = \HeloStore\ADLS\ReleaseRepository::instance();
    $releaseManager = \HeloStore\ADLS\ReleaseManager::instance();


	$params = array();
    $params['status'] = array();
	if ( ! empty( $auth ) && !empty($auth['release_status'])) {
		$params['status'] = $auth['release_status'];
	}
    $params['status'][] = Release::STATUS_PRODUCTION;

    if (!empty($order) && !empty($order['products'])) {
        $changed = false;
        foreach ($order['products'] as &$product) {
            if (!fn_is_adls_product($product)) {

                continue;
            }
            $product['releases'] = $releaseManager->getOrderItemReleases($order['user_id'], $product, $params);
            $releaseManager->checkFileIntegrity($product['releases']);
            $changed = true;
        }
        unset($product);
        if ($changed) {
            \Tygh\Tygh::$app['view']->assign('order_info', $order);
        }
    }
}
