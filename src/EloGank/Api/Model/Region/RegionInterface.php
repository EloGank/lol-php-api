<?php

namespace EloGank\Api\Model\Region;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
interface RegionInterface
{
    /**
     * @return string
     */
    public function getLoginQueue();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getServer();

    /**
     * @return string
     */
    public function getUniqueName();
} 