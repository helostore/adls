<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 2018-01-21
 * Time: 20:24
 */

namespace HeloStore\ADLS;


class Version
{
    public $major;
    public $minor;
    public $patch;

    /**
     * Version constructor.
     *
     * @param $major
     * @param $minor
     * @param $patch
     */
    public function __construct($major = 0 , $minor = 0, $patch = 0)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
    }
}