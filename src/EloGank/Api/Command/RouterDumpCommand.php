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
            ->setHelp(<<<EOF

This command dump all available controllers and methods (routes) for the API.

The output looks like :
<info>controller_name :</info>
\t<comment>method_name</comment> [parameter1, parameter2, ...]

EOF
            )
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
            $output->writeln(sprintf('<info>%s</info> : ', $controller));

            foreach ($methods as $method => $params) {
                $output->writeln(sprintf("  - <comment>%s</comment> :%s", $method, $this->formatParameters($method, $params)));
            }
        }
    }

    /**
     * @param string $methodName
     * @param array  $parameters
     *
     * @return string
     */
    protected function formatParameters($methodName, array $parameters)
    {
        $isWinOS = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
        $length = strlen($methodName);
        if ($isWinOS) {
            $length -= 2;
        }
        else {
            $length -= 1;
        }

        $length /= 8;
        if (!$isWinOS && is_float($length)) {
            ++$length;
        }

        $tabs = 6 - $length;
        $output = "";

        for ($i = 0; $i < $tabs; $i++) {
            $output .= "\t";
        }

        $output .= '[' . join(', ', $parameters) . ']';

        return $output;
    }
}