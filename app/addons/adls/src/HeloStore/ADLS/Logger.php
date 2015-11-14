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

	public function add($request, $server, $type = Logger::TYPE_LOG, $objectType = 'unknown', $objectAction = 'unknown')
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
		}

		if (!empty($request)) {
			$entry['request'] = json_encode($request);
		}

		if (!empty($server)) {
			$entry['server'] = json_encode($server);
		}

		db_query('INSERT INTO ?:adls_logs ?e', $entry);
	}
} 