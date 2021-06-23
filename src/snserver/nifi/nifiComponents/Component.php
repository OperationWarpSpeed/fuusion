<?php

namespace nifiComponents;

/**
 * Class component - Base class for all NIFI components
 * @package nifiComponents
 */
class Component
{
    public static $TYPE_PROCESS_GROUP = 0;
    public static $TYPE_PROCESSOR = 1;
    public static $TYPE_INPUT_PORT = 2;
    public static $TYPE_OUTPUT_PORT = 3;
    public static $TYPE_REMOTE_PROCESS_GROUP = 4;
    public static $TYPE_CONNECTION = 5;

    protected $id;
    protected $idInRPG;
    protected $name;
    /**
     * Component's x position in the Nifi UI
     * @var double
     */
    protected $x;
    /**
     * Component's y position in the Nifi UI
     * @var double
     */
    protected $y;
    protected $uri;
    public $type;

    /**
     * @var \stdClass All component's data, received from API
     */
    protected $apiData;

    public function __construct($name, $x, $y, $uri, $id, $type)
    {
        $this->name = $name;
        $this->x = $x;
        $this->y = $y;
        $this->id = $id;
        $this->uri = $uri;
        $this->type = $type;
        $this->idInRPG = "";
    }

    /**
     * @return string Component's id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string Component's id when accessed from a remote process group
     */
    public function getIdInRPG()
    {
        return $this->idInRPG;
    }

    /**
     * @param string Component's id when accessed from a remote process group
     */
    public function setIdInRPG($id)
    {
        $this->idInRPG = $id;
    }

    /**
     * @return string Component's Nifi API uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return double Component's x position in the Nifi UI
     */
    public function getX() {
        return $this->x;
    }

    /**
     * @return double Component's y position in the Nifi UI
     */
    public function getY() {
        return $this->y;
    }

    /**
     * @return string Component's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \stdClass
     */
    public function getApiData()
    {
        return $this->apiData;
    }

    /**
     * @param \stdClass $apiData
     */
    public function setApiData($apiData)
    {
        $this->apiData = $apiData;
        $this->id = $apiData->id;
        $this->uri = $apiData->uri;
    }
}
