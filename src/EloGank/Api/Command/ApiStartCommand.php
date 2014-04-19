<?php

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
            ->setDescription('Start the EloGank League of Legends API')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
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
            $apiManager->clean();
            $this->getApplication()->renderException($e, $output);

            posix_kill(getmypid(), SIGKILL);
        }
    }
}