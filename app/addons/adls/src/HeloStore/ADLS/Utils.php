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

use DateTime;
use Zend\I18n\Validator\Alnum;
use Zend\Validator\Hostname;
use Zend\Validator\StringLength;
use Zend\Validator\ValidatorChain;

/**
 * Class Utils
 *
 * @package HeloStore\ADLS
 */
class Utils extends Singleton
{
    public static function isPHPFunctionEnabled($func)
    {
        return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
    }

    public static function versionToInteger($string)
    {
        $version = Utils::explodeVersion($string);
        $ip = $version->major . '.' . $version->minor . '.' . $version->patch . '.' . 0;

        return ip2long($ip);
    }
    public static function integerToVersion($number)
    {
        $ip = long2ip($number);
        $version = substr($ip, 0, -2);

        return $version;
    }

    /**
     * @param $version
     *
     * @return Version
     */
    public static function explodeVersion($version)
    {
        $result = preg_match("/^(\d+)\.(\d+)[\. \-]?([a-z0-9\-\.]+)?$/i", $version, $matches);
        $minor = $major = $patch = 0;
        if ($result) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];
            $patch = isset($matches[3]) ? (int) $matches[3] : 0;

        } else {
            $major = intval($version);
        }

        if (!is_numeric($major)) {
            return new Version();
        }

        return new Version($major, $minor, $patch);
    }

    /**
     * @param $string
     *
     * @return mixed
     */
	public static function matchVersion($string) {
        preg_match("/(?:version|v|)\s*((?:[0-9]+\.?)+)/i", $string, $matches);

        if ( ! empty($matches[1])) {
            $matches[1] = trim($matches[1], '. ');
        }
        return $matches[1];
    }
	public static function stripDomainWWW($domain) {
        return preg_replace('/^www\./i', '', $domain);
    }
	public static function generateKey($length = 16, $groupLength = 4) {
		$chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		self::shuffle($chars);
		$array = array_slice($chars, 0, $length);
		$string = implode('', $array);
		if (!empty($groupLength)) {
			$string = chunk_split($string, $groupLength, '-');
			$string = trim($string, '-');
		}

		return $string;
	}

	private static function shuffle(&$items)
	{
		@mt_srand(self::make_seed());
		for ($i = count($items) - 1; $i > 0; $i--)
		{
			$j = @mt_rand(0, $i);
			$tmp = $items[$i];
			$items[$i] = $items[$j];
			$items[$j] = $tmp;
		}
	}
	private static function make_seed()
	{
		static $count = 0;
		$count++;
		list($usec, $sec) = explode(' ', microtime());
		return (float) $count + (float) $sec + ((float) $usec * 100000);
	}

	public static function validateHostname($hostname, $type = License::DOMAIN_TYPE_DEVELOPMENT) {
		$flags = (Hostname::ALLOW_LOCAL | Hostname::ALLOW_IP);

		$isStrict = ! defined('ADLS_SKIP_STRICT_DOMAIN_VALIDATION');
		
		if ($type == License::DOMAIN_TYPE_PRODUCTION && $isStrict) {
			$flags = (Hostname::ALLOW_DNS);
		}
		$chain = new ValidatorChain();
		$chain->attach(new Hostname($flags));

		// Validate the $hostname
		if ($chain->isValid($hostname)) {
			if ($type == License::DOMAIN_TYPE_PRODUCTION && $isStrict) {
				$ip = gethostbyname($hostname);
				if ($ip == $hostname) {
					return array(
						'This is not a valid Internet domain.'
					);
				}
			}
			return true;
		} else {
			return $chain->getMessages();
		}
	}

	public static function filterDomainProductOptions($options)
	{
		$opts = array();
		if (empty($options)) {
			return array();
		}
		foreach ($options as $k => $option) {
			if (!fn_adls_is_product_option_domain($option)) {
				continue;
			}

			$opts[$k] = $option;
		}
		return $opts;
	}
	public static function extractDomainsFromProductOptions($options)
	{
		$domains = array();
		foreach ($options as $option) {
			if (!fn_adls_is_product_option_domain($option)) {
				continue;
			}
			$domainType = $option['adls_option_type'];
			$domains[] = array(
				'name' => $option['value'],
				'type' => $domainType,
				'productOptionId' => $option['option_id'],
			);

		}

		return $domains;
	}
	public static function updateLicenseDomainsFromProductOptions($licenseId, $options)
	{
		$domains = self::extractDomainsFromProductOptions($options);

		if (!empty($domains)) {
			$manager = LicenseManager::instance();
			$manager->updateLicenseDomains($licenseId, $domains);
		}
	}

	/**
	 * @param DateTime $date
	 *
	 * @return $this
	 */
	public function overridePresentDate(DateTime $date)
	{
		global $_timeTravelDate;

		$_timeTravelDate = $date;

		return $this;
	}
	/**
	 * @return DateTime
	 */
	public function getCurrentDate()
	{
        global $_timeTravelDate;
		return (empty($_timeTravelDate)) ? new \DateTime() : clone $_timeTravelDate;
	}

	/**
	 * Returns the formatted size
	 *
	 * @param  int $size
	 * @return string
	 */
	public function toByteString($size, $precision = 2)
	{
		$sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		for ($i=0; $size >= 1024 && $i < 9; $i++) {
			$size /= 1024;
		}

		return round($size, $precision) . $sizes[$i];
	}

    public static function sanitizeServerData($server)
    {
        if (empty($server) || !is_array($server)) {
            return [];
        }
        $fields = [
            'HOSTNAME',
            'PHP_ENV',
            'PHP_VERSION',
            'SERVER_NAME',
            'HTTP_HOST',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CF_RAY',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CF_IPCOUNTRY',
            'HTTP_CF_VISITOR',
            'HTTP_USER_AGENT',
        ];
        $newArr = [];
        foreach ($fields as $field) {
            if (isset($server[$field])) {
                $newArr[$field] = $server[$field];
            }
        }
        return $newArr;
    }
} 