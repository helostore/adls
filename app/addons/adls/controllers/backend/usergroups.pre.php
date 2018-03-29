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



if ( ! defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
	    if ( isset( $_REQUEST['usergroup_data']['release_status'] ) ) {
		    $_REQUEST['usergroup_data']['release_status'] = implode( ',',
			    $_REQUEST['usergroup_data']['release_status'] );
	    }

        return array(CONTROLLER_STATUS_OK);
    }
}

if ($mode == 'update') {



}
