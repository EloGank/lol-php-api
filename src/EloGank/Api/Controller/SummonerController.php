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
use EloGank\Api\Component\Controller\Exception\ApiException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class SummonerController extends Controller
{
    /**
     * Check if summoner exists and return his information.<br />
     * This method exist because the route "summoner.summoner_by_name" return the same behavior of an overloaded<br />
     * client when the player does not exist. So we use a trick with the route<br />
     * "game.retrieve_in_progress_spectator_game" and errors which return the accountId when the player exists and<br />
     * a specific error when the player is not found.
     *
     * @param string $summonerName
     *
     * @return array
     *
     * @throws \EloGank\Api\Component\Controller\Exception\ApiException
     * @throws \Exception
     */
    public function getSummonerExistenceAction($summonerName)
    {
        try {
            $this->getResult($this->getClient()->invoke('gameService', 'retrieveInProgressSpectatorGameInfo', [$summonerName]));
        }
        catch (ApiException $e) {
            if ('com.riotgames.platform.game.GameNotFoundException' == $e->getCause()) {
                // Summoner found, but not currently in game, return his information
                if (preg_match('/No Game for player [0-9]+ was found in the system!/', $e->getMessage())) {
                    return $this->call('summoner.summoner_by_name', [$summonerName]);
                }

                throw $e;
            }
        }

        return $this->call('summoner.summoner_by_name', [$summonerName]);
    }

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