<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use HeloStore\ADLS\ReleaseManager;
use HeloStore\ADLS\ReleaseRepository;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if (empty($auth['user_id'])) {
	return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
}


if ($mode == 'view') {
    $productId = !empty($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
	$product = array();
	if ( ! empty( $productId ) ) {
		$product = fn_get_product_data($productId, $auth);
		if (empty($product)) {
			return array(CONTROLLER_STATUS_NO_PAGE);
		}

		fn_add_breadcrumb(__('adls.releases'), 'adls_releases.view');
		fn_add_breadcrumb($product['product']);
		Tygh::$app['view']->assign('viewSingle', true);
	} else {
		fn_add_breadcrumb(__('adls.releases'));
		Tygh::$app['view']->assign('viewSingle', false);
	}

	$params = array(
		'userId'     => $auth['user_id'],
		'productId'  => $productId,
		'extended'   => true,
		'compatibilities'   => true,
	);

	if ( ! empty( $productId ) ) {
        $params['sort_by'] = 'version';
        $params['sort_order'] = 'desc';
	} else {
        $params['sort_by'] = 'product';
        $params['sort_order'] = 'ascdesc';
	}

    if ( ! empty( $_REQUEST['sort_order'] ) ) {
        $params['sort_order'] = $_REQUEST['sort_order'];
    }
    if ( ! empty( $_REQUEST['sort_by'] ) ) {
        $params['sort_by'] = $_REQUEST['sort_by'];
    }

    if ( ! empty( $productId ) ) {
        list( $releases, $search ) = ReleaseRepository::instance()->find( $params );
    } else {
        list( $releases, $search ) = ReleaseRepository::instance()->findLatest( $params );
    }

    /** @var \HeloStore\ADLS\Release $release */
    foreach ($releases as $release) {

        $compatibilities = [];
        if ( ! empty($release)) {
            list($compatibilities, ) = \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->find(array(
                'releaseId' => $release->getId(),
            ));
            $release->setCompatibility($compatibilities);
        }
    }

	Tygh::$app['view']->assign('releases', $releases);
	Tygh::$app['view']->assign('search', $search);
	Tygh::$app['view']->assign('product', $product);
}


if ($mode == 'download' && !empty($_REQUEST['hash'])) {
    $releaseRepository = ReleaseRepository::instance();
    $subscriptionRepository = SubscriptionRepository::instance();
    $hash = strval($_REQUEST['hash']);
	$release = ReleaseRepository::instance()->findOneByHashUser( $hash, $auth['user_id'] );
    if (empty($release)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

	if ( ! ReleaseManager::instance()->download( $release ) ) {
		return array(CONTROLLER_STATUS_DENIED);
	}
	exit;


}