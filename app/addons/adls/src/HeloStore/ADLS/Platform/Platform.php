<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2017-12-31
 * Time: 11:29
 */

namespace HeloStore\ADLS\Platform;

use HeloStore\ADLS\Entity;

class Platform extends Entity
{
    const SLUG_CSCART = 'cscart';
    const SLUG_WORDPRESS = 'wordpress';
    const SLUG_MAGENTO = 'magento';

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $slug;

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
     * @return Platform
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
     * @return Platform
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return Platform
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCSCart()
    {
        return Platform::SLUG_CSCART === $this->slug;
    }

    /**
     * @return bool
     */
    public function isWordPress()
    {
        return Platform::SLUG_WORDPRESS === $this->slug;
    }

    /**
     * @return bool
     */
    public function isMagento()
    {
        return Platform::SLUG_MAGENTO === $this->slug;
    }
}