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
 * Class Release
 * 
 * @package HeloStore\ADLS
 */
class Release extends Entity
{
    const STATUS_ACTIVE = 'A';
    const STATUS_DISABLED = 'D';
    const STATUS_INACTIVE = 'I';

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var integer
	 */
	protected $productId;

	/**
	 * @var string
	 */
	protected $version;

    /**
     * @var integer
     */
	protected $number;

	/**
	 * @var integer
	 */
	protected $fileId;


	/**
	 * @var \DateTime
	 */
	protected $createdAt;

	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var integer
	 */
	protected $fileSize;

	/**
	 * @var integer
	 */
	protected $downloads;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @internal
     * @var array
     */
    private $compatibility;



    /**
     * Non-stored fields
     */




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
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 * @return $this
	 */
	public function setVersion($version)
	{
		$this->version = $version;

		return $this;
	}


    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     *
     * @return Release
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * @param int $fileId
     * @return $this
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return int
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @param int $downloads
     * @return $this
     */
    public function setDownloads($downloads)
    {
        $this->downloads = $downloads;

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
	 * Helpers
	 */

	/**
	 * Increment downloads number
     * 
     * @return $this
	 */
	public function download()
	{
		$this->downloads++;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param string $hash
	 *
	 * @return $this
	 */
	public function setHash( $hash ) {
		$this->hash = $hash;

		return $this;
	}

	public function compare(Release $release) {
		return version_compare( $this->getVersion(), $release->getVersion() );
	}

	/**
	 * Test if current release is newer than given release
	 *
	 * @param Release $release
	 *
	 * @return bool
	 */
	public function isNewerThan( Release $release ) {
		return ( $this->compare( $release ) === 1 );
	}

	/**
	 * Test if current release is older than given release
	 *
	 * @param Release $release
	 *
	 * @return bool
	 */
	public function isOlderThan( Release $release ) {
		return ( $this->compare( $release ) === -1 );
	}

	/**
	 * Test if current release is the same as given release
	 *
	 * @param Release $release
	 *
	 * @return bool
	 */
	public function isSameAs( Release $release ) {
		return ( $this->compare( $release ) === 0 );
	}

    /**
     * @return array
     */
    public function getCompatibility()
    {
        return $this->compatibility;
    }

    /**
     * @param array $compatibility
     *
     * @return Release
     */
    public function setCompatibility($compatibility)
    {
        $this->compatibility = $compatibility;

        return $this;
    }

}