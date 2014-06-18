<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Callback;

use EloGank\Api\Component\Callback\Exception\MissingOptionCallbackException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
abstract class Callback
{
    /**
     * @var array
     */
    protected $options;


    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;

        $this->compareOptions();
    }

    /**
     * @throws MissingOptionCallbackException
     */
    private function compareOptions()
    {
        $requiredOptions = $this->getRequiredOptions();
        if (!isset($requiredOptions[0])) {
            return;
        }

        foreach ($requiredOptions as $optionKey) {
            if (!isset($this->options[$optionKey])) {
                throw new MissingOptionCallbackException('The option "' . $optionKey . '" is missing');
            }
        }
    }

    /**
     * Set your required options here, if one or more options are missing, an exception will be thrown
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return array();
    }

    /**
     * Parse the API result and return the new content
     *
     * @param array|string $result
     *
     * @return mixed
     */
    public abstract function getResult($result);
} 