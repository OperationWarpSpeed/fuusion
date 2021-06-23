<?php

namespace nifiComponents;

/**
 *
 */
class ProcessGroup extends Component
{
    /**
     * @var Component[]|ProcessGroup[]
     */
    private $_components = array();

    /**
     * @var Connection[]
     */
    private $_connections = array();

    public function __construct($name, $x, $y, $uri, $id)
    {
        parent::__construct($name, $x, $y, $uri, $id, Component::$TYPE_PROCESS_GROUP);
    }

    /**
     * @param Component $component
     */
    public function addComponent($component)
    {
        array_push($this->_components, $component);
    }

    /**
     * @param string $name Component'ss name
     * @return bool|Component|ProcessGroup return Component or FALSE if component not found
     */
    public function getComponentByName($name)
    {
        foreach ($this->_components as $component) {
            if ($component->name == $name) {
                return $component;
            }
        }
        return FALSE;
    }

    /**
     * @param string $id Component's id
     * @return bool|Component return Component or FALSE if component not found
     */
    public function getComponentById($id)
    {
        foreach ($this->_components as $component) {
            if ($component->id == $id) {
                return $component;
            }
        }
        return FALSE;
    }

    /**
     * @param Connection $connection
     */
    public function addConnection($connection)
    {
        array_push($this->_connections, $connection);
    }

    /**
     * @param bool resource get connections from child process groups
     * @return Connection[] Connections
     */
    public function getConnections($recursive = false)
    {
        $coons = $this->_connections;
        if ($recursive) {
            foreach ($this->_components as $component) {
                if ($component->type == Component::$TYPE_PROCESS_GROUP) {
                    $coons = array_merge($coons, $component->getConnections(true));
                }
            }
        }
        return $coons;
    }

    /**
     * @return Component[]|ProcessGroup[] Components
     */
    public function getComponents()
    {
        return $this->_components;
    }
}
