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


use Tygh\Registry;

class SchemesManager extends \Tygh\Addons\SchemesManager
{
	/**
	 * Creates and returns XmlScheme object for addon
	 *
	 * @param  string    $addon_id Addon name
	 * @param  string    $path     Path to addons
	 * @return XmlScheme object
	 */
	public static function getSchemeExt($addon_id, $path = '')
	{
		if (empty($path)) {
			$path = Registry::get('config.dir.addons');
		}

		libxml_use_internal_errors(true);
		if (true or !isset (parent::$schemas[$addon_id])) {
			$_xml = self::readXml($path . $addon_id . '/addon.xml');
			if ($_xml !== FALSE) {
				$versions = self::getVersionDefinition();
				$version = (isset($_xml['scheme'])) ? (string) $_xml['scheme'] : '1.0';
				self::$schemas[$addon_id] = new $versions[$version]($_xml);
			} else {
				$errors = libxml_get_errors();

				$text_errors = array();
				foreach ($errors as $error) {
					$text_errors[] = self::displayXmlError($error, $_xml);
				}

				libxml_clear_errors();
				if (!empty($text_errors)) {
					fn_set_notification('E', __('xml_error'), '<br/>' . implode('<br/>' , $text_errors));
				}

				return false;
			}
		}

		return self::$schemas[$addon_id];
	}

	/**
	 * Loads xml
	 * @param $filename
	 * @return bool
	 */
	private static function readXml($filename)
	{
		if (file_exists($filename)) {
			return simplexml_load_file($filename);
		}

		return false;
	}

	/**
	 * Returns the scheme in which a class processing any certain xml scheme version is defined.
	 * @static
	 * @return array
	 */
	private static function getVersionDefinition()
	{
		return array(
			'1.0' => 'Tygh\\Addons\\XmlScheme1',
			'2.0' => 'Tygh\\Addons\\XmlScheme2',
			'3.0' => 'HeloStore\\ADLS\\Addons\\XmlScheme3Ext',
		);
	}
	private static function displayXmlError($error, $xml)
	{
		$return  = $xml[$error->line - 1] . "\n";

		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= '<b>'. __('warning') . " $error->code:</b> ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= '<b>'. __('error') . " $error->code:</b> ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= '<b>'. __('error') . " $error->code:</b> ";
				break;
		}

		$return .= trim($error->message) . '<br/>  <b>' . __('line') . "</b>: $error->line" . '<br/>  <b>' . __('column') . "</b>: $error->column";

		if ($error->file) {
			$return .= '<br/> <b>' . $error->file . '</b>';
		}

		return "$return<br/>";
	}
} 