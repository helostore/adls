<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2017-12-31
 * Time: 11:29
 */

namespace HeloStore\ADLS\Platform;

use HeloStore\ADLS\Entity;

class PlatformEdition  extends Entity
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
     * @var string
     */
    protected $name;

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
     * @return PlatformEdition
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return PlatformEdition
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return PlatformEdition
     */
    public function setPlatformId($platformId)
    {
        $this->platformId = $platformId;

        return $this;
    }
}