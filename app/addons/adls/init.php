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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once __DIR__ . '/vendor/autoload.php';

define('ADLS_DIR', dirname(__FILE__));
define('ADLS_AUTHOR_NAME', 'HELOstore');
define('ADLS_PRODUCT_TYPE_ADDON', 'A'); // P and C reserved
define('ADLS_PRODUCT_TYPE_THEME', 'T');

fn_register_hooks(
	'change_order_status'
	, 'get_product_options'
	, 'get_order_info'
	, 'delete_order'
);
