<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2017-12-30
 * Time: 12:25
 */

namespace HeloStore\ADLS;


class Usage
{
    public static function general($params = array())
    {
        $logger = \HeloStore\ADLS\Logger::instance();
        $params['limit'] = isset($params['limit']) ? $params['limit'] : 1000;
        $params['objectAction'] = 'update_check';
        $params['excludeIps'] = array(
            '***REMOVED***'
            , '127.0.0.1'
        );
        list($logs, $result) = $logger->get($params);
        $usage = array();
        foreach ($logs as $log) {
            if (empty($log)) {
                continue;
            }
            if (empty($log['request'])) {
                continue;
            }
            if (empty($log['request']['platform'])) {
                continue;
            }
            if (empty($log['request']['platform']['name'])) {
                continue;
            }
            if (empty($log['request']['platform']['version'])) {
                continue;
            }
            if (empty($log['request']['platform']['edition'])) {
                continue;
            }
            $platform = $log['request']['platform']['name'];
            $edition = $log['request']['platform']['edition'];
            $version = $log['request']['platform']['version'];
            if ( ! isset($usage[$platform])) {
                $usage[$platform] = array();
            }
            if ( ! isset($usage[$platform][$edition])) {
                $usage[$platform][$edition] = array();
            }
            if ( ! isset($usage[$platform][$edition][$version])) {
                $usage[$platform][$edition][$version] = array(
                    'requests' => 0,
                    'hostname' => array()
                );
            }
            if ( ! empty($log['request']['server']['hostname'])) {
                if ( ! in_array($log['request']['server']['hostname'], $usage[$platform][$edition][$version]['hostname'])) {
                    $usage[$platform][$edition][$version]['hostname'][] = $log['request']['server']['hostname'];
                }
            }
            $usage[$platform][$edition][$version]['requests']++;
        }
        foreach ($usage as $platform => $editions) {
            ksort($usage[$platform]);
            foreach ($editions as $edition => $versions) {
                ksort($usage[$platform][$edition]);
            }
        }

        return $usage;
    }

    public static function product($productCode, $params = array())
    {
        $logger = \HeloStore\ADLS\Logger::instance();
        $params['limit'] = isset($params['limit']) ? $params['limit'] : 100;
        $params['productCode'] = $productCode;
        $params['objectAction'] = 'update_check';
        $params['excludeIps'] = array(
            '***REMOVED***'
            , '127.0.0.1'
        );
        list($logs, $result) = $logger->get($params);
        $usage = array();
        foreach ($logs as $log) {
            if (empty($log)) {
                continue;
            }
            if (empty($log['request'])) {
                continue;
            }
            if (empty($log['request']['platform'])) {
                continue;
            }
            if (empty($log['request']['platform']['name'])) {
                continue;
            }
            if (empty($log['request']['platform']['version'])) {
                continue;
            }
            if (empty($log['request']['platform']['edition'])) {
                continue;
            }
            $platform = $log['request']['platform']['name'];
            $edition = $log['request']['platform']['edition'];
            $version = $log['request']['platform']['version'];
            if ( ! isset($usage[$platform])) {
                $usage[$platform] = array();
            }
            if ( ! isset($usage[$platform][$edition])) {
                $usage[$platform][$edition] = array();
            }
            if ( ! isset($usage[$platform][$edition][$version])) {
                $usage[$platform][$edition][$version] = array(
                    'requests' => 0,
                    'hostname' => array()
                );
            }
            if ( ! empty($log['request']['server']['hostname'])) {
                $hostname = $log['request']['server']['hostname'];
                if ( ! isset($usage[$platform][$edition][$version]['hostname'][$hostname])) {
                    $usage[$platform][$edition][$version]['hostname'][$hostname] = array();
                }
                $productVersion = $log['request']['products'][$productCode]['version'];
                if ( ! in_array($productVersion, $usage[$platform][$edition][$version]['hostname'][$hostname])) {
                    $usage[$platform][$edition][$version]['hostname'][$hostname][] = $productVersion;
                }
            }

            $usage[$platform][$edition][$version]['requests']++;
        }
        foreach ($usage as $platform => $editions) {
            ksort($usage[$platform]);
            foreach ($editions as $edition => $versions) {
                ksort($usage[$platform][$edition]);
            }
        }

        return $usage;
    }
}