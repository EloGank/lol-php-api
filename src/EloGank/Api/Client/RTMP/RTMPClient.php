<?php

namespace EloGank\Api\Client\RTMP;

use EloGank\Api\Client\Exception\AuthException;
use EloGank\Api\Client\Exception\PacketException;
use EloGank\Api\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * RTMP client class, based on the Gabriel Van Eyck's work (vaneyckster@gmail.com)
 * @see https://code.google.com/p/lolrtmpsclient/source/browse/trunk/src/com/gvaneyck/rtmp/RTMPSClient.java
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RTMPClient
{
    /**
     * @var string
     */
    protected $server;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    private $app;

    /**
     * @var string
     */
    private $swfUrl;

    /**
     * @var string
     */
    private $pageUrl;

    /**
     * @var bool
     */
    private $isSecure;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var int
     */
    private $DSId;

    /**
     * @var int
     */
    private $invokeId = 1;

    /**
     * @var RTMPSocket
     */
    protected $socket;


    /**
     * @param string          $server
     * @param string          $port
     * @param string          $app
     * @param string          $swfUrl
     * @param string          $pageUrl
     * @param bool            $isSecure
     */
    public function __construct($server, $port, $app, $swfUrl, $pageUrl, $isSecure = true)
    {
        $this->server   = $server;
        $this->port     = $port;
        $this->app      = $app;
        $this->swfUrl   = $swfUrl;
        $this->pageUrl  = $pageUrl;
        $this->isSecure = $isSecure;

        $this->startTime = time();
    }

    /**
     * @return array
     */
    public function connect()
    {
        $this->createSocket();

        $this->doHandshake();
        $this->encodeConnectionPacket();

        return $this->getResponse();
    }

    /**
     * @throws \RuntimeException
     */
    protected function createSocket()
    {
        $protocol = 'tcp';
        if ($this->isSecure) {
            $protocol = 'ssl';
        }

        $this->socket = new RTMPSocket($protocol, $this->server, $this->port);
        if ($this->socket->hasError()) {
            throw new AuthException('Error when connecting to server: ' . $this->socket->getErrorMessage());
        }
    }

    /**
     * @throws \EloGank\Api\Client\Exception\AuthException
     */
    protected function doHandshake()
    {
        // C0
        $this->socket->writeBytes(0x03);

        // C1
        $this->socket->writeInt(time());
        $this->socket->writeInt(0);
        $randC1 = str_pad("", 1528, 'x'); // used later
        $this->socket->write($randC1);

        // S0
        $version = $this->socket->readBytes();
        if (0x03 != $version) {
            throw new AuthException('Wrong handshake version (' . $version . ')');
        }

        // S1
        $sign = $this->socket->read(1536);

        // C2
        $this->socket->write($sign, 0, 4);
        $this->socket->writeInt(time());
        $this->socket->write($sign, 8);

        // S2
        $sign = $this->socket->read(1536);

        for ($i=8; $i<1536; $i++) {
            if ($randC1[$i - 8] != $sign[$i]) {
                throw new AuthException('Invalid handshake');
            }
        }
    }

    /**
     * Get the packet response
     *
     * @return array
     *
     * @throws \EloGank\Api\Client\Exception\AuthException
     */
    protected function getResponse()
    {
        $response = $this->parsePacket();

        if ('NetConnection.Connect.Success' != $response['data']['code']) {
            throw new AuthException('Connection failed');
        }

        $this->DSId = $response['data']['id'];

        return $response;
    }

    /**
     * Create and encode the connection packet
     */
    protected function encodeConnectionPacket()
    {
        $parameters = array(
            'app'            => $this->app,
            'flashVer'       => 'WIN 10,1,85,3',
            'swfUrl'         => $this->swfUrl,
            'tcUrl'          => sprintf('rtmps://%s:%d', $this->server, $this->port),
            'fpad'           => false,
            'capabilities'   => 239,
            'audioCodecs'    => 3191,
            'videoCodecs'    => 252,
            'pageUrl'        => $this->pageUrl,
            'objectEncoding' => 3
        );

        $output = new \SabreAMF_OutputStream();
        $amf3   = new \SabreAMF_AMF3_Serializer($output);
        $amf    = new \SabreAMF_AMF0_Serializer($output);

        $amf->writeAMFData('connect');
        $amf->writeAMFData(1); // the invokeId

        // Parameters
        $output->writeByte(0x11); // AMF3 object
        $output->writeByte(0x09); // array

        $output->writeByte(0x01);
        foreach($parameters as $name => $value) {
            $amf3->writeString($name);
            $amf3->writeAMFData($value);
        }
        $output->writeByte(0x01);

        // Service call arguments
        $output->writeByte(0x01);
        $output->writeByte(0x00);
        $amf->writeAMFData('nil');
        $amf->writeAMFData('', \SabreAMF_AMF0_Const::DT_STRING);

        $commandMessageObject = new \SabreAMF_AMF3_CommandMessage();
        $commandData = array(
            'messageRefType' => null,
            'operation'      => 5,
            'correlationId'  => '',
            'clientId'       => null,
            'destination'    => null,
            'messageId'      => $commandMessageObject->generateRandomId(),
            'timestamp'      => 0.0,
            'timeToLive'     => 0.0,
            'body'           => new \SabreAMF_TypedObject('', array()),
            'header'         => array(
                'DSMessagingVersion' => 1.0,
                'DSId'               => 'my-rtmps'
            )
        );

        $commandMessage = new \SabreAMF_TypedObject("flex.messaging.messages.CommandMessage", $commandData);
        $output->writeByte(0x11); // amf3
        $amf3->writeAMFData($commandMessage);

        $packet = $this->addHeaders($output->getRawData());
        $packet[7] = chr(0x14); // message type

        $this->socket->write($packet);
    }

    /**
     * @return array
     *
     * @throws \EloGank\Api\Client\Exception\PacketException
     */
    protected function parsePacket()
    {
        $packets = array();

        while (true) {
            $headerBasic = ord($this->socket->read(1));
            $channel = $headerBasic & 0x2F;
            $headerType = $headerBasic & 0xC0;
            $headerSize = 0;

            switch ($headerType) {
                case 0x00:
                    $headerSize = 12;
                break;
                case 0x40:
                    $headerSize = 8;
                break;
                case 0x80:
                    $headerSize = 4;
                break;
                case 0xC0:
                    $headerSize = 1;
                break;
            }

            if (!isset($packets[$channel])) {
                $packets[$channel] = array(
                    'data' => ''
                );
            }

            $packet = &$packets[$channel];

            // Parse the header
            if ($headerSize > 1) {
                $header = $this->socket->read($headerSize - 1);

                if ($headerSize >= 8) {
                    $size = 0;
                    for ($i = 3; $i < 6; $i++) {
                        $size *= 256;
                        $size += (ord(substr($header, $i, 1)) & 0xFF);
                    }

                    $packet['size'] = $size;
                    $packet['type'] = ord($header[6]);
                }
            }

            // Parse the content
            for ($i = 0; $i < 128; $i++) {
                if (!feof($this->socket->getSocket())) {
                    $packet['data'] .= $this->socket->read(1);
                }

                if (strlen($packet['data']) == $packet['size']) {
                    break;
                }
            }

            if (!(strlen($packet['data']) == $packet['size'])) {
                continue;
            }

            // Remove the read packet
            unset($packets[$channel]);

            $result = array();
            $input = new \SabreAMF_InputStream($packet['data']);

            switch ($packet['type']) {
                case 0x14: // decode connect
                    $decoder = new \SabreAMF_AMF0_Deserializer($input);
                    $result['result']      = $decoder->readAMFData();
                    $result['invokeId']    = $decoder->readAMFData();
                    $result['serviceCall'] = $decoder->readAMFData();
                    $result['data']        = $decoder->readAMFData();

                    try {
                        $input->readByte();

                        throw new PacketException('id not consume entire buffer');
                    }
                    catch (\Exception $e) {
                        // good
                    }
                break;

                case 0x11:
                    if ($input->readByte() == 0x00) {
                        $packet['data']    = substr($packet['data'], 1);
                        $result['version'] = 0x00;
                    }

                    $decoder = new \SabreAMF_AMF0_Deserializer($input);
                    $result['result']      = $decoder->readAMFData();
                    $result['invokeId']    = $decoder->readAMFData();
                    $result['serviceCall'] = $decoder->readAMFData();
                    $result['data']        = $decoder->readAMFData();

                    try {
                        $input->readByte();

                        throw new PacketException('id not consume entire buffer');
                    }
                    catch (\Exception $e) {
                        // good
                    }
                break;

                case 0x03: // ack
                case 0x06: // bandwidth
                    continue 2;
                default:
                    throw new PacketException('Unknown message type');
            }

            if (!isset($result['invokeId'])) {
                throw new PacketException("Error after decoding packet");
            }

            $invokeId = $result['invokeId'];

            if ($invokeId == null || $invokeId == 0) {
                throw new PacketException('Unknown invokeId: ' . $invokeId);
            }
            // TODO callbacks
            //elseif (isset($callbacks[$invokeId])) { }

            return $result;
        }
    }

    /**
     * Add header content to data
     *
     * @param string $data
     *
     * @return string
     */
    protected function addHeaders($data)
    {
        // Header
        $result = chr(0x03);

        // Timestamp
        $diff = (int)(microtime(true) * 1000 - $this->startTime);
        $result .= chr((($diff & 0xFF0000) >> 16));
        $result .= chr((($diff & 0x00FF00) >> 8));
        $result .= chr((($diff & 0x0000FF)));

        // Body size
        $result .= chr(((strlen($data) & 0xFF0000) >> 16));
        $result .= chr(((strlen($data) & 0x00FF00) >> 8));
        $result .= chr(((strlen($data) & 0x0000FF)));

        // Content type
        $result .= chr(0x11);

        // Source ID
        $result .= chr(0x00);
        $result .= chr(0x00);
        $result .= chr(0x00);
        $result .= chr(0x00);

        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $result .= $data[$i];

            if ($i % 128 == 127 && $i != $length - 1){
                $result .= chr(0xC3);
            }
        }

        return $result;
    }

    /**
     * @param $destination
     * @param $operation
     * @param array $parameters
     * @param string $packetClass
     * @param array $headers
     * @param array $body
     *
     * @return int
     */
    protected function invoke($destination, $operation, $parameters = array(), $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array())
    {
        $packet = new RTMPPacket($destination, $operation, $parameters, $packetClass, $headers, $body);
        $packet->build($this->DSId);

        $output = new \SabreAMF_OutputStream();
        $amf    = new \SabreAMF_AMF0_Serializer($output);
        $amf3   = new \SabreAMF_AMF3_Serializer($output);

        $nextInvokeId = ++$this->invokeId;

        $output->writeByte(0x00);
        $output->writeByte(0x05);
        $amf->writeAMFData($nextInvokeId);
        $output->writeByte(0x05);
        $output->writeByte(0x11);
        $amf3->writeAMFData($packet->getData());
        $ret = $this->addHeaders($output->getRawData());

        $this->socket->write($ret);

        return $nextInvokeId;
    }

    /**
     * @param string $destionation
     * @param string $operation
     * @param array  $parameters
     * @param string $packetClass
     * @param array  $headers
     * @param array  $body
     *
     * @return array
     */
    public function syncInvoke($destionation, $operation, $parameters = array(), $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array())
    {
        $this->invoke($destionation, $operation, $parameters, $packetClass, $headers, $body);

        return $this->parsePacket();
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return LoggerFactory::create('RTMPClient');
    }
}