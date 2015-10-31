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

class LicenseManager extends Singleton
{
	public function createLicense($productId, $orderId, $userId)
	{
		$exists = db_get_field('SELECT license_id FROM ?:adls_licenses WHERE product_id = ?i AND order_id = ?i and user_id = ?i', $productId, $orderId, $userId);
		if ($exists) {
			$this->addError('license_already_attached_to_order');
			return false;
		}

		$key = $this->generateUniqueKey();

		if ($key == false) {
			$this->addError('license_uniqueness_generating_failure');
			return false;
		}
		$now = date("Y-m-d H:i:s", TIME);

		$license = array(
			'product_id' => $productId,
			'order_id' => $orderId,
			'user_id' => $userId,
			'created_at' => $now,
			'updated_at' => $now,
			'license_key' => $key,
			'status' => License::STATUS_ACTIVE,
		);

		$result = db_query('INSERT INTO ?:adls_licenses ?e', $license);

		return $result;
	}

	public function deleteLicense($licenseId)
	{
		return db_query('DELETE FROM ?:adls_licenses WHERE license_id = ?i', $licenseId);
	}

	public function getLicenseByKey($key) {
		return db_get_row('SELECT * FROM ?:adls_licenses WHERE license_key = ?i', $key);

	}
	public function generateUniqueKey() {
		static $tries = 0;
		$maxTries = 10;
		while ($tries < $maxTries) {
			$tries++;
			$candidate = Utils::generateKey();
			$exists = $this->getLicenseByKey($candidate);
			if (empty($exists)) {
				return $candidate;
			}
		}

		return false;
	}

	public function updateLicenseDomains($licenseId, $domains)
	{
		foreach ($domains as $domain) {
			$entry = $this->getDomainByLicenseId($licenseId, $domain);
			if (empty($entry)) {
				$entry = array(
					'license_id' => $licenseId,
					'domain' => $domain,
					'created_at' =>  date("Y-m-d H:i:s", TIME)
				);
			}
			$entry['updated_at'] =  date("Y-m-d H:i:s", TIME);
			if (empty($entry['domain_id'])) {
				db_query('INSERT INTO ?:adls_license_domains ?e', $entry);
			} else {
				db_query('UPDATE ?:adls_license_domains SET ?u WHERE domain_id = ?i', $entry, $entry['domain_id']);
			}
		}

		return true;
	}

	public function getDomainByLicenseId($licenseId, $domain)
	{
		return db_get_row('SELECT * FROM ?:adls_license_domains WHERE license_id = ?i AND domain = ?s', $licenseId, $domain);
	}

}