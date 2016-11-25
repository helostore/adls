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

use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if (in_array($mode, array('update', 'add', 'manage'))) {
	$view = &Tygh::$app['view'];

	$types = array(
		'domain' => 'Single domain',
		'dev_domain' => 'Development domain',
		'domains' => 'Multiple domains',
	);

	$view->assign('adls_option_types', $types);
}
