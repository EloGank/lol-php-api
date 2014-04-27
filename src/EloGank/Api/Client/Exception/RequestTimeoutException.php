<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Exception;

use EloGank\Api\Component\Exception\ArrayException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RequestTimeoutException extends ArrayException { }