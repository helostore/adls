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


use DOMDocument;
use DOMXPath;
use HeloStore\ADLS\Platform\Platform;
use HeloStore\ADLS\Platform\PlatformEditionRepository;
use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\Platform\PlatformVersionRepository;
use HeloStore\ADLS\Singleton;
use HeloStore\ADLS\Utils;

/**
 * Class CompatibilitySetup
 *
 * @package HeloStore\ADLS
 */
class CompatibilitySetup extends Singleton
{
    /**
     * @throws \Exception
     */
    public function sync($platform = 'all') {
        if (in_array($platform, array('all', 'wordpress', 'wp'))) {
            $this->syncWordPress();
        }
        if (in_array($platform, array('all', 'cscart', 'csc'))) {
            $this->syncCSCart();
        }
    }

    public function syncCSCart() {
        fn_echo("Updating CS-Cart version history... \n");

        $platformRepository = PlatformRepository::instance();
        $platform = $platformRepository->findOne([
            'slug' => Platform::SLUG_CSCART,
        ]);
        if (empty($platform)) {
            throw new \Exception('CS-Cart platform not found in DB');
        }
        $versionRepository = PlatformVersionRepository::instance();
        $latestLocalVersion = $versionRepository->findOne([
            'platformId' => $platform->getId(),
            'sort_by' => 'version',
            'sort_order' => 'desc',
            'items_per_page' => 1,
        ]);

        $versionHistoryURL = 'https://docs.cs-cart.com/latest/history/index.html';
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTMLFile($versionHistoryURL);
        $xpath = new DomXPath($dom);
        $nodes = $xpath->query('//*[@id="sidebar"]/ul[6]/li/ul/li');
        $versionHistory = array();
        foreach ($nodes as $i => $node) {
            $text = $node->nodeValue;
            $parts = explode('(', $text);
            if (count($parts) > 2) {
                throw new \Exception('Unexpected format: ' . var_export($text, true));
            }
            $strVersion = trim($parts[0]);
            $strDate = trim(trim($parts[1]), '()');
            $date = new \DateTime($strDate);
            $versionHistory[] = ['version' => $strVersion, 'dateStr' => $strDate, 'date' => $date];
        }

        usort($versionHistory, function($a, $b) {
            return $a['date']->getTimestamp() - $b['date']->getTimestamp();
        });

        $newVersions = array();
        if ( ! empty($latestLocalVersion)) {
            foreach ($versionHistory as $entry) {
                if (version_compare($entry['version'], $latestLocalVersion->getVersion(), '<=')) {
                    fn_echo('- skipping existing version: ' . $entry['version'] . ' (' . $entry['dateStr'] . ')' . "\n");
                } else {
                    $newVersions[] = $entry;
                }
             }
        }
        $editionRepository = PlatformEditionRepository::instance();
        list ($editions, ) = $editionRepository->find(['names' => ['Ultimate', 'Multivendor']]);

        foreach ($newVersions as $entry) {
            fn_echo("- adding version: {$entry['version']}, release date: {$entry['dateStr']}, edition: Ultimate/Multivendor \n");
            foreach ($editions as $edition) {
                $versionRepository->add(
                    $platform->getId(),
                    $entry['version'],
                    $edition->getId(),
                    '',
                    $entry['date']->format('Y-m-d H:i:s')
                );
            }
        }
    }

    public function syncWordPress()
    {
        fn_echo("Updating WordPress version history... \n");

        $platformRepository = PlatformRepository::instance();
        $platform = $platformRepository->findOne([
            'slug' => Platform::SLUG_WORDPRESS,
        ]);
        if (empty($platform)) {
            throw new \Exception('WordPress platform not found in DB');
        }
        $versionRepository = PlatformVersionRepository::instance();
        $latestLocalVersion = $versionRepository->findOne([
            'platformId' => $platform->getId(),
            'sort_by' => 'version',
            'sort_order' => 'desc',
            'items_per_page' => 1,
        ]);
        if (empty($latestLocalVersion)) {
            throw new \Exception('Latest WordPress local version not found in DB');
        }
        $latestLVP = Utils::explodeVersion($latestLocalVersion->getVersion());
        $maxIterations = 1000;
        $i = 0;
        $currentMinor = $latestLVP->minor;
        $currentMajor = $latestLVP->major;
        $emptyMinorResponse = 0;
        $nextMinor = false;
        $nextMajor = false;
        while (true) {
            if ($nextMinor) {
                $currentMinor++;
                $nextMinor = true;
            }
            if ($nextMajor) {
                $currentMajor++;
                $currentMinor = 0;
                $nextMajor = false;
            }
            $apiUrl = 'http://displaywp.com/wp-json/version/' . $currentMajor . $currentMinor;
            fn_echo('- request: ' . $apiUrl . "\n");
            $json = file_get_contents($apiUrl);
            $data = json_decode($json);
            if (empty($data)) {
                fn_echo("empty response \n");
                $emptyMinorResponse++;
                $nextMajor = true;
                if ($emptyMinorResponse > 2) {
                    fn_echo("Received second empty response, breaking  at ${currentMajor}.${currentMinor}\n");
                    break;
                }
            } else {
                $nextMinor = true;
                if (!empty($data->minor_versions)) {
                    foreach ($data->minor_versions as $remoteVersion) {
                        $rv = $remoteVersion->number;
                        fn_echo("- found remote version: $rv \n");
                        $version = $versionRepository->findOne([
                            'platformId' => $platform->getId(),
                            'version' => $rv
                        ]);
                        if (!empty($version)) {
                            fn_echo("    - already have it, skipping \n");
                            continue;
                        }
                        $versionRepository->add($platform->getId(), $rv, null, '', $remoteVersion->date);
                        fn_echo("    - creating \n");
                    }
                }
            }
            $i++;
            if ($i > $maxIterations) {
                fn_echo("Warning: breaking loop after ' . $maxIterations . ' iterations\n");
                break;
            }
        }
    }
    public function make()
    {
        // Add platforms
        $platformRepository = PlatformRepository::instance();
        $platforms = [
            ['name' => 'CS-Cart', 'slug' => 'cscart'],
            ['name' => 'WordPress', 'slug' => 'wordpress'],
            ['name' => 'Magento', 'slug' => 'magento'],
        ];
        foreach ($platforms as $entry) {
            $platform = $platformRepository->findOneByName($entry['name']);
            if (!empty($platform)) {
                continue;
            }
            $platformRepository->add($entry['name'], $entry['slug']);
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
            $versionRepository->add($platform->getId(), $entry->version, $edition->getId(), $entry->description, $entry->date);
        }

        // Add versions to WordPress platform
        // @TODO automatically update using https://wordpress.org/download/release-archive/ https://codex.wordpress.org/api.php?hidebots=1&days=7&limit=20&action=feedrecentchanges&feedformat=atom
        $json = file_get_contents(ADLS_DIR . '/fixture/wordpress_history.json');
        $data = json_decode($json);
        $platform = $platformRepository->findOneByName('WordPress');
        foreach ($data as $entry) {
            $version = $versionRepository->findOne([
                'platformId' => $platform->getId(),
                'version' => $entry->version
            ]);
            if (!empty($version)) {
                $version->setDescription($entry->description);
                $versionRepository->update($version);
                continue;
            }
            $versionRepository->add($platform->getId(), $entry->version, null, $entry->description, $entry->date);
        }

    }
}
