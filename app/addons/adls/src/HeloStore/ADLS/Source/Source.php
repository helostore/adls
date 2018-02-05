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

namespace HeloStore\ADLS\Source;

use HeloStore\ADLS\Entity;

/**
 * Class Source. Represents the source code repository of a product. A multi-platform product can have multiple sources (one for each platform).
 * 
 * @package HeloStore\ADLS
 */
class Source extends Entity
{
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
    protected $sourcePath;

    /**
     * @var string
     */
    protected $releasePath;

    /**
     * @var integer
     */
    protected $platformId;


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
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     *
     * @return Source
     */
    public function setSourcePath($sourcePath)
    {
        $this->sourcePath = $sourcePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getReleasePath()
    {
        return $this->releasePath;
    }

    /**
     * @param string $releasePath
     *
     * @return Source
     */
    public function setReleasePath($releasePath)
    {
        $this->releasePath = $releasePath;

        return $this;
    }

    /**
     * @return int
     */
    public function getPlatformId()
    {
        return $this->platformId;
    }

    /**
     * @param int $platformId
     *
     * @return Source
     */
    public function setPlatformId($platformId)
    {
        $this->platformId = $platformId;

        return $this;
    }
}