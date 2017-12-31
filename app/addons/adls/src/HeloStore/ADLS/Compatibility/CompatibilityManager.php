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

namespace HeloStore\ADLS\Compatibility;


use HeloStore\ADLS\Platform\PlatformVersion;
use HeloStore\ADLS\Singleton;

/**
 * Class CompatibilityManager
 * 
 * @package HeloStore\ADLS
 */
class CompatibilityManager extends Singleton
{
    public function assign($productId, $releaseId, PlatformVersion $platformVersion)
    {
        $data = [
            'releaseId' => $releaseId,
            'platformVersionId' => $platformVersion->getId(),
            'platformId' => $platformVersion->getPlatformId(),
            'editionId' => $platformVersion->getEditionId(),
            'productId' => $productId,
        ];
        $query = db_quote('REPLACE INTO ?:adls_compatibility ?e', $data);
        db_query($query);
    }

}