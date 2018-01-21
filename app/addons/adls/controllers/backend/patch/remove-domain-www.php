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


$domains = db_get_array('SELECT * FROM ?:adls_license_domains WHERE name LIKE "www%"');
if (empty($domains)) {
    fn_print_r('No domains matched for patch');

    return;
}
fn_print_r('Matched ' . count($domains) . ' domains for patch');
$updates = 0;
foreach ($domains as $domain) {
    $name = Utils::stripDomainWWW($domain['name']);
    if (db_query('UPDATE ?:adls_license_domains SET name = ?s WHERE id = ?i', $name, $domain['id'])) {
        $updates++;
    }
}
fn_print_r('Updated ' . $updates . ' domains: removed WWW');