<?php

namespace EloGank\Api\Client\RTMP;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RTMPSocket extends \Stackable
{
    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    private $errorMessage;

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
     */
    public function read($length = 1)
    {
        return fread($this->socket, $length);
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