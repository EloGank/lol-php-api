<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Controller\Exception;

use EloGank\Api\Server\Exception\ServerException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ApiException extends ServerException
{
    /**
     * @var string
     */
    protected $cause;

    /**
     * @param string $cause
     * @param string $message
     */
    public function __construct($cause, $message)
    {
        $this->cause = $cause;

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getCause()
    {
        return $this->cause;
    }
}
