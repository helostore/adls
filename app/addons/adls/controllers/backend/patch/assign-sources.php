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

use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\ProductRepository;
use HeloStore\ADLS\Source\Source;
use HeloStore\ADLS\Source\SourceManager;
use HeloStore\ADLS\Utils;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * http://local.helostore.com/hsw.php?dispatch=patch.apply&patch=assign-sources
 */

db_query('UPDATE ?:adls_platforms SET slug = "cscart" WHERE id = 1');
db_query('UPDATE ?:adls_platforms SET slug = "wordpress" WHERE id = 2');
db_query('UPDATE ?:adls_platforms SET slug = "magento" WHERE id = 3');
db_query('ALTER TABLE `?:products` ADD COLUMN `adls_slug`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `adls_subscription_id`');
db_query('UPDATE ?:products SET adls_slug = adls_addon_id WHERE adls_slug = "" OR adls_slug IS NULL');
db_query('UPDATE ?:products SET adls_slug = "ebriza" WHERE product_id = 14');
db_query('UPDATE ?:products SET adls_slug = "forensics" WHERE product_id = 11');


$cscart = PlatformRepository::instance()->findOneByName('CS-Cart');
$wordpress = PlatformRepository::instance()->findOneByName('WordPress');
$productRepository = ProductRepository::instance();
list($products, ) = $productRepository->find();

$pluginProductIds = array(11, 14);

foreach ($products as $i => $product) {
    if (in_array($product['product_id'], $pluginProductIds)) {
        $data = array(
            'platformId' => $wordpress->getId(),
            'productId' => $product['product_id'],
            'releasePath' => ADLS_SOURCE_PATH . '/' . $wordpress->getSlug() . '/products/' . $product['adls_addon_id'],
            'sourcePath' => ADLS_SOURCE_PATH . '/' . $wordpress->getSlug() . '/products/' . $product['adls_addon_id'],
        );
    } else {
        $data = array(
            'platformId' => $cscart->getId(),
            'productId' => $product['product_id'],
            'releasePath' => ADLS_SOURCE_PATH . '/' . $cscart->getSlug() . '/products/' . $product['adls_addon_id'],
            'sourcePath' => ADLS_SOURCE_PATH . '/' . $cscart->getSlug() . '/products/' . $product['adls_addon_id'],
        );
    }

    $sourceId = SourceManager::instance()->update($data);
    db_query('UPDATE ?:adls_releases SET sourceId = ?i WHERE productId = ?i', $sourceId, $product['product_id']);
}

exit;