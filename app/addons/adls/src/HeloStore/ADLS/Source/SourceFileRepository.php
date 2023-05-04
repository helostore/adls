<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2018-02-03
 * Time: 10:38
 */

namespace HeloStore\ADLS\Source;


use HeloStore\ADLS\Platform\Platform;
use HeloStore\ADLS\Singleton;
use HeloStore\ADLS\Utils;

class SourceFileRepository extends Singleton
{
    public static function getSourcePath($productSlug, $platformSlug)
    {
        if (!defined('ADLS_SOURCE_PATH')) {
            throw new \Exception('Required constant is not defined: ADLS_SOURCE_PATH');
        }
        return ADLS_SOURCE_PATH
               . '/'
               . $platformSlug
               . '/'
               . 'products'
               . '/'
               . $productSlug;

    }

    public static function getReleasePath($productSlug, $platformSlug, $version = null)
    {
        return ADLS_SOURCE_PATH
               . '/'
               . $platformSlug
               . '/'
               . 'releases'
               . '/'
               . $productSlug
               . ($version === null ? '' : '/' . $productSlug . '-' . $version . '.zip');

    }

    public function findByPlatform(Platform $platform)
    {
        $path = ADLS_SOURCE_PATH
                . '/'
                . $platform->getSlug()
                . '/'
                . 'products';

        $dirs = glob($path . '/' . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {

        }
    }

    public function getLatestChangeLogFromGit($product, Platform $platform)
    {
        if (!Utils::isPHPFunctionEnabled('shell_exec')) {
            throw new \Exception('Required PHP function is disabled: shell_exec');
        }
        $path = SourceFileRepository::getSourcePath($product['adls_slug'], $platform->getSlug());
        $relVer = $product['latestRelease']->getVersion();
        $buildVer =  $product['latestBuild']['version'];
        $command = "git -C \"${path}\" log --oneline v${relVer}..v${buildVer} --format=\"%ad | %s\" --date=short";
        $output  = shell_exec($command);
        $output  = trim($output);

        return $output;
    }

    public function findTags($product, Platform $platform)
    {
        if (!Utils::isPHPFunctionEnabled('shell_exec')) {
            throw new \Exception('Required PHP function is disabled: shell_exec');
        }
        $path = SourceFileRepository::getSourcePath($product['adls_slug'], $platform->getSlug());

//        $command = "git -C \"${path}\" tag ";
        $command = "git -C \"${path}\" tag -l --format=\"%(refname:short)|%(taggerdate:iso8601)\"";
        $output  = shell_exec($command);
        $output  = trim($output);

        if (empty($output)) {
            return array();
        }
        $output = explode("\n", $output);

        $builds = array();
        foreach ($output as $k => $v) {
            if (empty($v)) {
                unset($output[$k]);
                continue;
            }
            if (substr($v, 0, 1) !== 'v') {
                unset($output[$k]);
                continue;
            }
            $output[$k] = trim($output[$k], 'v');
            $p          = explode("|", $output[$k]);
            $builds[]   = array(
                'version' => $p[0],
                'date'    => \DateTime::createFromFormat('Y-m-d H:i:s e', $p[1]),
            );
        }

        usort($builds, function ($a, $b) {
            return version_compare($a['version'], $b['version']);
        });

        return $builds;
    }

    public function findReleaseFile($productSlug, $platformSlug, $version)
    {
        $path = SourceFileRepository::getReleasePath($productSlug, $platformSlug, $version);
        if (file_exists($path)) {
            return $path;
        }

        return false;
    }

}
