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

use HeloStore\ADLS\ReleaseManager;
use HeloStore\ADLS\ReleaseRepository;
use HeloStore\ADLSS\Subscribable\SubscribableRepository;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
}


if ($mode == 'download' && !empty($_REQUEST['hash'])) {
    $releaseRepository = ReleaseRepository::instance();
    $subscriptionRepository = SubscriptionRepository::instance();

    $hash = strval($_REQUEST['hash']);
	$release = ReleaseRepository::instance()->findOneByHash( $hash );
	aa( $release, 1 );
//    $orderId = intval($_REQUEST['orderId']);
//    $orderItemId = strval($_REQUEST['orderItemId']);
//    $userId = $_SERVER['auth']['user_id'];
//    $userId = !empty($auth['user_id']) ? $auth['user_id'] : 0;
//
//    $orderId = db_get_field("SELECT order_id FROM ?:orders WHERE user_id = ?i AND order_id = ?i", $userId, $orderId);
    if (empty($orderId)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $order = fn_get_order_info($orderId);
    if (empty($order)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (empty($order['products']) || empty($order['products'][$orderItemId])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    $orderItem = $order['products'][$orderItemId];
    $productId = $orderItem['product_id'];


    // Check if this product requires a subscription
    $isSubscribable = SubscribableRepository::instance()->isProductSubscribable($productId);

    if ($isSubscribable) {
        $subscription = $subscriptionRepository->findOneByOrderItem($orderId, $orderItemId);
        if (empty($subscription)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        if ($requestReleaseId == $subscription->getReleaseId()) {
            $release = $releaseRepository->findOneById($subscription->getReleaseId());
        } else {
            $release = $releaseRepository->findOneBySubscriptionAndId($subscription, $requestReleaseId);
        }
    } else {
        $release = $releaseRepository->findOneById($requestReleaseId);
    }

    if (empty($release)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    ReleaseManager::instance()->download($release);
    exit;
}