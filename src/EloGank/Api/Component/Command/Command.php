<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
abstract class Command extends BaseCommand
{
    /**
     * Write a section title
     *
     * @param OutputInterface $output
     * @param string|null     $sectionTitle
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

        $output->writeln('');
    }
}