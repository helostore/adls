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
namespace HeloStore\ADLS;

use Tygh\Registry;

class SubscriptionManager extends Singleton
{
	/**
	 * Create new subscription
	 *
	 * @param $licenseId
	 * @param $userId
	 * @param $params
	 * @return bool|int
	 */
	public function create($licenseId, $userId, $params)
	{
		$date = new \DateTime();
		$startDate = clone $date;
		$endDate = clone $date;
		$type = Subscription::TYPE_YEARLY;

		$neverExpires = 0;

		if ($type == Subscription::TYPE_YEARLY) {
			$endDate->modify('+1 year');
		} else if ($type == Subscription::TYPE_MONTHLY) {
			$endDate->modify('+1 month');
		} else if ($type == Subscription::TYPE_MONTHLY) {
			$endDate = null;
			$neverExpires = true;
		} else {
			$endDate->modify('+15 days');
		}

		$data = array(
			'license_id' => $licenseId,
			'user_id' => $userId,
			'type' => $type,
			'start_date' => $startDate->format('Y-m-d H:i:s'),
			'end_date' => $endDate->format('Y-m-d H:i:s'),
			'never_expires' => $neverExpires,
			'created_at' => $date->format('Y-m-d H:i:s'),
			'update_at' => $date->format('Y-m-d H:i:s'),
		);

		$subscriptionId = db_query('INSERT INTO ?:adls_subscriptions ?e', $data);

		return $subscriptionId;
	}
}