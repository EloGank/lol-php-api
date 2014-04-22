<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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