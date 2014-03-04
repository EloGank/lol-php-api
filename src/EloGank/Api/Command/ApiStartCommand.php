<?php

namespace EloGank\Api\Command;

use EloGank\Api\Configuration\Config;
use EloGank\Api\Manager\ApiManager;
use EloGank\Api\Region\RegionFactory;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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

        $apiManager = new ApiManager(array(
            new ConsoleHandler($output, true, array(
                OutputInterface::VERBOSITY_NORMAL => Logger::INFO,
                OutputInterface::VERBOSITY_VERBOSE => Logger::NOTICE,
                OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::DEBUG,
                OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG
            ))
        ));

        $apiManager->connect();
    }

    protected function getConfigurationFilePath()
    {
        return __DIR__ . '/../../../../config/config.yml';
    }

    /**
     * @param OutputInterface $output
     *
     * @param string|null $sectionTitle
     */
    protected function writeSection(OutputInterface $output, $sectionTitle = null)
    {
        $sectionLength = 80;
        $section = str_pad('[', $sectionLength - 1, '=') . ']';
        $output->writeln(array(
            '',
            $section
        ));

        if (null != $sectionTitle) {
            $length = ($sectionLength - strlen($sectionTitle)) / 2;
            $output->writeln(array(
                str_pad('[', $length, ' ') . $sectionTitle . str_pad('', $sectionLength - strlen($sectionTitle) - $length, ' ') . ']',
                $section
            ));
        }
    }
}