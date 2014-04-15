<?php

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Controller\Controller;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class TestController extends Controller
{
    /**
     * @param string $summonerName
     *
     * @return array
     */
    public function getTestMethodAction($summonerName)
    {
        var_dump($this->call('other_test.other_test_method', array(123456)));

        return $this->getClient()->syncInvoke('summonerService', 'getSummonerByName', array(
            $summonerName
        ));
    }
} 