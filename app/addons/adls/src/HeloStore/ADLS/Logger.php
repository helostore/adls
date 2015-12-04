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


use Net_GeoIP;
use Tygh\Registry;

class Logger extends Singleton
{
	const TYPE_ERROR = 'E';
	const TYPE_SUCCESS = 'S';
	const TYPE_WARNING = 'W';
	const TYPE_LOG = 'L';

	const OBJECT_TYPE_REQUEST = 'request';
	const OBJECT_TYPE_API = 'api';

	public function success($request, $server, $objectType = '', $objectAction = '', $content = '')
	{
		return $this->add(Logger::TYPE_SUCCESS, $request, $server, $objectType, $objectAction, $content);
	}
	public function warning($request, $server, $objectType = '', $objectAction = '', $content = '')
	{
		return $this->add(Logger::TYPE_WARNING, $request, $server, $objectType, $objectAction, $content);
	}
	public function error($request, $server, $objectType = '', $objectAction = '', $content = '', $backtrace = '')
	{
		return $this->add(Logger::TYPE_ERROR, $request, $server, $objectType, $objectAction, $content, $backtrace);
	}
	public function log($request, $server, $objectType = '', $objectAction = '', $content = '')
	{
		return $this->add(Logger::TYPE_LOG, $request, $server, $objectType, $objectAction, $content);
	}
	public function add($type, $request, $server, $objectType = '', $objectAction = '', $content = '', $backtrace = '')
	{
		$entry = array();
		$entry['type'] = $type;
		$entry['object_type'] = $objectType;
		$entry['object_action'] = $objectAction;
		$entry['timestamp'] = TIME;

		if (!empty($request['auth'])) {
			if (!empty($request['auth']['user_id'])) {
				$entry['user_id'] = $request['auth']['user_id'];

			}
		}
		$entry['ip'] = fn_get_ip();
		$entry['country'] = $this->getCountryCodeByIp($entry['ip']);

		if (!empty($content)) {
			$entry['content'] = is_string($content) ? $content : json_encode($content);
		}

		if (!empty($backtrace)) {
			$entry['backtrace'] = is_string($backtrace) ? $backtrace : json_encode($backtrace);
		}

		if (!empty($request)) {
			$entry['request'] = json_encode($request);
		}

		if (!empty($server)) {
			$entry['server'] = json_encode($server);
		}

		return db_query('INSERT INTO ?:adls_logs ?e', $entry);
	}

	public function get($params)
	{
		$conditions = array();
		$joins = array();

		if (!empty($params['type'])) {
			$conditions[] = db_quote('al.type = ?s', $params['type']);
		}
		if (!empty($params['object_type'])) {
			$conditions[] = db_quote('al.object_type = ?s', $params['object_type']);
		}
		if (!empty($params['ip'])) {
			$conditions[] = db_quote('al.ip = ?s', $params['ip']);
		}
		if (!empty($params['exclude_ips'])) {
            $conditions[] = db_quote('al.ip NOT IN (?a)', $params['exclude_ips']);
        }
        if (!empty($params['user_id'])) {
            $conditions[] = db_quote('al.user_id = ?s', $params['user_id']);
		}

		$joins[] = db_quote('LEFT JOIN ?:country_descriptions AS cd ON cd.code = al.country AND cd.lang_code = ?s', CART_LANGUAGE);

		$joins = !empty($joins) ?  implode("\n", $joins) : '';
		$conditions = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

		$query = db_quote('
			SELECT
				al.*,
				cd.country AS country_name
			FROM ?:adls_logs AS al
			' . $joins . '
			' . $conditions . '
			ORDER BY log_id DESC
		');

        $result = array();
		if (!empty($params['single'])) {
			$items = db_get_row($query);
        } else {
            $items = db_get_array($query);
            $result['total'] = count($items);
		}

        foreach ($items as $i => $item) {
            $items[$i]['request'] = @json_decode($items[$i]['request'], true);
            if (!empty($items[$i]['request'])) {
                if (!empty($items[$i]['request']['email'])) {
                    $items[$i]['email'] = $items[$i]['request']['email'];
                }
                if (!empty($items[$i]['request']['product']['code'])) {
                    $items[$i]['product_code'] = $items[$i]['request']['product']['code'];
                }
            }
        }

		return array($items, $result);
	}

	public function getCountryCodeByIp($ip)
	{
		if (function_exists('geoip_country_code_by_name')) {
			$code = @geoip_country_code_by_name($ip);
			$code = !empty($code) ? $code : '';
		} else {
			$geoip = Net_GeoIP::getInstance(Registry::get('config.dir.lib') . 'pear/data/geoip.dat');
			$code = $geoip->lookupCountryCode($ip);
		}

		return $code;
	}

    public function getLogTypeLabel($code)
    {
        $labels = array(
            Logger::TYPE_ERROR => 'Error',
            Logger::TYPE_LOG => 'Log',
            Logger::TYPE_SUCCESS => 'Success',
            Logger::TYPE_WARNING => 'Warning',
        );

        return (isset($labels[$code]) ? $labels[$code] : 'Unknown');
    }
} 