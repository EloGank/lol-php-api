<?php

namespace EloGank\Api\Command;

use EloGank\Api\Component\Command\Command;
use EloGank\Api\Component\Routing\Router;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RouterDumpCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('elogank:router:dump')
            ->setDescription('Dump all available API routes')
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
        $this->writeSection($output, 'Router : dump');

        $router = new Router();
        $router->init();

        $routes = $router->getRoutes();
        foreach ($routes as $controller => $methods) {
            $output->writeln(sprintf('%s : ', $controller));

            foreach ($methods as $method => $params) {
                $output->writeln(sprintf("\t- %s [%s]", $method, join(', ', $params)));
            }
        }
    }
}