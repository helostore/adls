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


use Tygh\Addons\SchemesManager;
use Tygh\Registry;

class ProductManager extends Singleton
{
	public function getReviewUrl($key)
	{
		static $map = array(
			'company' => 'http://marketplace.cs-cart.com/vlad-sergiu-valentin-pfa.html?selected_section=discussion#discussion',
			'developer' => 'http://marketplace.cs-cart.com/add-ons/integrations/developer.html?selected_section=discussion#discussion',
			'free_shipping_notice' => 'http://marketplace.cs-cart.com/add-ons/customer-experience/free-shipping-incentive-add-on-for-cs-cart.html?selected_section=discussion#discussion',
			'autoimage_lite' => 'http://marketplace.cs-cart.com/add-ons/customer-experience/autoimage-lite.html?selected_section=discussion#discussion',
		);

		return (isset($map[$key]) ? $map[$key] : null);
	}

	public function getReviewMessage($productCode)
	{
		$reviewMessage = "<p class='alert alert-info'>PS: would you mind taking a minute or two to write brief a review for <a href='[productReviewUrl]' target='_blank'>this product</a> or <a href='[developerReviewUrl]' target='_blank'>about us</a>? Your comments help others know what to expect from this product or from us, and will help us improve our services and products. Thank you very much <span style='font-size:1.5em;'>&#x263a;</span>.</p>";
		$reviewMessage = strtr($reviewMessage, array(
			'[developerReviewUrl]' => $this->getReviewUrl('company'),
			'[productReviewUrl]' => $this->getReviewUrl($productCode),
		));

		return $reviewMessage;
	}

	public function isPaidSubscription($subscriptionId)
	{
		if (is_array($subscriptionId)) {
			$subscriptionId = $subscriptionId['adls_subscription_id'];
		}
		return $subscriptionId == 2;
	}
	public function isFreeSubscription($subscriptionId)
	{
		if (is_array($subscriptionId)) {
			$subscriptionId = $subscriptionId['adls_subscription_id'];
		}
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

	public function getProductById($productId)
	{
		return $this->getProducts(array(
			'product_id' => $productId,
			'single' => true
		));
	}

	public function getProducts($params = array())
	{
		$conditions = array();
		$joins = array();

		if (!empty($params['addon_id'])) {
			$conditions[] = db_quote('p.addon_id = ?s', $params['addon_id']);
		}
		if (!empty($params['product_id'])) {
			$conditions[] = db_quote('p.product_id = ?i', $params['product_id']);
		}

		$joins = !empty($joins) ?  implode("\n", $joins) : '';
		$conditions = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

		$query = db_quote('
			SELECT
				p.product_id,
				p.adls_addon_id,
				p.adls_subscription_id
			FROM ?:products AS p
			' . $joins . '
			' . $conditions . '
		');
		if (!empty($params['single'])) {
			$items = db_get_row($query);
		} else {
			$items = db_get_array($query);
		}

		return $items;
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


	public function getStoreProductsData()
	{
		$products = $this->getStoreProducts();
		$productsData = db_get_hash_array('
			SELECT
				product_id
				, adls_addon_id
				, adls_subscription_id
				, adls_release_date
				, adls_release_version
			FROM ?:products WHERE adls_addon_id IN (?a)', 'adls_addon_id', array_keys($products));
		$addonsPath = Registry::get('config.dir.addons');
		$releaseLogFilename = 'release.json';
		foreach ($products as $k => $v) {
			if (isset($productsData[$k])) {
				$products[$k] = array_merge($v, $productsData[$k]);
			}
			$releaseLogPath = $addonsPath . $k . DIRECTORY_SEPARATOR . $releaseLogFilename;
			$products[$k]['releases'] = array();
			$products[$k]['lastRelease'] = array();
			$products[$k]['has_unreleased_version'] = false;
			if (file_exists($releaseLogPath)) {
				$data = file_get_contents($releaseLogPath);
				if (!empty($data)) {
					$json = json_decode($data, true);
					if (!empty($json) && is_array($json)) {
						$lastRelease = reset($json);
						$products[$k]['releases'] = $json;
						$products[$k]['lastRelease'] = $lastRelease;
						$developmentVersion = $products[$k]['version'];
						$releasedVersion = $products[$k]['adls_release_version'];
						if (!empty($releasedVersion) && version_compare($developmentVersion, $releasedVersion, '>')) {
							$products[$k]['has_unreleased_version'] = true;
						}
					}
				}
			}
		}

		return $products;
	}
	public function getStoreProducts($params = array())
	{
		list($allItems, ) = fn_get_addons($params);

		$products = array();

		foreach ($allItems as $name => $item) {
			$scheme = SchemesManager::getScheme($name);
			if ($this->isOwnProduct($scheme)) {
				$item['version'] = $scheme->getVersion();
				$products[$name] = $item;
			}
		}
		return $products;
	}

	public function checkUpdates($customerProducts, $storeProducts)
	{
		$updates = array();
		foreach ($customerProducts as $productCode => $customerProduct) {
			$storeProduct = !empty($storeProducts[$productCode]) ? $storeProducts[$productCode] : '';
			if (empty($storeProduct)) {
				continue;
			}

			$storeVersion = !empty($storeProduct['version']) ? $storeProduct['version'] : '';
			$customerVersion = !empty($customerProduct['version']) ? $customerProduct['version'] : '';
//			if ($productCode == 'developer') { $storeVersion = 1; }
//			if ($productCode == 'sidekick') { $storeVersion = 1; }
//			if ($productCode == 'autoimage_lite') { $storeVersion = 1; }
			// @TODO: check update compatibility with platform (CS-Cart)!!!!
			$comparison = version_compare($storeVersion, $customerVersion);

			if ($comparison === 1) {
				// our store version is newer
				$updates[$productCode] = array(
					'version' => $storeVersion,
					'code' => $productCode,
					'reviewMessage' => $this->getReviewMessage($productCode)
				);

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
	public function isOwnProduct($productCodeOrScheme)
	{
		$scheme = is_string($productCodeOrScheme) ? SchemesManager::getScheme($productCodeOrScheme) : $productCodeOrScheme;
		if (empty($scheme)) {
			return false;
		}
		try {
			// xml prop is protected. We care not. We go for it. (XmlScheme3 should have implemented getAuthors()!)
			$a = (Array)$scheme;
			$key = "\0*\0_xml";;
			if (empty($a) || empty($a[$key]) || ! $a[$key] instanceof \SimpleXMLElement) {
				return false;
			}

			$author = (Array)$a[$key]->authors->author;
			if (empty($author) || empty($author['name']) || $author['name'] != ADLS_AUTHOR_NAME) {
				return false;
			}

			return true;

		} catch (\Exception $e) {
			// Doing nothing, having a coffee, chilling.
		}

		return false;
	}

	/**
	 * Updates release data attached to a CS-Cart product. Used by Developers Tools add-on.
	 *
	 * @param $productCode
	 * @param $params
	 * @return bool|int
	 */
	public function updateRelease($productCode, $params)
	{
		$productId = db_get_field('SELECT product_id FROM ?:products WHERE adls_addon_id = ?s', $productCode);
		if (empty($productId)) {
			return false;
		}
		list ($files, ) = fn_get_product_files(array('product_id' => $productId));
		$filename = $params['filename'];
		if (!empty($files)) {
			$file = array_shift($files);
			$fileId = $file['file_id'];
		} else {
			$file = array(
				'product_id' => $productId,
				'file_name' => $filename,
				'position' => 0,
				'folder_id' => null,
				'activation_type' => 'P',
				'max_downloads' => 0,
				'license' => '',
				'agreement' => 'Y',
				'readme' => '',
			);
			$fileId = 0;
		}
		$file['file_name'] = $filename;

		$_REQUEST['file_base_file'] = array(
			$fileId => $params['archiveUrl']
		);
		$_REQUEST['type_base_file'] = array(
			$fileId => 'url'
		);
		$fileId = fn_update_product_file($file, $fileId);
		if (!empty($fileId)) {
			$productData = array(
				'adls_release_version' => $params['version']
				, 'adls_release_date' => TIME
			);
			db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $productData, $productId);
		}

		return $fileId;
	}
}