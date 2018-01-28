<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2017-12-31
 * Time: 11:29
 */

namespace HeloStore\ADLS\Compatibility;

use HeloStore\ADLS\Entity;

/**
 * Class Compatibility
 * @package HeloStore\ADLS\Reelease
 */
class Compatibility extends Entity
{
    /**
     * @var integer
     */
    protected $releaseId;
    /**
     * @var integer
     */
    protected $platformVersionId;
    /**
     * @var integer
     */
    protected $platformId;
    /**
     * @var integer
     */
    protected $editionId;
    /**
     * @var integer
     */
    protected $productId;

    /**
     * @return int
     */
    public function getReleaseId()
    {
        return $this->releaseId;
    }

    /**
     * @param int $releaseId
     *
     * @return Compatibility
     */
    public function setReleaseId($releaseId)
    {
        $this->releaseId = $releaseId;

        return $this;
    }

    /**
     * @return int
     */
    public function getPlatformVersionId()
    {
        return $this->platformVersionId;
    }

    /**
     * @param int $platformVersionId
     *
     * @return Compatibility
     */
    public function setPlatformVersionId($platformVersionId)
    {
        $this->platformVersionId = $platformVersionId;

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
     * @return Compatibility
     */
    public function setPlatformId($platformId)
    {
        $this->platformId = $platformId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEditionId()
    {
        return $this->editionId;
    }

    /**
     * @param int $editionId
     *
     * @return Compatibility
     */
    public function setEditionId($editionId)
    {
        $this->editionId = $editionId;

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
     * @return Compatibility
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return
            $this->getExtra('platform$name') . ' ' .
            $this->getExtra('edition$name') . ' ' .
            $this->getExtra('platform$version') . ' ';
    }
}