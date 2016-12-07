<?php

use HeloStore\ADLS\License;
use HeloStore\ADLS\Utils;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'options') {

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('adls_options_changed_in_cart', true);
    }

    if (!empty($_REQUEST['product_data'])) {

    } else {
        $changedOption = $_REQUEST['changed_option'];
        foreach ($_REQUEST['cart_products'] as $itemId => $item) {
            if (!isset($changedOption[$itemId])) {
                continue;
            }
            $changedOptionId = $changedOption[$itemId];
            foreach ($item['product_options'] as $optionId => $optionValue) {

                $option = db_get_row("SELECT * FROM ?:product_options WHERE option_id = ?i", $optionId);

                if (!fn_adls_is_product_option_domain($option)) {
                    continue;
                }
                $domainType = License::DOMAIN_TYPE_DEVELOPMENT;
                if ($option['adls_option_type'] == 'domain') {
                    $domainType = License::DOMAIN_TYPE_PRODUCTION;
                }

                $result = Utils::validateHostname($optionValue, $domainType);
                if ($result !== true) {
                    $message = __('adls.order_license_domain_update_failed', array('[domain]' => $optionValue));
                    foreach ($result as $value) {
                        $message .= '<br> - ' . $value;
                    }
                    fn_set_notification('E', __('error'), $message, 'K');

                    // restore previous value
                    $prevValue = '';
                    if (!empty($_SESSION['cart']['products'][$itemId])) {
                        $prevValue = $_SESSION['cart']['products'][$itemId]['product_options'][$optionId];

                    }
                    $_REQUEST['cart_products'][$itemId]['product_options'][$optionId] = $prevValue;
                }

            }
        }

    }
}