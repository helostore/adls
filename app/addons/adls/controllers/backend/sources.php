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

$sourceManager = SourceManager::instance();
$sourceRepository = SourceRepository::instance();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'delete') {
        $source = $sourceRepository->findOneById($_REQUEST['id']);
        if ( ! empty($source)) {
            $result = $sourceRepository->delete($source);
            if ($result) {
                fn_set_notification('N', __('notice'), 'Successfully deleted source.');
            } else {
                fn_set_notification('E', __('error'), 'Failed deleting source: unknown error.');
            }
        } else {
            fn_set_notification('E', __('error'), 'Failed deleting source: source not found.');
        }

        return array(CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER']);
    }


    if ($mode == 'update') {
        $sourceId = $sourceManager->update($_POST['source_data']);
        if (empty($sourceId)) {
            fn_set_notification('E', __('error'), 'Failed creating new source.');
        } else {
            fn_set_notification('N', __('notice'), 'Successfully created new source.');
        }
        return array(CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER']);
    }
}

if ($mode == 'update') {



}