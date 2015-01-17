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
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Controller\Controller;

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
     * NOTE: in the patch 5.1, the "game.retrieve_in_progress_spectator_game" route doesn't throw exception anymore but
     * a "NULL" response body.
     *
     * @param string $summonerName
     *
     * @return array
     *
     * @throws \EloGank\Api\Component\Controller\Exception\ApiException
     * @throws \Exception
     *
     * @deprecated Use "summoner.summoner_by_name" route instead
     */
    public function getSummonerExistenceAction($summonerName)
    {
        return $this->call('summoner.summoner_by_name', [$summonerName]);
    }

    /**
     * Return all data needed to show information about a summoner
     *
     * Example of parameters :
     * $parameters = [
     *     [
     *         ['accountId' => 11111, 'summonerId' => 2222222, 'summonerName' => 'Foo Bar'],
     *         ['accountId' => 44444, 'summonerId' => 3333333, 'summonerName' => 'Bar Foo']
     *     ],
     *     ['INFORMATION', 'MAIN_CHAMPION'] // filters
     * ];
     *
     * @param array $summonerData Summoner data, index by "accountId", "summonerId" and "summonerName". Example :
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
        $filtersByKey = array_flip($filters);

        foreach ($summonerData as $data) {
            $accountId = $data['accountId'];
            $summonerId = $data['summonerId'];
            $summonerName = $data['summonerName'];

            $formatResult = function ($result, $response) use ($summonerId) {
                foreach ($result as $key => $value) {
                    $response[$summonerId][$key] = $value;
                }

                return $response;
            };

            if (isset($filtersByKey['INFORMATION'])) {
                $this->onClientReady(function (LOLClientInterface $client) use ($formatResult, $summonerId, $summonerName) {
                    $invokeId = $client->invoke('summonerService', 'getSummonerByName', [$summonerName], new SummonerInformationCallback());
                    $this->fetchResult($invokeId, $formatResult);
                });
            }

            if (isset($filtersByKey['ACTIVE_SPELLBOOK'])) {
                $this->onClientReady(function (LOLClientInterface $client) use ($formatResult, $summonerId, $accountId) {
                    $invokeId = $client->invoke('summonerService', 'getAllPublicSummonerDataByAccount', [$accountId], new SummonerActiveSpellBookCallback());
                    $this->fetchResult($invokeId, $formatResult);
                });
            }

            if (isset($filtersByKey['ACTIVE_MASTERIES'])) {
                $this->onClientReady(function (LOLClientInterface $client) use ($formatResult, $summonerId) {
                    $invokeId = $client->invoke('masteryBookService', 'getMasteryBook', [$summonerId], new SummonerActiveMasteriesCallback());
                    $this->fetchResult($invokeId, $formatResult);
                });
            }

            if (isset($filtersByKey['LEAGUE_SOLO_5x5'])) {
                $this->onClientReady(function (LOLClientInterface $client) use ($formatResult, $summonerId) {
                    $invokeId = $client->invoke('leaguesServiceProxy', 'getAllLeaguesForPlayer', [$summonerId], new SummonerLeagueSolo5x5Callback([
                        'summonerId' => $summonerId
                    ]));
                    $this->fetchResult($invokeId, $formatResult);
                });
            }

            if (isset($filtersByKey['MAIN_CHAMPION']) || isset($filtersByKey['CHAMPIONS_DATA'])) {
                $this->onClientReady(function (LOLClientInterface $client) use ($formatResult, $summonerId, $accountId, $filtersByKey) {
                    $invokeId = $client->invoke('playerStatsService', 'getAggregatedStats', [$accountId, 'CLASSIC', 4], new SummonerChampionCallback([
                        'main_champion'  => isset($filtersByKey['MAIN_CHAMPION']),
                        'champions_data' => isset($filtersByKey['CHAMPIONS_DATA'])
                    ]));
                    $this->fetchResult($invokeId, $formatResult);
                });
            }
        }

        $this->sendResponse(function ($response) {
            return ['data' => $response];
        });
    }
}