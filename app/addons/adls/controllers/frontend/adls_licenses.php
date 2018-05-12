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
use HeloStore\ADLS\Release;
use HeloStore\ADLS\ReleaseManager;
use HeloStore\ADLS\ReleaseRepository;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (empty($auth['user_id'])) {
	return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
}

if ( $mode === 'manage' ) {
	$userId = $auth['user_id'];
	$licenseRepository = LicenseRepository::instance();
	$userId = $auth['user_id'];
    $params = array(
        'userId'     => $userId,
        'getDomains' => true,
        'extended'   => true,
        'sort_by'    => 'product',
        'sort_order' => 'asc',
    );
    if ( ! empty( $_REQUEST['sort_order'] ) ) {
        $params['sort_order'] = $_REQUEST['sort_order'];
    }
    if ( ! empty( $_REQUEST['sort_by'] ) ) {
        $params['sort_by'] = $_REQUEST['sort_by'];
    }
	list($licenses, $search) = $licenseRepository->find($params);


    $releaseParams = array(
        'extended' => true,
        'userId' => $userId
    );
    $releaseParams['status'] = array();
    if ( ! empty( $auth ) && !empty($auth['release_status'])) {
        $releaseParams['status'] = $auth['release_status'];
    }
    $releaseParams['status'][] = Release::STATUS_PRODUCTION;


    $releaseRepository = ReleaseRepository::instance();
	list ($releases, ) = $releaseRepository->find($releaseParams);
	$tmp = array();
	foreach ( $releases as $release ) {
		$pid = $release->getProductId();
		if ( ! isset( $tmp[ $pid ] ) ) {
			$tmp[ $pid ] = array();
		}
		$tmp[ $pid ][] = $release;
	}
    foreach ($tmp as $productId => $releases) {
        ReleaseManager::instance()->checkFileIntegrity($releases);
    }
	$releases = $tmp;
    Tygh::$app['view']->assign('releases', $releases);

    if ( ! empty( $licenses ) ) {
		foreach ( $licenses as $license ) {
			$pid = $license->getProductId();
			$license->latestRelease = null;
			$license->otherReleases = array();
			if ( ! empty( $releases[ $pid ] ) ) {
				$license->latestRelease = $releases[ $pid ][0];
				if ( count( $releases[ $pid ] ) > 1 ) {
					$license->otherReleases = $releases[ $pid ];
				}
			}
		}
	}
    Tygh::$app['view']->assign('licenses', $licenses);
    Tygh::$app['view']->assign('search', $search);
    fn_add_breadcrumb(__('adls.licenses'));
}
