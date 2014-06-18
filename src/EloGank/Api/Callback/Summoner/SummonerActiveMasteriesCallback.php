<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Callback\Summoner;

use EloGank\Api\Component\Callback\Callback;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class SummonerActiveMasteriesCallback extends Callback
{
    /**
     * {@inheritdoc}
     */
    public function getResult($result)
    {
        foreach ($result['bookPages'] as $bookPage) {
            if (true === $bookPage['current']) {
                return ['activeMasteries' => $bookPage];
            }
        }


        return ['activeMasteries' => []];
    }
}