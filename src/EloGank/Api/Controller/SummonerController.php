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

use EloGank\Api\Callback\Summoner\SummonerActiveMasteriesCallback;
use EloGank\Api\Callback\Summoner\SummonerActiveSpellBookCallback;
use EloGank\Api\Callback\Summoner\SummonerChampionCallback;
use EloGank\Api\Callback\Summoner\SummonerInformationCallback;
use EloGank\Api\Callback\Summoner\SummonerLeagueSolo5x5Callback;
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
     * Return all data needed to show information about a summoner
     *
     * @param array $summonerData Summoner data, index by "accountId", "summonerId" and "summonerName"
     * @param array $filters      Fetch only data passed in filters
     *   - INFORMATION: fetch the main summoner information like the level
     *   - ACTIVE_SPELLBOOK: fetch the active spell book
     *   - ACTIVE_MASTERIES: fetch the active masteries book
     *   - LEAGUE_SOLO_5x5: fetch the league solo 5x5 data, only for the summoner, not the entire league
     *   - MAIN_CHAMPION: fetch the main champion id
     *   - CHAMPIONS_DATA: fetch the ranked champions data
     *
     * @return array
     */
    public function getAllSummonerDataAction(array $summonerData, array $filters = [
        'INFORMATION', 'ACTIVE_SPELLBOOK', 'ACTIVE_MASTERIES', 'LEAGUE_SOLO_5x5', 'MAIN_CHAMPION', 'CHAMPIONS_DATA'
    ])
    {
        $invokeIds = [];
        $filtersByKey = array_flip($filters);

        foreach ($summonerData as $data) {
            $accountId = $data['accountId'];
            $summonerId = $data['summonerId'];
            $summonerName = $data['summonerName'];

            if (isset($filtersByKey['INFORMATION'])) {
                $invokeIds[$summonerId][] = $this->getClient()->invoke('summonerService', 'getSummonerByName', [$summonerName], new SummonerInformationCallback());
            }

            if (isset($filtersByKey['ACTIVE_SPELLBOOK'])) {
                $invokeIds[$summonerId][] = $this->getClient()->invoke('summonerService', 'getAllPublicSummonerDataByAccount', [$accountId], new SummonerActiveSpellBookCallback());
            }

            if (isset($filtersByKey['ACTIVE_MASTERIES'])) {
                $invokeIds[$summonerId][] = $this->getClient()->invoke('masteryBookService', 'getMasteryBook', [$summonerId], new SummonerActiveMasteriesCallback());
            }

            if (isset($filtersByKey['LEAGUE_SOLO_5x5'])) {
                $invokeIds[$summonerId][] = $this->getClient()->invoke('leaguesServiceProxy', 'getAllLeaguesForPlayer', [$summonerId], new SummonerLeagueSolo5x5Callback([
                    'summonerId' => $summonerId
                ]));
            }

            if (isset($filtersByKey['MAIN_CHAMPION']) || isset($filtersByKey['CHAMPIONS_DATA'])) {
                $invokeIds[$summonerId][] = $this->getClient()->invoke('playerStatsService', 'retrieveTopPlayedChampions', [$accountId, 'CLASSIC'], new SummonerChampionCallback([
                    'main_champion'  => isset($filtersByKey['MAIN_CHAMPION']),
                    'champions_data' => isset($filtersByKey['CHAMPIONS_DATA'])
                ]));
            }
        }

        $results = [];
        foreach ($invokeIds as $summonerId => $invokeIdsBySummoner) {
            foreach ($invokeIdsBySummoner as $invokeId) {
                $result = $this->getResult($invokeId);

                foreach ($result as $key => $value) {
                    $results[$summonerId][$key] = $value;
                }
            }
        }

        return $this->view(['data' => $results]);
    }
}