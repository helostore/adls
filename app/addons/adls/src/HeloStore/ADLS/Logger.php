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

/**
 * Class Logger
 * 
 * @package HeloStore\ADLS
 */
class Logger extends Singleton
{
	const TYPE_ERROR = 'E';
	const TYPE_SUCCESS = 'S';
	const TYPE_WARNING = 'W';
	const TYPE_LOG = 'L';
	const TYPE_DEBUG = 'D';

	const OBJECT_TYPE_REQUEST = 'request';
	const OBJECT_TYPE_API = 'api';
	const OBJECT_TYPE_SUBSCRIPTION_ALERT = 'subscription_alert';
	const OBJECT_TYPE_SUBSCRIPTION_MIGRATE_ALERT = 'subscription_migrate_alert';

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

    public function update($id, $entry)
    {
        array_walk($entry, function (&$value, $key) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
        });

        return db_query('UPDATE ?:adls_logs SET ?u WHERE id = ?i', $entry, $id);

	}

    public function debug()
    {
        $content = json_encode(func_get_args());
        return $this->add(Logger::TYPE_DEBUG, $_REQUEST, $_SERVER, '', '', $content, '');
    }
	public function add($type, $request, $server, $objectType = '', $objectAction = '', $content = '', $backtrace = '')
	{
		$entry = array();
		$entry['type'] = $type;
		$entry['objectType'] = $objectType;
		$entry['objectAction'] = $objectAction;
		$entry['timestamp'] = TIME;

		if (!empty($request['auth'])) {
			if (!empty($request['auth']['user_id'])) {
				$entry['userId'] = $request['auth']['user_id'];

			}
		}
        if (!empty($server['REMOTE_ADDR'])) {
            $entry['ip'] = $server['REMOTE_ADDR'];
        } else {
            $entry['ip'] = fn_get_ip();
        }
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
        $limit = '';

		if (!empty($params['id'])) {
			$conditions[] = db_quote('al.id = ?i', $params['id']);
		}
		if (!empty($params['type'])) {
			$conditions[] = db_quote('al.type = ?s', $params['type']);
		}
		if (!empty($params['objectType'])) {
			$conditions[] = db_quote('al.objectType = ?s', $params['objectType']);
        }
        if (!empty($params['objectAction'])) {
            $conditions[] = db_quote('al.objectAction = ?s', $params['objectAction']);
		}
		if (!empty($params['ip'])) {
			$conditions[] = db_quote('al.ip = ?s', $params['ip']);
		}
		if (!empty($params['excludeIps'])) {
            $conditions[] = db_quote('al.ip NOT IN (?a)', $params['excludeIps']);
        }
        if (!empty($params['userId'])) {
            $conditions[] = db_quote('al.userId = ?s', $params['userId']);
		}
        if (!empty($params['requestPattern'])) {
            $conditions[] = db_quote('al.request LIKE ?l', '%' . $params['requestPattern'] . '%');
        }

        if (!empty($params['fromDate'])) {
            if ($params['fromDate'] instanceof \DateTime) {
                $params['fromDate'] = $params['fromDate']->getTimestamp();
            }
            $conditions[] = db_quote('al.timestamp > ?i', $params['fromDate']);
        }

        if (!empty($params['limit'])) {
            $limit = ' LIMIT 0,' . $params['limit'];
		}
        if (!empty($params['productCode'])) {
            $conditions[] = db_quote('al.request LIKE ?l', '%"code":"' . $params['productCode'] . '"%');
        }
		$joins[] = db_quote('LEFT JOIN ?:country_descriptions AS cd ON cd.code = al.country AND cd.lang_code = ?s', CART_LANGUAGE);

		$joins = !empty($joins) ?  implode("\n", $joins) : '';
		$conditions = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

        if (!empty($params['items_per_page'])) {
            $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT(al.id)) FROM ?:adls_logs AS al ?p ?p ?p ?p", $joins, $conditions);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }


		$query = db_quote('
			SELECT
				al.*,
				cd.country AS country_name
			FROM ?:adls_logs AS al
			' . $joins . '
			' . $conditions . '
			ORDER BY id DESC
			' . $limit . '
		');

		if (!empty($params['single'])) {
			$items = db_get_row($query);
        } else {
            $items = db_get_array($query);
		}

        foreach ($items as $i => $item) {
            $items[$i]['request'] = @json_decode($items[$i]['request'], true);
            $items[$i]['server'] = @json_decode($items[$i]['server'], true);
            if (!empty($items[$i]['request'])) {
                if (!empty($items[$i]['request']['email'])) {
                    $items[$i]['email'] = $items[$i]['request']['email'];
                }
				if (!empty($items[$i]['request']['products'])) {
					$items[$i]['product_code'] = array();
                    foreach ($items[$i]['request']['products'] as $product) {
                        $items[$i]['product_code'][] = $product['code'];
                    }
                    $items[$i]['product_code'] = implode(', ', $items[$i]['product_code']);
                }
                if (!empty($items[$i]['request']['product']['code'])) {
                    $items[$i]['product_code'] = $items[$i]['request']['product']['code'];
                }
            }
        }

		return array($items, $params);
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

    public function isError($code)
    {
        return ($code == Logger::TYPE_ERROR);
    }

    public function isWarning($code)
    {
        return ($code == Logger::TYPE_WARNING);
    }

    public function isLog($code)
    {
        return ($code == Logger::TYPE_LOG);
    }

    public function isSuccess($code)
    {
        return ($code == Logger::TYPE_SUCCESS);
    }
}
