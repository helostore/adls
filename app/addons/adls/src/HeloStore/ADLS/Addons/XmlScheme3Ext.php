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

namespace HeloStore\ADLS\Addons;

use Tygh\Addons\XmlScheme3;

class XmlScheme3Ext extends XmlScheme3
{

	/**
	 * @return array
	 */
	public function getAuthors()
	{
		return (isset($this->_xml->authors)) ? (array) $this->_xml->authors->children() : array();
	}

	/**
	 * @return array
	 */
	public function hasAuthor($authorName)
	{
		$authorsNodes = $this->getAuthors();
		if (empty($authorsNodes) || empty($authorsNodes['author'])) {
			return false;
		}
		$authorsNodes = !is_array($authorsNodes['author']) ? array($authorsNodes['author']) : $authorsNodes['author'];
		foreach ($authorsNodes as $authorNode) {
			if ($authorNode->name == $authorName) {
				return true;
			}
		}

		return false;
	}

}
