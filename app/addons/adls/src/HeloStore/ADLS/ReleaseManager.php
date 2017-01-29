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
namespace HeloStore\ADLS;

use Tygh\Registry;

class ReleaseManager extends Singleton
{
	/**
	 * Updates release data attached to a CS-Cart product. Used by Developers Tools add-on.
	 *
	 * @param $productCode
	 * @param $params
	 * @return bool|int
	 */
	public function update($productCode, $params)
	{
		$productId = db_get_field('SELECT product_id FROM ?:products WHERE adls_addon_id = ?s', $productCode);
		if (empty($productId)) {
			return false;
		}
		list ($files, ) = fn_get_product_files(array('product_id' => $productId));
		
		$filename = $params['filename'];
		if (!empty($files)) {
			$file = array_shift($files);
			$fileId = $file['file_id'];
		} else {
			$file = array(
				'product_id' => $productId,
				'file_name' => $filename,
				'position' => 0,
				'folder_id' => null,
				'activation_type' => 'P',
				'max_downloads' => 0,
				'license' => '',
				'agreement' => 'Y',
				'readme' => '',
			);
			$fileId = 0;
		}
		$file['file_name'] = $filename;

		$_REQUEST['file_base_file'] = array(
			$fileId => $params['archiveUrl']
		);
		$_REQUEST['type_base_file'] = array(
			$fileId => 'url'
		);
		$fileId = fn_update_product_file($file, $fileId);
		if (!empty($fileId)) {
			$productData = array(
				'adls_release_version' => $params['version']
			, 'adls_release_date' => TIME
			);
			db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $productData, $productId);
		}

		return $fileId;
	}
}