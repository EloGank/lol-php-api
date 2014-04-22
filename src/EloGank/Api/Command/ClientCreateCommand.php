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

use EloGank\Api\Client\Worker\ClientWorker;
use EloGank\Api\Client\Factory\ClientFactory;
use EloGank\Api\Component\Command\Command;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Logging\LoggerFactory;
use Predis\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientCreateCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('elogank:client:create')
            ->setDescription('Create a new API asynchronous client worker')
            ->addArgument('account_key', InputArgument::REQUIRED, 'The account key in configuration')
            ->addArgument('client_id', InputArgument::REQUIRED, 'The client id')
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
        $redis  = new Client(sprintf('tcp://%s:%d', ConfigurationLoader::get('client.async.redis.host'), ConfigurationLoader::get('client.async.redis.port')));
        $logger = LoggerFactory::create('Client #' . $input->getArgument('client_id'), true);
        $client = ClientFactory::create(
            $logger,
            $redis,
            $input->getArgument('account_key'),
            $input->getArgument('client_id'),
            true
        );

        $connector = new ClientWorker($logger, $client, $redis);
        $connector->listen();
    }
}