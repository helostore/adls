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


class Subscription
{
	const STATUS_ACTIVE = 'A';
	const STATUS_DISABLED = 'D';
	const STATUS_INACTIVE = 'I';

	const TYPE_YEARLY = 'Y';
	const TYPE_MONTHLY = 'M';
	const TYPE_PERPETUAL = 'P';
}