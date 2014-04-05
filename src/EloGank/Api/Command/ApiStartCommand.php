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
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, 'EloGank - League of Legends API');

        $server = new Server(new ApiManager());
        $server->listen();
    }
}