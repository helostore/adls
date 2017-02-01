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
	 * Release a new version
	 *
	 * @param $productCode
	 * @param $params
	 * @return bool|int
	 */
	public function release($productCode, $params)
	{
		$productId = db_get_field('SELECT product_id FROM ?:products WHERE adls_addon_id = ?s', $productCode);
		if (empty($productId)) {
			return false;
		}

		$date = new \DateTime();
		$fileId = $this->attachFile($productId, $params);

		$data = array(
			'product_id' => $productId,
			'created_at' => $date->format('Y-m-d H:i:s'),
			'file_id' => $fileId,
			'version' => $params['version']
		);

		$releaseId = db_query('INSERT INTO ?:adls_releases ?e', $data);

		return $releaseId;
	}

	/**
	 * Attach release file to product
	 *
	 * @param $productId
	 * @param $params
	 * @return int
	 */
	public function attachFile($productId, $params)
	{

		list ($files, ) = fn_get_product_files(array('product_id' => $productId));
		$position = ($files ? count($files) : 1);
		$position++;

		$filename = $params['filename'];

		$file = array(
			'product_id' => $productId,
			'file_name' => $filename,
			'position' => $position,
			'folder_id' => null,
			'activation_type' => 'M',
			'max_downloads' => 0,
			'license' => '',
			'agreement' => 'Y',
			'readme' => '',
		);
		$fileId = 0;
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
				'adls_release_version' => $params['version'],
				'adls_release_date' => TIME
			);
			db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $productData, $productId);
		}

		return $fileId;
	}
}