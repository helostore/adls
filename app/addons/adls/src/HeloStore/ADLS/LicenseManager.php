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
	public function existsLicense($productId, $itemId, $orderId, $userId)
	{
		return db_get_field('SELECT license_id FROM ?:adls_licenses WHERE product_id = ?i AND order_item_id = ?i AND order_id = ?i and user_id = ?i', $productId, $itemId, $orderId, $userId);
	}
	public function createLicense($productId, $itemId, $orderId, $userId)
	{
		$licenseId = $this->existsLicense($productId, $itemId, $orderId, $userId);
		if (!empty($licenseId)) {
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
			'order_item_id' => $itemId,
			'order_id' => $orderId,
			'user_id' => $userId,
			'created_at' => $now,
			'updated_at' => $now,
			'license_key' => $key,
			'status' => License::STATUS_INACTIVE,
		);

		$result = db_query('INSERT INTO ?:adls_licenses ?e', $license);

		return $result;
	}

	/**
	 * @deprecated
	 * @return bool
	 */

	/*
	public function updateDomain()
	{
		$licenseId = $this->existsLicense($productId, $itemId, $orderId, $userId);
		if (!empty($licenseId)) {
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
			'order_item_id' => $itemId,
			'order_id' => $orderId,
			'user_id' => $userId,
			'created_at' => $now,
			'updated_at' => $now,
			'license_key' => $key,
			'status' => License::STATUS_INACTIVE,
		);

		$result = db_query('INSERT INTO ?:adls_licenses ?e', $license);

		return $result;
	}
	*/


	public function deleteLicense($licenseId)
	{
		db_query('DELETE FROM ?:adls_license_domains WHERE license_id = ?i', $licenseId);

		return db_query('DELETE FROM ?:adls_licenses WHERE license_id = ?i', $licenseId);
	}

	public function getLicenseByKey($key) {
		return db_get_row('SELECT * FROM ?:adls_licenses WHERE license_key = ?s', $key);
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
			$productOptionId = null;
			if (!empty($domain['product_option_id'])) {
				$entry = $this->getDomainByOptionId($licenseId, $domain['product_option_id']);
				$productOptionId = $domain['product_option_id'];
			} else {
				$entry = $this->getDomainByType($licenseId, $domain['type']);
			}
			$domainChanged = false;
			if (empty($entry)) {
				$entry = array(
					'license_id' => $licenseId,
					'created_at' =>  date("Y-m-d H:i:s", TIME),
					'type' => $domain['type'],
					'product_option_id' => $productOptionId,
					'status' => License::STATUS_INACTIVE
				);
			} else {
				if ($entry['name'] != $domain['name']) {
					$domainChanged = true;
				}
			}
			$entry['name'] = $domain['name'];
			$entry['updated_at'] = date("Y-m-d H:i:s", TIME);
			if (empty($entry['domain_id'])) {
				db_query('INSERT INTO ?:adls_license_domains ?e', $entry);
			} else {
				db_query('UPDATE ?:adls_license_domains SET ?u WHERE domain_id = ?i', $entry, $entry['domain_id']);
			}

			if ($domainChanged) {
				if ($this->inactivateLicense($licenseId, $entry['name'])) {
					fn_set_notification('N', __('notice'), __('adls.order_licenses_inactivated'), 'K');
				}
			}
		}

		return true;
	}

	public function getDomainByType($licenseId, $type)
	{
		return $this->getDomainBy(array('license_id' => $licenseId, 'type' => $type));
	}
	public function getDomainByOptionId($licenseId, $option_id)
	{
		return $this->getDomainBy(array('license_id' => $licenseId, 'product_option_id' => $option_id));
	}
	public function getDomainBy($params)
	{
		$conditions = array();
		if (!empty($params['license_id'])) {
			$conditions[] = db_quote('license_id = ?s', $params['license_id']);
		}
		if (!empty($params['domain_id'])) {
			$conditions[] = db_quote('domain_id = ?i', $params['domain_id']);
		}
		if (!empty($params['domain'])) {
			$conditions[] = db_quote('name = ?s', $params['domain']);
		}
		if (!empty($params['type'])) {
			$conditions[] = db_quote('type = ?s', $params['type']);
		}
		if (!empty($params['product_option_id'])) {
			$conditions[] = db_quote('product_option_id = ?s', $params['product_option_id']);
		}
		$conditions = !empty($conditions) ? ' AND ' . implode(' AND ', $conditions) : '';
		$query = db_quote('SELECT * FROM ?:adls_license_domains WHERE 1 ?p', $conditions);

		return db_get_row($query);
	}
	
	public function getLicenses($params)
	{
		$conditions = array();
		$joins = array();

		if (!empty($params['license_id'])) {
			$conditions[] = db_quote('al.license_id = ?s', $params['license_id']);
		}

		if (!empty($params['domain'])) {
			$joins[] = db_quote('LEFT JOIN ?:adls_license_domains AS ald ON ald.license_id = al.license_id');
			$conditions[] = db_quote('ald.name = ?s', $params['domain']);
		}

//		if (!empty($params['license'])) {
//			$joins[] = db_quote('LEFT JOIN ?:users AS u ON u.license_id = al.license_id');
//			$conditions[] = db_quote('ald.name = ?s', $params['domain']);
//		}

		if (!empty($params['product'])) {
			$joins[] = db_quote('LEFT JOIN ?:products AS p ON p.product_id = al.product_id');
			$conditions[] = db_quote('p.adls_addon_id = ?s', $params['product']);
		}
		if (!empty($params['product_id'])) {
			$conditions[] = db_quote('al.product_id = ?i', $params['product_id']);
		}
		if (!empty($params['order_item_id'])) {
			$conditions[] = db_quote('al.order_item_id = ?i', $params['order_item_id']);
		}
		if (!empty($params['order_id'])) {
			$conditions[] = db_quote('al.order_id = ?i', $params['order_id']);
		}

		$joins = !empty($joins) ?  implode("\n", $joins) : '';
		$conditions = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

		$query = db_quote('
			SELECT
				al.*
			FROM ?:adls_licenses AS al
			' . $joins . '
			' . $conditions . '
		');
		$items = db_get_array($query);

		if (!empty($items)) {
			foreach ($items as &$item) {
				if (!empty($item)) {
					$item['domains'] = $this->getLicenseDomains($item['license_id']);
				}

				if (!empty($item['domains'])) {
					$disabled = 0;
					foreach ($item['domains'] as $domain) {
						if ($domain['status'] == License::STATUS_DISABLED) {
							$disabled++;
						}
					}
					if ($disabled == count($item['domains'])) {
						$item['domains_disabled'] = true;
					}
				}
			}
			unset($item);
		}

		if (!empty($params['single'])) {
			$items = reset($items);
		}

		return $items;
	}

	public function getOrderLicenses($orderId)
	{
		$params = array(
			'order_id' => $orderId,
		);
		$license = $this->getLicenses($params);

		return $license;
	}
	public function getOrderLicense($orderId, $itemId)
	{
		$params = array(
			'order_id' => $orderId,
			'order_item_id' => $itemId,
			'get_domains' => true,
			'single' => true
		);
		$license = $this->getLicenses($params);

		return $license;
	}

	public function getLicense($licenseId)
	{
		$params = array(
			'license_id' => $licenseId,
			'get_domains' => true,
			'single' => true
		);
		$license = $this->getLicenses($params);

		return $license;
	}

	public function getLicenseDomains($licenseId)
	{
		$domains = db_get_hash_array('SELECT * FROM ?:adls_license_domains WHERE license_id = ?i ORDER BY domain_id', 'domain_id', $licenseId);

		return $domains;
	}

	public function isActiveLicense($licenseId, $domain = '')
	{
		$status = $this->getLicenseStatus($licenseId, $domain);

		return ($status == License::STATUS_ACTIVE);
	}
	public function getLicenseStatus($licenseId, $domain = '')
	{
		if (!empty($domain)) {
			$result = db_get_field('SELECT status FROM  ?:adls_license_domains WHERE license_id = ?i AND name = ?s', $licenseId, $domain);
		} else {
			$result = db_query('SELECT status FROM ?:adls_licenses WHERE license_id = ?i', $licenseId);
		}

		return $result;
	}

	/**
	 * Updates status of license AND domain (if provided)
	 *
	 * @param $licenseId
	 * @param $status
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function changeLicenseStatus($licenseId, $status, $domain = '')
	{
		$update = array(
			'status' => $status
		);
		$domainId = null;
		if (!empty($domain)) {
			$domainId = db_get_field('SELECT domain_id FROM ?:adls_license_domains WHERE license_id = ?i AND name = ?s', $licenseId, $domain);
			if (!empty($domainId)) {
				$result = db_query('UPDATE ?:adls_license_domains SET ?u WHERE license_id = ?i AND name = ?s', $update, $licenseId, $domain);
				if (!$result) {
					return false;
				}
			} // else wildcard license (for any domain)
		}
		$result = db_query('UPDATE ?:adls_licenses SET ?u WHERE license_id = ?i', $update, $licenseId);

		return $result;
	}
	public function disableLicense($licenseId, $domain = '')
	{
		return $this->changeLicenseStatus($licenseId, License::STATUS_DISABLED, $domain);
	}
	public function activateLicense($licenseId, $domain = '')
	{
		return $this->changeLicenseStatus($licenseId, License::STATUS_ACTIVE, $domain);
	}
	public function inactivateLicense($licenseId, $domain = '')
	{
		return $this->changeLicenseStatus($licenseId, License::STATUS_INACTIVE, $domain);
	}
}