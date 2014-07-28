<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Exception;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ArrayException extends \Exception
{
    /**
     * @return string
     */
    public function getCause()
    {
        return get_called_class();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'success' => false,
            'error'   => [
                'caused_by' => $this->getCause(),
                'message'   => $this->getMessage()
            ]
        ];
    }
} 