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
class SummonerLeagueSolo5x5Callback extends Callback
{
    /**
     * {@inheritdoc}
     */
    public function getResult($result)
    {
        foreach ($result['summonerLeagues'] as $summonerLeague) {
            if ('RANKED_SOLO_5x5' != $summonerLeague['queue']) {
                continue;
            }

            if (isset($this->options['full']) && true === $this->options['full']) {
                $league = [];
                foreach ($summonerLeague['entries'] as $entry) {
                    $league[] = [
                        'name'  => $summonerLeague['name'],
                        'queue' => $summonerLeague['queue'],
                        'data'  => $entry
                    ];
                }
            }

            // Return only the league data of the selected summoner
            foreach ($summonerLeague['entries'] as $entry) {
                if ($this->options['summonerId'] == $entry['playerOrTeamId']) {
                    return ['league' => [
                        'name'  => $summonerLeague['name'],
                        'queue' => $summonerLeague['queue'],
                        'data'  => $entry
                    ]];
                }
            }
        }

        return ['league' => []];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredOptions()
    {
        if (!isset($this->options['full']) || false === $this->options['full']) {
            return [
                'summonerId', // integer
            ];
        }

        return parent::getRequiredOptions();
    }
}