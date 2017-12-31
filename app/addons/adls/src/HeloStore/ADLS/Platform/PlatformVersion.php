<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2017-12-31
 * Time: 11:29
 */

namespace HeloStore\ADLS\Platform;

use HeloStore\ADLS\Entity;

class PlatformVersion extends Entity
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $platformId;

    /**
     * @var integer
     */
    protected $editionId;

    /**
     * @var string
     */

    protected $version;

    /**
     * @var string
     */

    protected $description;

    /**
     * @var \DateTime
     */
    protected $releaseDate;

    /**
     * @return int
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return PlatformVersion
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * @return PlatformVersion
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
     * @return PlatformVersion
     */
    public function setEditionId($editionId)
    {
        $this->editionId = $editionId;

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
     *
     * @return PlatformVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return PlatformVersion
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * @param \DateTime $releaseDate
     *
     * @return PlatformVersion
     */
    public function setReleaseDate($releaseDate)
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }
}