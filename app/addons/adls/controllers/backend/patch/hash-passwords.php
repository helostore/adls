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

use HeloStore\ADLS\Utils;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * http://local.helostore.com/hsw.php?dispatch=patch.apply&patch=hash-passwords
 */


function fn_adls_patch_passwords(&$input)
{
    array_walk_recursive($input, function(&$value, $key) {
        if (in_array($key, ['REQUEST_URI', 'QUERY_STRING'])) {
            parse_str($value, $array);
            if (is_array($array)) {
                fn_adls_patch_passwords($array);
                $value = http_build_query($array);
            }
        }
        if ('password' === $key) {
            if ( ! empty($value)) {
                $isMd5 = strlen($value) == 32 && ctype_xdigit($value);
                if ( ! $isMd5) {
                    $initialValue = $value;
                    $value = md5($value);
//                    fn_echo($initialValue . ' -> ' . $value . PHP_EOL);
                    fn_echo('.');
                }
            }
        }
    });
}

$params = array(
    'items_per_page' => 1000,
//    'id' => 7657
);

$logger = \HeloStore\ADLS\Logger::instance();
$logs = [];
$count = 0;
do {

    list($logs, $params) = $logger->get($params);
    sleep(2);
    fn_echo("Page: " . $params['page'] . ', items: ' . count($logs) . PHP_EOL);
    $params['page']++;

    if (empty($logs)) {
        break;
    }

    foreach ($logs as $log) {
        fn_adls_patch_passwords($log);
        $logger->update($log['id'], $log);
        $count++;
    }


} while (!empty($logs));

fn_print_r("Processed entries: " . $count);

exit;