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

use HeloStore\ADLS\License;
use HeloStore\ADLS\LicenseClient;
use HeloStore\ADLS\LicenseManager;
use HeloStore\ADLS\LicenseServer;
use HeloStore\ADLS\Logger;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'apply') {
    if (empty($_REQUEST['patch'])) {
        die('No patch specified');
    }
    $patch = $_REQUEST['patch'];

    $path = __DIR__ . '/patch/' . $patch . '.php';

    if (!file_exists($path)) {
        die('Patch file not found: ' . $path);
    }
    fn_print_r('Applying patch: ' . $path);
    require_once $path;
    exit('EOF');
}