<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Controller\Controller;

/**
 * This is a common controller, used by default API calls.
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class CommonController extends Controller
{
    /**
     * @param string       $destination
     * @param string       $service
     * @param string|array $parameters
     *
     * @return array
     */
    public function commonCall($destination, $service, $parameters)
    {
        return $this->view(
            $this->getResult(
                $this->getClient()->invoke($destination, $service, $parameters)
            )
        );
    }
} 