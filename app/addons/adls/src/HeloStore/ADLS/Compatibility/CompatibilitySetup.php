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


use HeloStore\ADLS\Platform\PlatformEditionRepository;
use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\Platform\PlatformVersion;
use HeloStore\ADLS\Platform\PlatformVersionRepository;
use HeloStore\ADLS\Singleton;

/**
 * Class CompatibilitySetup
 * 
 * @package HeloStore\ADLS
 */
class CompatibilitySetup extends Singleton
{
    public function make()
    {
        // Add platforms
        $platformRepository = PlatformRepository::instance();
        $platforms = ['CS-Cart', 'WordPress', 'Magento'];
        foreach ($platforms as $name) {
            $platform = $platformRepository->findOneByName($name);
            if (!empty($platform)) {
                continue;
            }
            $platformRepository->add($name);
        }
        $platform = $platformRepository->findOneByName('CS-Cart');

        // Add editions to CS-Cart platform
        $editionRepository = PlatformEditionRepository::instance();
        $editions = ['Simple', 'Ultimate', 'Multivendor'];
        foreach ($editions as $name) {
            $edition = $editionRepository->findOneByName($name);
            if (!empty($edition)) {
                continue;
            }
            $editionRepository->add($platform->getId(), $name);
        }
        $edition = $editionRepository->findOneByName('Ultimate');


        // Add versions to CS-Cart platform
        $versionRepository = PlatformVersionRepository::instance();
        $json = file_get_contents(ADLS_DIR . '/fixture/cscart_history.json');
        $data = json_decode($json);
        foreach ($data as $entry) {
            $version = $versionRepository->findOne([
                'editionId' => $edition->getId(),
                'platformId' => $platform->getId(),
                'version' => $entry->version
            ]);
            if (!empty($version)) {
                $version->setDescription($entry->description);
                $versionRepository->update($version);
                continue;
            }
//            $entry->date = \DateTime::createFromFormat('Y-m-d H:i:s', $entry->date);
            $versionRepository->add($platform->getId(), $entry->version, $edition->getId(), $entry->description, $entry->date);
        }


        // Add versions to WordPress platform
        // @TODO automatically update using https://wordpress.org/download/release-archive/ https://codex.wordpress.org/api.php?hidebots=1&days=7&limit=20&action=feedrecentchanges&feedformat=atom
        $json = file_get_contents(ADLS_DIR . '/fixture/wordpress_history.json');
        $data = json_decode($json);
        $platform = $platformRepository->findOneByName('WordPress');
        foreach ($data as $entry) {
            $version = $versionRepository->findOne([
//                'editionId' => $edition->getId(),
                'platformId' => $platform->getId(),
                'version' => $entry->version
            ]);
            if (!empty($version)) {
                $version->setDescription($entry->description);
                $versionRepository->update($version);
                continue;
            }
//            $entry->date = \DateTime::createFromFormat('Y-m-d H:i:s', $entry->date);
            $versionRepository->add($platform->getId(), $entry->version, null, $entry->description, $entry->date);
        }

    }
}