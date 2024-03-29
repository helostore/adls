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

$schema['central']['adls'] = array(
	'items' => array(
		'adls.licenses' => array(
			'href' => 'licenses.manage',
			'position' => 20
		),
		'adls.releases' => array(
			'href' => 'releases.overview',
			'position' => 50
		),
        'logs' => array(
            'href' => 'adls.logs',
            'position' => 100
        ),
        'adls.usage' => array(
            'href' => 'adls.usage',
            'position' => 200
        )
	),
	'position' => 900,
);

return $schema;
