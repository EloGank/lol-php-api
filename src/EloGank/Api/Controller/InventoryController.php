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
class InventoryController extends Controller
{
    /**
     * Return the id of free rotation week champions indexed by the key "freeChampions"
     *
     * @return array
     */
    public function getAvailableFreeChampionsAction()
    {
        $invokeId = $this->getClient()->invoke('inventoryService', 'getAvailableChampions', [], function ($result) {
            $freeChampions = [];
            foreach ($result as $champion) {
                if (true === $champion['freeToPlay']) {
                    $freeChampions[] = $champion['championId'];
                }
            }

            return ['freeChampions' => $freeChampions];
        });

        return $this->view($this->getResult($invokeId));
    }
} 