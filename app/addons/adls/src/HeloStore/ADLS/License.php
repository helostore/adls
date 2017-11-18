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

/**
 * Class License
 * 
 * @package HeloStore\ADLS
 */
class License extends Entity
{
	const STATUS_ACTIVE = 'A';
	const STATUS_DISABLED = 'D';
	const STATUS_INACTIVE = 'I';

	const DOMAIN_TYPE_PRODUCTION = 'P';
	const DOMAIN_TYPE_DEVELOPMENT = 'D';

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var integer
	 */
	protected $orderId;

	/**
	 * @var integer
	 */
	protected $productId;

	/**
	 * @var integer
	 */
	protected $orderItemId;

	/**
	 * @var integer
	 */
	protected $userId;


	/**
	 * @var \DateTime
	 */
	protected $createdAt;

	/**
	 * @var \DateTime
	 */
	protected $updatedAt;

	/**
	 * @var string
	 */
	protected $licenseKey;

	/**
	 * @var string
	 */
	protected $status;

    /**
     * Non-stored fields
     */

    /**
     * @var array
     */
    protected $domains;

    /**
     * @var bool
     */
    protected $allDomainsDisabled;


	/**
	 * Getters/Setters
	 */

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @param int $orderId
	 *
	 * @return $this
	 */
	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @param int $productId
	 *
	 * @return $this
	 */
	public function setProductId($productId)
	{
		$this->productId = $productId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOrderItemId()
	{
		return $this->orderItemId;
	}

	/**
	 * @param int $orderItemId
	 *
	 * @return $this
	 */
	public function setOrderItemId($orderItemId)
	{
		$this->orderItemId = $orderItemId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 *
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}

	/**
	 * @param \DateTime $createdAt
	 *
	 * @return $this
	 */
	public function setCreatedAt($createdAt)
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getUpdatedAt()
	{
		return $this->updatedAt;
	}

	/**
	 * @param \DateTime $updatedAt
	 *
	 * @return $this
	 */
	public function setUpdatedAt($updatedAt)
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

    /**
     * @return string
     */
    public function getLicenseKey()
    {
        return $this->licenseKey;
    }

    /**
     * @param string $licenseKey
     *
     * @return $this
     */
    public function setLicenseKey($licenseKey)
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }

    /**
     * @return array
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param array $domains
     *
     * @return $this
     */
    public function setDomains($domains)
    {
        $this->domains = $domains;

        return $this;
    }


	/**
	 * Helpers
	 */

	/**
	 * @return bool|mixed
	 */
	public function getProductionDomain() {
		if ( ! $this->hasDomains() ) {
			return false;
		}

		foreach ( $this->domains as $domain ) {
			if ( $domain['type'] === License::DOMAIN_TYPE_PRODUCTION ) {
				return $domain;
			}
		}
	}

	/**
	 * @return string
	 */
    public static function convertStatusToLabel($status)
    {
		return __('adls.license.status.' . strtolower($status));
    }

    /**
     * @return string
     */
	public function getStatusLabel()
	{
        return self::convertStatusToLabel($this->status);
	}

    /**
     * @return bool
     */
    public function hasDomains()
    {
        return !empty($this->domains);
    }

    /**
     * @return boolean
     */
    public function hasAllDomainsDisabled()
    {
        return $this->allDomainsDisabled;
    }

    /**
     * @param boolean $allDomainsDisabled
     */
    public function setAllDomainsDisabled($allDomainsDisabled)
    {
        $this->allDomainsDisabled = $allDomainsDisabled;
    }

	/**
	 * @return bool
	 */
	public function isActive()
	{
		return $this->status == self::STATUS_ACTIVE;
	}
	/**
	 * @return bool
	 */
	public function isInactive()
	{
		return $this->status == self::STATUS_INACTIVE;
	}
	/**
	 * @return bool
	 */
	public function isDisabled()
	{
		return $this->status == self::STATUS_DISABLED;
	}
}