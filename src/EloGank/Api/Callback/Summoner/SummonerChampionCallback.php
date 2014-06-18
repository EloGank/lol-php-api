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
class SummonerChampionCallback extends Callback
{
    /**
     * {@inheritdoc}
     */
    public function getResult($result)
    {
        if (!isset($result[0])) {
            $emptyData = [];
            if (true === $this->options['main_champion']) {
                $emptyData['mainChampion'] = null;
            }

            if (true === $this->options['champions_data']) {
                $emptyData['champions'];
            }

            return $emptyData;
        }

        $data = [];
        if (true === $this->options['main_champion']) {
            $data['mainChampionId'] = $result[1]['championId'];
        }

        if (true === $this->options['champions_data']) {
            $data['champions'] = $result;
        }

        return $data;
    }

    protected function getRequiredOptions()
    {
        return [
            'main_champion', // boolean
            'champions_data'  // boolean
        ];
    }
}