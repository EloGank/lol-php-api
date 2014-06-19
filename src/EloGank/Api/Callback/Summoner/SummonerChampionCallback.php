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
        if (!isset($result['lifetimeStatistics'][0])) {
            $emptyData = [];
            if (true === $this->options['main_champion']) {
                $emptyData['mainChampion'] = null;
            }

            if (true === $this->options['champions_data']) {
                $emptyData['champions'] = null;
            }

            return $emptyData;
        }

        $data = [];
        if (true === $this->options['main_champion']) {
            $totalPlayedSession = 0;
            $mainChampionId = null;

            foreach ($result['lifetimeStatistics'] as $championData) {
                if ('TOTAL_SESSIONS_PLAYED' == $championData['statType'] && $championData['value'] > $totalPlayedSession) {
                    $mainChampionId = $championData['championId'];
                }
            }

            $data['mainChampionId'] = $mainChampionId;
        }

        if (true === $this->options['champions_data']) {
            $dataByChampionId = [];
            foreach ($result['lifetimeStatistics'] as $championData) {
                $dataByChampionId[$championData['championId']][] = [
                    'statType' => $championData['statType'],
                    'value'    => $championData['value']
                ];
            }

            $data['champions'] = $dataByChampionId;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredOptions()
    {
        return [
            'main_champion', // boolean
            'champions_data'  // boolean
        ];
    }
}