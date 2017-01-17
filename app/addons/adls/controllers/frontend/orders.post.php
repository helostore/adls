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
use HeloStore\ADLS\Utils;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'details') {

        if (!empty($_REQUEST['order_id']) && !empty($_REQUEST['licenses'])) {
            $orderChanged = false;

            $orderId = intval($_REQUEST['order_id']);
            $licenseManager = LicenseManager::instance();
            $licenses = $licenseManager->getOrderLicenses($orderId);
            $requestLicenses = is_array($_REQUEST['licenses']) ? $_REQUEST['licenses'] : array();
            $requestLicensesIds = array_keys($requestLicenses);

            foreach ($licenses as $license) {
                $licenseId = $license['license_id'];
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
                    $domainId = $domain['domain_id'];
                    if (!isset($requestLicense['domains'][$domainId])) {
                        continue;
                    }

                    $newDomainValue = $requestLicense['domains'][$domainId];
                    $newDomainValue = trim($newDomainValue);
                    $oldDomainValue = $domain['name'];
                    if ($newDomainValue == $oldDomainValue) {
                        continue;
                    }

                    if ($domain['status'] == License::STATUS_DISABLED) {
                        $message = __('adls.order_license_domain_update_failed_license_is_disabled', array('[domain]' => $newDomainValue));
                        foreach ($result as $value) {
                            $message .= '<br> - ' . $value;
                        }
                        fn_set_notification('E', __('error'), $message, 'K');
                        continue;
                    }

                    $result = Utils::validateHostname($newDomainValue, $domain['type']);
                    if ($result !== true) {
                        $message = __('adls.order_license_domain_update_failed', array('[domain]' => $newDomainValue));
                        foreach ($result as $value) {
                            $message .= '<br> - ' . $value;
                        }
                        fn_set_notification('E', __('error'), $message, 'K');
                        continue;
                    }


                    $updates = array(
                        'name' => $newDomainValue
                    );
                    $licenseManager->inactivateLicense($licenseId, $oldDomainValue);
                    if (!empty($updates)) {
                        $query = db_quote('UPDATE ?:adls_license_domains SET ?u WHERE license_id = ?i AND domain_id = ?i', $updates, $licenseId, $domainId);
                        db_query($query);

                        if ($domain['type'] == \HeloStore\ADLS\License::DOMAIN_TYPE_PRODUCTION) {
                            $langVar = 'adls.order_license_production_domain_updated';
                        } else {
                            $langVar = 'adls.order_license_development_domain_updated';
                        }

                        $message = __($langVar, array('[old]' => $oldDomainValue, '[new]' => $newDomainValue));
                        fn_set_notification('N', __('notice'), $message, 'K');

                        // update product option value as well
//                        $domain = $licenseManager->getDomainBy(array(
//                            'license_id' => $licenseId,
//                            'domain_id' => $domainId,
//                        ));


                        $extra = db_get_field('SELECT extra FROM ?:order_details WHERE item_id = ?s AND order_id = ?i AND product_id = ?i',
                            $license['order_item_id'],
                            $license['order_id'],
                            $license['product_id']
                        );
                        $productOptionId = $domain['product_option_id'];
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
                                    $license['order_item_id'],
                                    $license['order_id'],
                                    $license['product_id']
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
