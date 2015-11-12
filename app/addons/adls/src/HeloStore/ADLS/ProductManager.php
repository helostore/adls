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


use HeloStore\ADLS\Addons\SchemesManager;

class ProductManager extends Singleton
{
	public function isPaidSubscription($subscriptionId)
	{
		return $subscriptionId == 2;
	}
	public function isFreeSubscription($subscriptionId)
	{
		return $subscriptionId == 1;
	}
	public function getSubscriptionPlans()
	{
		static $plans = array(
			1 => 'Free',
			2 => 'Paid',
		);

		return $plans;
	}
	public function getStoreProduct($productCode)
	{
		$products = $this->getStoreProducts();
		$product = (isset($products[$productCode]) ? $products[$productCode] : null);

		if (empty($product)) {
			return null;
		}
		$data = db_get_row('SELECT product_id, adls_addon_id, adls_subscription_id FROM ?:products WHERE adls_addon_id = ?s', $productCode);
		if (is_array($data)) {
			$product = array_merge($product, $data);
		}

		return $product;
	}
	public function getStoreProducts($params = array())
	{
		list($allItems, ) = fn_get_addons($params);
		$products = array();

		foreach ($allItems as $name => $item) {
			$scheme = SchemesManager::getSchemeExt($name);
			if (empty($scheme)) {
				continue;
			}

			if (method_exists($scheme, 'hasAuthor') && $scheme->hasAuthor(ADLS_AUTHOR_NAME)) {
				$item['version'] = $scheme->getVersion();
				$products[$name] = $item;
			}
		}
		return $products;
	}

	public function checkUpdates($customerProducts, $storeProducts, $attachUpdateInfo = false)
	{
		$updates = array();
		foreach ($customerProducts as $productCode => $customerProduct) {
			$storeProduct = !empty($storeProducts[$productCode]) ? $storeProducts[$productCode] : '';
			if (empty($storeProduct)) {
				continue;
			}

			$storeVersion = !empty($storeProduct['version']) ? $storeProduct['version'] : '';
			$customerVersion = !empty($customerProduct['version']) ? $customerProduct['version'] : '';
//			if ($productCode == 'autoimage_lite') { $storeVersion = 1; }
			// @TODO: check update compatibility with platform (CS-Cart)!!!!
			$comparison = version_compare($storeVersion, $customerVersion);

			if ($comparison === 1) {
				// our store version is newer
				$updates[$productCode] = array(
					'version' => $storeVersion,
					'code' => $productCode,
				);
				if ($attachUpdateInfo) {
				}
			} elseif ($comparison === -1) {
				// customer version is newer !? product alteration?!
			} elseif ($comparison === 0) {
				// up-to-date, just how we like it
			} else {
				continue;
			}
		}

		return $updates;
	}

	public function validateUpdateRequest(&$customerProducts)
	{
		foreach ($customerProducts as $i => $customerProduct) {
			$isUpdateAllowed = true;
			if ($isUpdateAllowed) {

			} else {
				unset($customerProducts[$i]);
			}
		}
	}
}