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
		if (!empty($server['REMOTE_ADDR'])) {
			$entry['ip'] = $server['REMOTE_ADDR'];
			$entry['country'] = fn_get_country_by_ip($entry['ip']);
		}


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
		if (!empty($params['user_id'])) {
			$conditions[] = db_quote('al.user_id = ?s', $params['user_id']);
		}
		$joins = !empty($joins) ?  implode("\n", $joins) : '';
		$conditions = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

		$query = db_quote('
			SELECT
				al.*
			FROM ?:adls_logs AS al
			' . $joins . '
			' . $conditions . '
			ORDER BY log_id DESC
		');
		if (!empty($params['single'])) {
			$items = db_get_row($query);
		} else {
			$items = db_get_array($query);
		}

		return $items;
	}
} 