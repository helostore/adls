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

namespace HeloStore\ADLS\Source;

use HeloStore\ADLS\Manager;


class SourceManager extends Manager
{

    /**
     * @param $data
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function update($data)
    {
        $sourceRepository = SourceRepository::instance();
        $source = $sourceRepository->findOne(array(
            'productId' => $data['productId'],
            'platformId' => $data['platformId']
        ));
        if (empty($source)) {
            $source = new Source();
            $source->setProductId($data['productId']);
            $source->setPlatformId($data['platformId']);
            $sourceId = $sourceRepository->add($source);

        } else {
            $sourceId = $source->getId();
        }

        $source = $sourceRepository->findOneById($sourceId);
        $source->setSourcePath($data['sourcePath']);
        $source->setReleasePath($data['releasePath']);
        $sourceRepository->update($source);

        return $sourceId;
    }
}