<?php

namespace nifiComponents;

class Connection extends Component
{
    /**
     * @var ProcessGroup|Component Could be either Process Group or Remote Process Group
     */
    private $sourceGroup;
    /**
     * @var Component
     */
    private $sourceComponent;
    /**
     * @var ProcessGroup|Component Could be either Process Group or Remote Process Group
     */
    private $destinationGroup;
    /**
     * @var Component
     */
    private $destinationComponent;
    public function __construct($uri, $id, $sourceProcessGroup, $sourceComponent, $destinationProcessGroup, $destinationComponent)
    {
        parent::__construct('', 0, 0, $uri, $id, Component::$TYPE_CONNECTION);
        $this->sourceGroup = $sourceProcessGroup;
        $this->sourceComponent = $sourceComponent;
        $this->destinationGroup = $destinationProcessGroup;
        $this->destinationComponent = $destinationComponent;
    }

    public function getSourceGroup()
    {
        return $this->sourceGroup;
    }

    public function getSourceComponent()
    {
        return $this->sourceComponent;
    }

    public function getDestinationGroup()
    {
        return $this->destinationGroup;
    }

    public function getDestinationComponent()
    {
        return $this->destinationComponent;
    }
}