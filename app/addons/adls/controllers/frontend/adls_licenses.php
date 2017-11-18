<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2017 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */

use HeloStore\ADLS\LicenseRepository;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ( $mode === 'manage' ) {
	$userId = $auth['user_id'];
	$licenseRepository = LicenseRepository::instance();
	$userId = $auth['user_id'];
	list($licenses, $search) = $licenseRepository->find(array(
		'userId' => $userId,
		'getDomains' => true,
		'extended' => true
	));
	Tygh::$app['view']->assign('licenses', $licenses);
	Tygh::$app['view']->assign('search', $search);
}