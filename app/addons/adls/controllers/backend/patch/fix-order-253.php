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

use HeloStore\ADLS\LicenseRepository;
use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\ProductRepository;
use HeloStore\ADLS\Source\Source;
use HeloStore\ADLS\Source\SourceManager;
use HeloStore\ADLS\Utils;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * http://local.helostore.com/hsw.php?dispatch=patch.apply&patch=fix-order-253
 */
$orderId = 253;
$subscriptionId = 34;
$licenseRepository = LicenseRepository::instance();
$subscription = SubscriptionRepository::instance()->findOneById($subscriptionId);

$license = $licenseRepository->findOneBySubscription($subscription);
if ( ! empty($license)) {
    fn_print_r('A license already found, ID #' . $license->getId() . ' (patch already applied?)');
    exit;
}

$subscription->setStartDate(null);
$subscription->setEndDate(null);
SubscriptionRepository::instance()->update($subscription);
$subscription = SubscriptionRepository::instance()->findOneById($subscriptionId);

$orderInfo = fn_get_order_info($orderId);
fn_adls_process_order($orderInfo, 'P', 'O');

$license = $licenseRepository->findOneBySubscription($subscription);
fn_print_r('License created with ID #' . $license->getId());


$subscription->setLicense($license);
SubscriptionRepository::instance()->update($subscription);
\HeloStore\ADLSS\Subscription\SubscriptionManager::instance()->begin($subscription, 6);
$subscription->setPaidCycles(1);
SubscriptionRepository::instance()->update($subscription);

fn_print_r('License attached to subscription');


die('EOP');
