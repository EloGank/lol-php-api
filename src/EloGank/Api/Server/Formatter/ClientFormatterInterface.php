<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Server\Formatter;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
interface ClientFormatterInterface
{
    /**
     * Format the result into another format
     *
     * @param array $results
     *
     * @return mixed
     */
    public function format(array $results);
} 