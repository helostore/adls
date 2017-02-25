<?php
/**
 * Created by PhpStorm.
 * User: WSergio
 * Date: 02-Feb-17
 * Time: 19:34
 */

namespace HeloStore\ADLS;


class Entity
{
    /**
     * Keep here the entity$prop variables for now
     *
     * @var array
     */
    public $extra = array();


    /**
     * Entity constructor.
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $vars = get_object_vars($this);
        $array = array();
        foreach ($vars as $field => $value) {
            if (in_array($field, array('_fieldsMap')) || $field == 'extra') {
                continue;
            }
            if (in_array($field, array(
                'startDate',
                'endDate',
                'updatedAt',
                'createdAt',
                'date'))) {
                $value = ($value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value);
            }
//            $k = Utils::instance()->camelToSnake($k);
            $array[$field] = $value;
        }

        return $array;
    }

    /**
     * @param $data
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function fromArray($data)
    {
        $class = get_class($this);
        foreach ($data as $field => $v) {
//            $field = Utils::instance()->snakeToCamel($k);

            // Skip special variables (e.g. user$email will be used to hydrate $this->user
            if (strstr($field, '$') != false) {
                $this->extra[$field] = $v;
                continue;
            }
            if (!empty($this->_fieldsMap)) {
                $field = str_replace(array_keys($this->_fieldsMap), array_values($this->_fieldsMap), $field);
            }

            if (!property_exists($class, $field)) {
                throw new \Exception('fromArray(): unknown field in class ' . $class . ': ' . $field . ', ' . print_r($data, true));
            }

            if (in_array($field, array(
                'startDate',
                'endDate',
                'updatedAt',
                'createdAt',
                'date'))) {

                $v = (!empty($v) ? \DateTime::createFromFormat('Y-m-d H:i:s', $v) : null);
            }

            $this->$field = $v;
        }

        return $this;
    }
}