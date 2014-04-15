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
    public function getTestMethod($summonerName)
    {
        return $this->getClient()->syncInvoke('summonerService', 'getSummonerByName', array(
            $summonerName
        ));
    }
} 