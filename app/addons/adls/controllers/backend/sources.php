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

use HeloStore\ADLS\Source\Source;
use HeloStore\ADLS\Source\SourceManager;
use HeloStore\ADLS\Source\SourceRepository;


if ( ! defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        SourceManager::instance()->update($_POST['source_data']);

        return array(CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER']);
    }
}

if ($mode == 'update') {



}