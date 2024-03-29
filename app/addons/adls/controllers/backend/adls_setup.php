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


if ($mode == 'platforms') {
    \HeloStore\ADLS\Compatibility\CompatibilitySetup::instance()->make();
    exit;
}
if ($mode == 'platforms_sync') {
    \HeloStore\ADLS\Compatibility\CompatibilitySetup::instance()->sync($_REQUEST['platform']);
    exit;
}
