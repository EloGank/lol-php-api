<?php

namespace EloGank\Api\Region;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Region implements RegionInterface
{
    /**
     * @var string
     */
    protected $uniqueName;

    /**
     * @var string
     */
    protected $server;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $loginQueue;


    /**
     * @param string $uniqueName
     * @param string $name
     * @param string $server
     * @param string $loginQueue
     */
    public function __construct($uniqueName, $name, $server, $loginQueue)
    {
        $this->uniqueName = $uniqueName;
        $this->name       = $name;
        $this->server     = $server;
        $this->loginQueue = $this->setLoginQueue($loginQueue);
    }

    /**
     * @param string $loginQueue
     *
     * @return string
     */
    protected function setLoginQueue($loginQueue)
    {
        if ('/' == $loginQueue[strlen($loginQueue) - 1]) {
            $loginQueue = substr($loginQueue, 0, -1);
        }

        return 'https://' . $loginQueue;
    }

    /**
     * @return string
     */
    public function getLoginQueue()
    {
        return $this->loginQueue;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $uniqueName
     */
    public function setUniqueName($uniqueName)
    {
        $this->uniqueName = $uniqueName;
    }

    /**
     * @return string
     */
    public function getUniqueName()
    {
        return $this->uniqueName;
    }
}