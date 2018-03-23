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


use HeloStore\ADLS\Platform\Platform;
use HeloStore\ADLS\Platform\PlatformEditionRepository;
use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\Platform\PlatformVersionRepository;
use HeloStore\ADLS\Source\Source;
use HeloStore\ADLS\Source\SourceFileRepository;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;
use Tygh\Addons\SchemesManager;
use Tygh\Registry;

/**
 * Class ProductManager
 *
 * @package HeloStore\ADLS
 */
class ProductManager extends Singleton
{
    public function getReviewUrl($key)
    {
        static $map = array(
            'company'                 => 'http://marketplace.cs-cart.com/vlad-sergiu-valentin-pfa.html?selected_section=discussion#discussion',
            'developer'               => 'http://marketplace.cs-cart.com/add-ons/integrations/developer.html?selected_section=discussion#discussion',
            'free_shipping_notice'    => 'http://marketplace.cs-cart.com/add-ons/customer-experience/free-shipping-incentive-add-on-for-cs-cart.html?selected_section=discussion#discussion',
            'free_shipping_incentive' => 'http://marketplace.cs-cart.com/add-ons/customer-experience/free-shipping-incentive-add-on-for-cs-cart.html?selected_section=discussion#discussion',
            'autoimage_lite'          => 'http://marketplace.cs-cart.com/add-ons/customer-experience/autoimage-lite.html?selected_section=discussion#discussion',
        );

        return (isset($map[$key]) ? $map[$key] : null);
    }

    public function getReviewMessage($productCode)
    {
        if ($this->getReviewUrl($productCode) == null) {
            return '';
        }

        $reviewMessage = "<p class='alert alert-info'>PS: would you mind taking a minute or two to write a brief review for <a href='[productReviewUrl]' target='_blank'>this product</a> or <a href='[developerReviewUrl]' target='_blank'>about us</a>? Your comments help others know what to expect from this product or from us, and will help us improve our services and products. Thank you very much <span style='font-size:1.5em;'>&#x263a;</span>.</p>";
        $reviewMessage = strtr($reviewMessage, array(
            '[developerReviewUrl]' => $this->getReviewUrl('company'),
            '[productReviewUrl]'   => $this->getReviewUrl($productCode),
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
            'single'     => true
        ));
    }

    public function getProductByAddonId($addonId)
    {
        return $this->getProducts(array(
            'addon_id' => $addonId,
            'single'   => true
        ));
    }

    public function getProducts($params = array(), $langCode = CART_LANGUAGE)
    {
        $conditions = array();
        $joins      = array();

        if ( ! empty($params['addon_id'])) {
            $conditions[] = db_quote('p.adls_addon_id = ?s', $params['addon_id']);
        }
        if (isset($params['product_id'])) {
            $conditions[] = db_quote('p.product_id = ?i', $params['product_id']);
        }
        $joins[]    = db_quote('LEFT JOIN ?:product_descriptions AS pd ON pd.product_id = p.product_id AND pd.lang_code = ?s',
            $langCode);
        $joins      = ! empty($joins) ? implode("\n", $joins) : '';
        $conditions = ! empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $query = db_quote('
			SELECT
				p.product_id,
				p.adls_addon_id,
				p.adls_release_version,
				p.adls_subscription_id,
				pd.product
			FROM ?:products AS p
			' . $joins . '
			' . $conditions . '
		');

        if ( ! empty($params['single'])) {
            $items = db_get_row($query);
        } else {
            $items = db_get_array($query);
        }

        return $items;
    }

    public function getStoreProduct($productCode)
    {
        $products = $this->getStoreProducts();
        $product  = (isset($products[$productCode]) ? $products[$productCode] : null);

        if (empty($product)) {
            return null;
        }
        $data = db_get_row('SELECT product_id, adls_addon_id, adls_subscription_id FROM ?:products WHERE adls_addon_id = ?s',
            $productCode);
        if (is_array($data)) {
            $product = array_merge($product, $data);
        }

        return $product;
    }


    public function hydrateProductWithReleases(&$product, Platform $platform)
    {
        $productId = $product['product_id'];
        // Only released builds
        $product['releases']      = array();
        $product['latestRelease'] = array();
        if ( ! empty($productId)) {
            $params = [];
            if (AREA === 'A') {
                $params['getUserCount'] = true;
            }
//            $params['compatibilityPlatformId'] = $platform->getId();
            $params['sourcePlatformId'] = $platform->getId();
            list($product['releases'],) = ReleaseRepository::instance()->findByProductId($productId, $params);

            $params2                  = array(
                'compatibilityPlatformId' => $platform->getId()
            );
            $product['latestRelease'] = ReleaseRepository::instance()->findProductionOneLatestByProduct($productId,
                null, $params2);
        }

        // All builds, released or not
        $product['builds']      = SourceFileRepository::instance()->findTags($product, $platform);
        $product['latestBuild'] = null;
        if ( ! empty($product['builds'])) {
            $product['latestBuild'] = end($product['builds']);
        }
        // Check if there's unreleased builds
        $product['has_unreleased_version'] = true;
        if ( ! empty($product['latestBuild']) && ! empty($product['latestRelease'])) {
            $latestReleaseVersion = $product['latestRelease']->getVersion();
            $latestBuildVersion   = $product['latestBuild']['version'];
            if (version_compare($latestBuildVersion, $latestReleaseVersion) < 1) {
                $product['has_unreleased_version'] = false;
            }
        }
    }

    public function hydrateProductsWithReleases(&$products, Platform $platform)
    {
        foreach ($products as $k => $v) {
            $this->hydrateProductWithReleases($products[$k], $platform);
        }
    }

    public function getStoreProductsData()
    {
        $products           = $this->getStoreProducts();
        $productsData       = db_get_hash_array('
			SELECT
				product_id
				, adls_addon_id
				, adls_subscription_id
				, adls_release_date
				, adls_release_version
			FROM ?:products WHERE adls_addon_id IN (?a)', 'adls_addon_id', array_keys($products));
        $addonsPath         = Registry::get('config.dir.addons');
        $releaseLogFilename = 'release.json';

        foreach ($products as $k => $v) {
            if (isset($productsData[$k])) {
                $products[$k] = array_merge($v, $productsData[$k]);
                $v            = $products[$k];
            }
            $releaseLogPath           = $addonsPath . $k . DIRECTORY_SEPARATOR . $releaseLogFilename;
            $products[$k]['releases'] = array();
//			$products[$k]['lastRelease'] = array();
            $products[$k]['has_unreleased_version'] = false;
            $products[$k]['releases2']              = array();
            $products[$k]['latestRelease2']         = array();
            if ( ! empty($v['product_id'])) {
                $params = [];
                if (AREA === 'A') {
                    $params['getUserCount'] = true;
                }
                list($products[$k]['releases2'],) = ReleaseRepository::instance()->findByProductId($v['product_id'],
                    $params);
                $products[$k]['latestRelease2'] = ReleaseRepository::instance()->findProductionOneLatestByProduct($v['product_id']);
            }


            if (file_exists($releaseLogPath)) {
                $data = file_get_contents($releaseLogPath);
                if ( ! empty($data)) {
                    $json = json_decode($data, true);
                    if ( ! empty($json) && is_array($json)) {
                        $lastRelease                 = reset($json);
                        $products[$k]['releases']    = $json;
                        $products[$k]['lastRelease'] = $lastRelease;
                        $developmentVersion          = $products[$k]['version'];
                        if (empty($products[$k]['adls_release_version'])) {
                            continue;
                        }
                        $releasedVersion = $products[$k]['adls_release_version'];
                        if (version_compare($developmentVersion, $releasedVersion, '>')) {
                            $products[$k]['has_unreleased_version'] = true;
                        }
                    }
                }
            }
            if (empty($products[$k]['releases2']) && ! empty($products[$k]['releases'])) {
                $products[$k]['has_unreleased_version'] = true;
            }
        }


        return $products;
    }

    public function getStoreProducts($params = array())
    {
        list($allItems,) = fn_get_addons($params);

        $products = array();

        foreach ($allItems as $name => &$item) {
            $scheme = SchemesManager::getScheme($name);
            unset($item['url']);
            if ($this->isOwnProduct($scheme)) {
                $item['version'] = $scheme->getVersion();
                $products[$name] = $item;
            }
        }

        return $products;
    }

    /**
     * @param array $customerProducts
     * @param array $storeProducts
     * @param int $userId
     * @param null $request
     *
     * @return array
     * @throws \Exception
     */
    public function checkUpdates($customerProducts, $storeProducts, $userId = 0, $request = null)
    {
        $updates = array();
        foreach ($customerProducts as $productCode => $customerProduct) {
            $storeProduct = ! empty($storeProducts[$productCode]) ? $storeProducts[$productCode] : '';
            if (empty($storeProduct)) {
                continue;
            }
            $update = $this->getProductUpdate($productCode, $customerProduct, $storeProduct, $userId, $request);
            if ( ! empty($update)) {
                $updates[$productCode] = $update;
            }

        }

        return $updates;
    }

    /**
     * @param $productCode
     * @param $customerProduct
     * @param $storeProduct
     * @param int $userId
     * @param null $request
     *
     * @return array|bool
     * @throws \Exception
     */
    public function getProductUpdate($productCode, $customerProduct, $storeProduct, $userId = 0, $request = null)
    {
        $licenseKey   = $customerProduct['license'];
        $license      = null;
        $subscription = null;
        $productId    = 0;
        $productName  = $productCode;
        if ( ! empty($storeProduct) && ! empty($storeProduct['product_id'])) {
            $productId = $storeProduct['product_id'];
        }
        if ( ! empty($storeProduct['name'])) {
            $productName = $storeProduct['name'];
        }
        if ( ! empty($product['product'])) {
            $productName = $product['product'];
        }
        $releaseRepository = ReleaseRepository::instance();
//		$product = $this->getProductById( $productId );
        $customerVersion = ! empty($customerProduct['version']) ? $customerProduct['version'] : '';

        $productData      = ProductManager::instance()->getProductByAddonId($productCode);
        $freeSubscription = $this->isFreeSubscription($productData['adls_subscription_id']);
        $paidSubscription = $this->isPaidSubscription($productData['adls_subscription_id']);


        if ($paidSubscription) {
            if ( ! empty($licenseKey)) {
                $license = LicenseRepository::instance()->findOneByKey($licenseKey);
                if (empty($license)) {
                    return array(
                        'notifications' => array(
                            array(
                                'notification_type' => 'W',
                                'title'             => __('warning'),
                                'message'           => 'License not found for ' . $productName . ' v' . $customerVersion,
                                'code'              => LicenseClient::CODE_ERROR_UPDATE_CHECK_FAILED_INVALID_LICENSE
                            )
                        )
                    );
                    //				return false;
                    //				throw new \Exception(, LicenseClient::CODE_ERROR_UPDATE_CHECK_FAILED_INVALID_LICENSE);
                }
            }
        }

        if ( ! empty($license)) {
            $subscription = SubscriptionRepository::instance()->findOne(array(
                'extended'  => true,
                'userId'    => $userId,
                'orderId'   => $license->getOrderId(),
                'itemId'    => $license->getOrderItemId(),
                'productId' => $license->getProductId(),
            ));
        }

        $platformId        = null;
        $platformEditionId = null;
        $platformVersionId = null;
        if ( ! empty($request) && ! empty($request['platform'])) {
            if ( ! empty($request['platform']['name'])) {
                $platform = PlatformRepository::instance()->findOneByName($request['platform']['name']);
                if ( ! empty($platform)) {
                    $platformId = $platform->getId();
                }
            }
            if ( ! empty($request['platform']['edition'])) {
                $platformEdition = PlatformEditionRepository::instance()->findOneByName($request['platform']['edition']);
                if ( ! empty($platformEdition)) {
                    $platformEditionId = $platformEdition->getId();
                }
            }
            if ( ! empty($request['platform']['version'])) {
                $platformVersion = PlatformVersionRepository::instance()->findOneByVersion($request['platform']['version']);
                if ( ! empty($platformVersion)) {
                    $platformVersionId = $platformVersion->getId();
                }
            }
        }

        /** @var Release $latestRelease */
        $latestRelease = $releaseRepository->find(array(
            'one'                            => true,
            'productId'                      => $productId,
            'compatibilityPlatformId'        => $platformId,
            'compatibilityPlatformEditionId' => $platformEditionId,
            'compatibilityPlatformVersionId' => $platformVersionId
        ));

        if (empty($latestRelease)) {
//			throw new \Exception('Latest release not found for ' . $productCode, LicenseClient::CODE_ERROR_ALIEN);
            return false;
        }

        /** @var Release $latestUserRelease */
        $latestUserRelease = null;

        if ( ! empty($subscription)) {
            $latestUserRelease = $releaseRepository->find(array(
                'one'                            => true,
                'userId'                         => $userId,
                'productId'                      => $productId,
                'licenseId'                      => $license->getId(),
                'subscriptionId'                 => $subscription->getId(),
                'compatibilityPlatformId'        => $platformId,
                'compatibilityPlatformEditionId' => $platformEditionId,
                'compatibilityPlatformVersionId' => $platformVersionId
            ));
        } elseif ( ! empty($license)) {
            $latestUserRelease = $releaseRepository->find(array(
                'one'                            => true,
                'userId'                         => $userId,
                'productId'                      => $productId,
                'licenseId'                      => $license->getId(),
                'compatibilityPlatformId'        => $platformId,
                'compatibilityPlatformEditionId' => $platformEditionId,
                'compatibilityPlatformVersionId' => $platformVersionId
            ));
        } else {
            $latestUserRelease = $releaseRepository->find(array(
                'one'                            => true,
                'userId'                         => $userId,
                'productId'                      => $productId,
                'compatibilityPlatformId'        => $platformId,
                'compatibilityPlatformEditionId' => $platformEditionId,
                'compatibilityPlatformVersionId' => $platformVersionId
            ));
        }

        if ($freeSubscription) {
            $latestUserRelease = $latestRelease;
        }


        if (empty($latestUserRelease)) {
//            throw new \Exception('No latest release found');
            return false;
        }

        $currentUserRelease = $releaseRepository->findOneByProductVersion($productId, $customerVersion);
        if (empty($currentUserRelease)) {
            $message = 'You are using a deprecated product, ' . $productName . ' v' . $customerVersion . '. Please manually update to the latest version available in our store.';
            $code    = LicenseClient::CODE_ERROR_UPDATE_CHECK_FAILED_INVALID_VERSION;

            return array(
                'notifications' => array(
                    array(
                        'notification_type' => 'W',
                        'title'             => __('warning'),
                        'message'           => $message,
                        'code'              => $code
                    )
                )
            );

//			throw new \Exception($message, $code);
        }

        // There is a newer release to which user has access to
        if ($latestUserRelease->isNewerThan($currentUserRelease)) {
            $message = __('adls.api.update.message', array(
                '[addon]'          => $productName,
                '[currentVersion]' => $currentUserRelease->getVersion(),
                '[nextVersion]'    => $latestRelease->getVersion()
            ));

            return array(
                'version'       => $latestUserRelease->getVersion(),
                'userVersion'   => $customerVersion,
                'releaseId'     => $latestUserRelease->getId(),
                'code'          => $productCode,
                'reviewMessage' => $this->getReviewMessage($productCode),
                'notifications' => array(
                    array(
                        'notification_type'  => 'N',
                        'notification_extra' => 'adls.api.product_update_available',
                        'notification_state' => 'K',
                        'message_type'       => 'update',
                        'title'              => __('adls.api.update.title'),
                        'message'            => $message
                    )
                )
            );
        }
        // There is a newer release to which user has NO access to
        if ($latestRelease->isNewerThan($latestUserRelease)) {
            if (empty($subscription)) {
                throw new \Exception('Subscription not found', LicenseClient::CODE_ERROR_ALIEN);
            }

            $updateUrl = fn_url('adls_subscriptions.add?subscription_id=' . $subscription->getId(), 'C');
            $message   = __('adls.api.update.upsell.message', array(
                '[addon]'          => $productName,
                '[currentVersion]' => $currentUserRelease->getVersion(),
                '[nextVersion]'    => $latestRelease->getVersion(),
                '[updateUrl]'      => $updateUrl,
            ));

            // Suggest subscription renewal
            return array(
//				'version' => $latestRelease->getVersion(),
//                'userVersion' => $customerVersion,
//				'releaseId' => $latestRelease->getId(),
                'code'          => $productCode,
//				'reviewMessage' => $this->getReviewMessage($productCode),
                'notifications' => array(
                    array(
                        'notification_type'  => 'N',
                        'notification_extra' => 'adls.api.product_update_available_title',
                        'notification_state' => 'K',
                        'message_type'       => 'update_upsell',
                        'title'              => __('adls.api.update.title'),
                        'message'            => $message
                    )
                )
            );
        }

        return false;
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
            $a   = (Array)$scheme;
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
     * @deprecated Instead, use HeloStore\ADLS\ReleaseManager::release()
     *
     * Updates release data attached to a CS-Cart product. Used by Developers Tools add-on.
     *
     * @param $productCode
     * @param $params
     *
     * @return bool|int
     */
    public function release($productCode, $params)
    {
        $releaseManager = ReleaseManager::instance();
        if (method_exists($releaseManager, 'release')) {
            $storeProduct = $this->getStoreProduct($productCode);

            return ReleaseManager::instance()->release($storeProduct, $params);
        }

        return null;
    }

    /**
     * @deprecated Instead, use HeloStore\ADLS\ReleaseManager::release()
     *
     * Updates release data attached to a CS-Cart product. Used by Developers Tools add-on.
     *
     * @param $productCode
     * @param $params
     *
     * @return bool|int
     * @throws ReleaseException
     */
    public function updateRelease($productCode, $params)
    {
        $releaseManager = ReleaseManager::instance();
        if (method_exists($releaseManager, 'release')) {
            $storeProduct = $this->getStoreProduct($productCode);

            return ReleaseManager::instance()->release($storeProduct, $params);
        }

        return null;
    }
}
