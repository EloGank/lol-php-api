<?php

namespace EloGank\Api\Logger\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Predis\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RedisHandler extends AbstractProcessingHandler
{
    /**
     * @var \Predis\Client
     */
    private $redisClient;

    /**
     * @var string
     */
    private $redisKey;


    /**
     * @param Client $redis
     * @param string $key
     * @param int    $level
     * @param bool   $bubble
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Client $redis, $key, $level = Logger::DEBUG, $bubble = true)
    {
        $this->redisClient = $redis;
        $this->redisKey    = $key;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(array $record)
    {
        $this->redisClient->rpush($this->redisKey, sprintf('%s|%s', $record['level'], $record['message']));
    }

    /**
     * @return \Monolog\Formatter\FormatterInterface|LineFormatter
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}