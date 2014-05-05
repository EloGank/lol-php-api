<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\RTMP;

use EloGank\Api\Client\Exception\RequestTimeoutException;
use EloGank\Api\Component\Configuration\ConfigurationLoader;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RTMPSocket
{
    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @param string $protocol
     * @param string $server
     * @param int    $port
     */
    public function __construct($protocol, $server, $port)
    {
        $this->socket = stream_socket_client(sprintf('%s://%s:%d', $protocol, $server, $port), $errorCode, $errorMessage);
        if (0 != $errorCode) {
            $this->errorMessage = $errorMessage;
        }
        else {
            $this->timeout = (int) ConfigurationLoader::get('client.request.timeout');
            if (1 > $this->timeout) {
                $this->timeout = 5;
            }

            stream_set_timeout($this->socket, $this->timeout);
        }
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return null != $this->errorMessage;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param mixed    $data
     * @param int|null $start
     * @param int|null $end
     */
    public function write($data, $start = null, $end = null)
    {
        if (null !== $start && null == $end) {
            $data = substr($data, $start);
        }
        elseif (null !== $start && null !== $end) {
            $data = substr($data, $start, $end);
        }

        fwrite($this->socket, $data);
    }

    /**
     * @param mixed $bytes
     */
    public function writeBytes($bytes)
    {
        $this->write(chr($bytes));
    }

    /**
     * @param int $int
     */
    public function writeInt($int)
    {
        $this->write(pack('N', $int));
    }

    /**
     * @param int $length
     *
     * @return mixed
     *
     * @throws RequestTimeoutException
     */
    public function read($length = 1)
    {
        $read   = [$this->socket];
        $write  = null;
        $except = null;

        $n = stream_select($read, $write, $except, $this->timeout);
        if (false === $n) {
            return null;
        }
        elseif (0 < $n) {
            return fread($this->socket, $length);
        }

        throw new RequestTimeoutException('Request timeout, the client will reconnect');
    }

    /**
     * @param int $length
     *
     * @return mixed
     */
    public function readBytes($length = 1)
    {
        return unpack('C', $this->read($length))[1];
    }

    /**
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }
}