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

use Tygh\CompanySingleton;

/**
 * Class Singleton
 *
 * @package HeloStore\ADLS
 */
class Singleton extends CompanySingleton {

	protected $errors = array();

	public function getErrors()
	{
		return $this->errors;
	}
	public function addError($message)
	{
		$this->errors[] = $message;
	}

	/**
	 * @param int $company_id
	 * @param array $params
	 *
	 * @return static
	 */
	public static function instance($company_id = 0, $params = array())
	{
		return parent::instance($company_id, $params);
	}

} 