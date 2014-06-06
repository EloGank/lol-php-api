<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Command;

use EloGank\Api\Component\Command\Command;
use EloGank\Api\Manager\ApiManager;
use EloGank\Api\Server\Server;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ApiStartCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('elogank:api:start')
            ->setDescription('Start the EloGank League of Legends API server')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, 'EloGank - League of Legends API');

        $apiManager = new ApiManager();
        try {
            $server = new Server($apiManager);
            $server->listen();
        }
        catch (\Exception $e) {
            $this->getApplication()->renderException($e, $output);
            $apiManager->getLogger()->critical($e);

            $apiManager->clean();

            // Need to be killed manually
            posix_kill(getmypid(), SIGKILL);
        }
    }
}