<?php

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Controller\Controller;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class TestController extends Controller
{
    /**
     * @param $summonerName
     * @param $accountId
     * @param $summonerId
     *
     * @return mixed
     */
    public function getTestMethodAction($summonerName, $accountId, $summonerId)
    {
        $time = microtime(true);
        $invokeIds = [
            $this->getClient()->invoke('summonerService', 'getSummonerByName', array($summonerName), function ($result) {
                var_dump('callback 1');

                return $result;
            }),
            $this->getClient()->invoke('summonerService', 'getAllPublicSummonerDataByAccount', array($accountId)),
            $this->getClient()->invoke('masteryBookService', 'getMasteryBook', array($summonerId)),
            $this->getClient()->invoke('playerStatsService', 'getRecentGames', array($accountId), function ($result) {
                var_dump('callback 2');

                return $result;
            }),
            $this->getClient()->invoke('leaguesServiceProxy', 'getAllLeaguesForPlayer', array($summonerId)),
            $this->getClient()->invoke('playerStatsService', 'retrieveTopPlayedChampions', array($accountId, 'CLASSIC')),
            $this->getClient()->invoke('playerStatsService', 'getAggregatedStats', array($accountId, 'CLASSIC', 4))
        ];

        $results = [];
        $client = $this->getClient();
        foreach ($invokeIds as $invokeId) {
            $results[] = $client->getResults($invokeId);
        }

        return microtime(true) - $time;
    }
} 