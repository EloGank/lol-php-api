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
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class GameController extends Controller
{
    /**
     * Return all data needed to show information about a summoner current game
     *
     * @param int $accountId
     * @param int $summonerId
     *
     * @return array
     */
    public function getAllSummonerDataCurrentGameAction($accountId, $summonerId)
    {
        $invokeIds = [
            $this->getClient()->invoke('summonerService', 'getAllPublicSummonerDataByAccount', [$accountId], function ($result) {
                foreach ($result['spellBook']['bookPages'] as $bookPage) {
                    if (true === $bookPage['current']) {
                        return ['spellBook' => $bookPage];
                    }
                }

                return ['spellBook' => []];
            }),
            $this->getClient()->invoke('masteryBookService', 'getMasteryBook', [$summonerId], function ($result) {
                foreach ($result['bookPages'] as $bookPage) {
                    if (true === $bookPage['current']) {
                        return ['masteryBook' => $bookPage];
                    }
                }

                return ['masteryBook' => []];
            }),
            $this->getClient()->invoke('playerStatsService', 'getRecentGames', [$summonerId], function ($result) {
                return ['recentGames' => $result['gameStatistics']];
            }),
            $this->getClient()->invoke('leaguesServiceProxy', 'getAllLeaguesForPlayer', [$summonerId], function ($result) use ($summonerId) {
                foreach ($result['summonerLeagues'] as $summonerLeague) {
                    if ('RANKED_SOLO_5x5' != $summonerLeague['queue']) {
                        continue;
                    }

                    foreach ($summonerLeague['entries'] as $entry) {
                        if ($summonerId == $entry['playerOrTeamId']) {
                            return ['league' => [
                                'name'  => $summonerLeague['name'],
                                'queue' => $summonerLeague['queue'],
                                'data'  => $entry
                            ]];
                        }
                    }
                }

                return ['league' => []];
            }),
            $this->getClient()->invoke('playerStatsService', 'retrieveTopPlayedChampions', [$accountId, 'CLASSIC'], function ($result) {
                if (!isset($result[0])) {
                    return ['mainChampion' => null];
                }

                return ['mainChampion' => $result[1]['championId']];
            }),
        ];

        $results = [];
        foreach ($invokeIds as $invokeId) {
            $result = $this->getResult($invokeId);
            reset($result);
            $key = key($result);

            $results[$key] = $result[$key];
        }

        return $this->view($results);
    }
} 