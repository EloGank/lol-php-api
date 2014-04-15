<?php

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Controller\Controller;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class OtherTestController extends Controller
{
    public function otherTestMethodAction($accountId)
    {
        return $this->getClient()->syncInvoke('summonerService', 'getAllPublicSummonerDataByAccount', array(
            $accountId
        ));
    }
} 