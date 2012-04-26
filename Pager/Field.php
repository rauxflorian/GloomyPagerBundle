<?php

namespace Gloomy\PagerBundle\Pager;

class Field
{
    public static $defaultDateFormat = 'Y-m-d H:i:s';

    protected $_property;

    protected $_qualifier;

    protected $_type;

    protected $_label;

    protected $_options;

    protected $_dateFormat;

    protected $_visible;

    public function __construct($property, $type = 'text', $label = '', $qualifier = null, $options = array())
    {
        $this->_property  = $property;
        $this->_type      = $type;
        $this->_label     = $label ?: ucfirst($property);
        $this->_qualifier = $qualifier ?: $property;
        $this->_options   = $options;
        $this->_visible   = true;
    }

    public function getProperty()
    {
        return $this->_property;
    }

    public function getQualifier()
    {
        return $this->_qualifier;
    }

    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setLabel($label)
    {
        $this->_label = $label;
        return $this;
    }

    public function getLabel()
    {
        return $this->_label;
    }

    public function setOptions($options)
    {
        $this->_options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setDateFormat($dateFormat)
    {
        $this->_dateFormat = $dateFormat;
        return $this;
    }

    public function getDateFormat()
    {
        if (is_null($this->_dateFormat)) {
            $this->_dateFormat = self::$defaultDateFormat;
        }
        return $this->_dateFormat;
    }

    public function show()
    {
        $this->_visible = true;
        return $this;
    }

    public function hide()
    {
        $this->_visible = false;
        return $this;
    }

    public function isVisible()
    {
        return $this->_visible;
    }
}
