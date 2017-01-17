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

use Tygh\Mailer;
use Tygh\Pdf;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    return array(CONTROLLER_STATUS_OK, 'orders.manage');
}

$params = $_REQUEST;

if ($mode == 'details') {
    $view = &Tygh::$app['view'];

    Registry::set('navigation.tabs.adls_licenses', array (
        'title' => __('adls.licenses'),
        'js' => true
    ));

    $orderInfo = $view->getTemplateVars('order_info');
    if (empty($orderInfo)) {
        return;
    }
    $orderId = $orderInfo['order_id'];

    $licenseManager = \HeloStore\ADLS\LicenseManager::instance();
    $licenses = $licenseManager->getOrderLicenses($orderId);
}