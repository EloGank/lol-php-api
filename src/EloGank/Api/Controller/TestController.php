<?php

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Configuration\ConfigurationLoader;
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
        $clients = [
            $this->getClient()->invoke('summonerService', 'getSummonerByName', array($summonerName)),
            $this->getClient()->invoke('summonerService', 'getAllPublicSummonerDataByAccount', array($accountId)),
            $this->getClient()->invoke('masteryBookService', 'getMasteryBook', array($summonerId)),
            $this->getClient()->invoke('playerStatsService', 'getRecentGames', array($accountId)),
            $this->getClient()->invoke('leaguesServiceProxy', 'getAllLeaguesForPlayer', array($summonerId)),
            $this->getClient()->invoke('playerStatsService', 'retrieveTopPlayedChampions', array($accountId, 'CLASSIC')),
            $this->getClient()->invoke('playerStatsService', 'getAggregatedStats', array($accountId, 'CLASSIC', 4)),
        ];

        $results = [];
        foreach ($clients as $client) {
            $results[] = $client->getResults();
        }

        file_put_contents(ConfigurationLoader::get('cache.path') . '/test.log', serialize($results));

        return microtime(true) - $time;
    }
} 