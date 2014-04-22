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
class JsonException extends \Exception
{
    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode(array(
            'success' => false,
            'error'   => array(
                'caused_by' => get_called_class(),
                'message'   => $this->getMessage()
            )
        ));
    }
} 