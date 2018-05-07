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

/**
 * Class LicenseManager
 *
 * @package HeloStore\ADLS
 */
class LicenseManager extends Manager
{
	/**
	 * @var LicenseRepository
	 */
	protected $repository;

	/**
	 * LicenseManager constructor.
	 */
	public function __construct()
	{
		$this->setRepository(LicenseRepository::instance());
	}

	public function existsLicense($productId, $itemId, $orderId, $userId)
	{
		return db_get_field('SELECT id FROM ?:adls_licenses WHERE productId = ?i AND orderItemId = ?i AND orderId = ?i and userId = ?i', $productId, $itemId, $orderId, $userId);
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
			$this->addError('license_uniqueness_generating_failure' . 'pid: ' . $productId . ', oid: ' . $orderId . 'oiid: ' . $itemId);
			return false;
		}
		$now = date("Y-m-d H:i:s", TIME);

		$license = array(
			'productId' => $productId,
			'orderItemId' => $itemId,
			'orderId' => $orderId,
			'userId' => $userId,
			'createdAt' => $now,
			'updatedAt' => $now,
			'licenseKey' => $key,
			'status' => License::STATUS_INACTIVE,
		);

		$result = db_query('INSERT INTO ?:adls_licenses ?e', $license);

		return $result;
	}

	/**
	 * Create unique license key
	 *
	 * @return bool|string
	 */
	public function generateUniqueKey() {
		$tries = 0;
		$maxTries = 10;
		while ($tries < $maxTries) {
			$tries++;
			$candidate = Utils::generateKey();
			$exists = $this->repository->findOneByKey($candidate);
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
			if (!empty($domain['productOptionId'])) {
				$entry = $this->getDomainByOptionId($licenseId, $domain['productOptionId']);
				$productOptionId = $domain['productOptionId'];
			} else {
				$entry = $this->getDomainByType($licenseId, $domain['type']);
			}
			$domainChanged = false;
			if (empty($entry)) {
				$entry = array(
					'licenseId' => $licenseId,
					'createdAt' =>  date("Y-m-d H:i:s", TIME),
					'type' => $domain['type'],
					'productOptionId' => $productOptionId,
					'status' => License::STATUS_INACTIVE
				);
			} else {
				if ($entry['name'] != $domain['name']) {
					$domainChanged = true;
				}
			}
			$entry['name'] = $domain['name'];
			$entry['updatedAt'] = date("Y-m-d H:i:s", TIME);
			if (empty($entry['id'])) {
				db_query('INSERT INTO ?:adls_license_domains ?e', $entry);
			} else {
				db_query('UPDATE ?:adls_license_domains SET ?u WHERE id = ?i', $entry, $entry['id']);
			}

			if ($domainChanged) {
				if ($this->inactivateLicense($licenseId, $entry['name'])) {
					fn_set_notification('N', __('notice'), __('adls.order_licenses_inactivated'), 'K');
				}
			}
		}

		return true;
	}

    public function getDomainVariations($domainName)
    {
        $variations = array();
        $variations[] = $domainName;
        if (substr($domainName, 0, 4) === 'www.') {
            $variations[] = substr($domainName, 4);
        } else {
            $variations[] = 'www.' . $domainName;
        }

        return $variations;
    }
    public function isValidLicenseDomain($licenseId, $domainName)
    {
        $candidates = $this->getDomainVariations($domainName);
        $licensedDomains = $this->getDomainBy(array('licenseId' => $licenseId, 'domains' => $candidates));

        return !empty($licensedDomains);
    }
	public function getDomainByType($licenseId, $type)
	{
		return $this->getDomainBy(array('licenseId' => $licenseId, 'type' => $type));
	}
	public function getDomainByOptionId($licenseId, $optionId)
	{
		return $this->getDomainBy(array('licenseId' => $licenseId, 'productOptionId' => $optionId));
	}
	public function getDomainBy($params)
	{
		$conditions = array();
		if (!empty($params['licenseId'])) {
			$conditions[] = db_quote('licenseId = ?s', $params['licenseId']);
		}
		if (!empty($params['id'])) {
			$conditions[] = db_quote('id = ?i', $params['id']);
		}
		if (!empty($params['domain'])) {
			$conditions[] = db_quote('name = ?s', $params['domain']);
		}
        if (!empty($params['domains'])) {
            $conditions[] = db_quote('name IN (?a)', $params['domains']);
        }
		if (!empty($params['type'])) {
			$conditions[] = db_quote('type = ?s', $params['type']);
		}
		if (!empty($params['productOptionId'])) {
			$conditions[] = db_quote('productOptionId = ?s', $params['productOptionId']);
		}
		$conditions = !empty($conditions) ? ' AND ' . implode(' AND ', $conditions) : '';
		$query = db_quote('SELECT * FROM ?:adls_license_domains WHERE 1 ?p', $conditions);

		return db_get_row($query);
	}

	public function getLicenses($params)
	{
		$conditions = array();
		$joins = array();

		if (!empty($params['id'])) {
			$conditions[] = db_quote('al.id = ?s', $params['id']);
		}

		if (!empty($params['domain'])) {
			$joins[] = db_quote('LEFT JOIN ?:adls_license_domains AS ald ON ald.licenseId = al.id');
			$conditions[] = db_quote('ald.name = ?s', $params['domain']);
		}

//		if (!empty($params['license'])) {
//			$joins[] = db_quote('LEFT JOIN ?:users AS u ON u.license_id = al.license_id');
//			$conditions[] = db_quote('ald.name = ?s', $params['domain']);
//		}

		if (!empty($params['product'])) {
			$joins[] = db_quote('LEFT JOIN ?:products AS p ON p.product_id = al.productId');
			$conditions[] = db_quote('p.adls_addon_id = ?s', $params['product']);
		}
		if (!empty($params['productId'])) {
			$conditions[] = db_quote('al.productId = ?i', $params['productId']);
		}
		if (!empty($params['orderItemId'])) {
			$conditions[] = db_quote('al.orderItemId = ?s', $params['orderItemId']);
		}
		if (!empty($params['orderId'])) {
			$conditions[] = db_quote('al.orderId = ?i', $params['orderId']);
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
				$item = new License( $item );
				if (!empty($item)) {
					$item->setDomains($this->getLicenseDomains($item->getId()));
				}

				if (!empty($item->hasDomains())) {
					$disabled = 0;
					foreach ($item->getDomains() as $domain) {
						if ($domain['status'] == License::STATUS_DISABLED) {
							$disabled++;
						}
					}
					if ($disabled == count($item->getDomains())) {
						$item->setAllDomainsDisabled(true);
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
			'orderId' => $orderId,
		);
		$license = $this->getLicenses($params);

		return $license;
	}
	public function getOrderLicense($orderId, $itemId)
	{
		$params = array(
			'orderId' => $orderId,
			'orderItemId' => $itemId,
			'get_domains' => true,
			'single' => true
		);
		$license = $this->getLicenses($params);

		return $license;
	}

	public function getLicense($licenseId)
	{
		$params = array(
			'id' => $licenseId,
			'get_domains' => true,
			'single' => true
		);
		$license = $this->getLicenses($params);

		return $license;
	}

	public function getLicenseDomains($licenseId)
	{
		$domains = db_get_hash_array('SELECT * FROM ?:adls_license_domains WHERE licenseId = ?i ORDER BY id', 'id', $licenseId);

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
			$result = db_get_field('SELECT status FROM  ?:adls_license_domains WHERE licenseId = ?i AND name = ?s', $licenseId, $domain);
		} else {
			$result = db_get_field('SELECT status FROM ?:adls_licenses WHERE id = ?i', $licenseId);
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
	public function changeLicenseStatus($licenseId, $status, $domain = null)
	{
		if ($domain !== null) {
            $candidates = $this->getDomainVariations($domain);
            $licensedDomain = $this->getDomainBy(array('licenseId' => $licenseId, 'domains' => $candidates));
            if ( ! empty($licensedDomain)) {
                if (!$this->updateLicenseDomainStatus($licensedDomain['id'], $status)) {
//                    return false;
                }
            }
		}

        return $this->updateLicenseStatus($licenseId, $status);
	}

    public function updateLicenseStatus($licenseId, $status)
    {
        db_query('UPDATE ?:adls_licenses SET status = ?s WHERE id = ?i', $status, $licenseId);

        return true;
    }

    public function updateLicenseDomainStatus($domainId, $status)
    {
        return db_query('UPDATE ?:adls_license_domains SET status = ?s WHERE id = ?i', $status, $domainId);
    }

	public function doDisableLicense( $licenseId ) {

		$domains = $this->getLicenseDomains($licenseId);

		if (!empty($domains)) {
			$success = true;
			foreach ($domains as $domain) {
				if (!$this->disableLicense($licenseId, $domain['name'])) {
					$success = false;
				}
			}
			return $success;

		} else {
			if ($this->disableLicense($licenseId)) {
				return true;
			}
		}

		return false;
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

    public function getLicenseStatuses()
    {
        return array(
            License::STATUS_ACTIVE => License::convertStatusToLabel(License::STATUS_ACTIVE),
            License::STATUS_INACTIVE => License::convertStatusToLabel(License::STATUS_INACTIVE),
            License::STATUS_DISABLED => License::convertStatusToLabel(License::STATUS_DISABLED),
        );
    }
}
