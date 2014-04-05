<?php

namespace EloGank\Api\Client\RTMP;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RTMPPacket
{
    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $additionnalHeaders;

    /**
     * @var \SabreAMF_TypedObject
     */
    private $headers;

    /**
     * @var array
     */
    private $additionnalBody;

    /**
     * @var \SabreAMF_TypedObject
     */
    private $data;

    /**
     * @var string
     */
    private $class;


    /**
     * @param string $destination
     * @param string $operation
     * @param array  $parameters
     * @param string $packetClass
     * @param array  $headers
     * @param array  $body
     */
    public function __construct($destination, $operation, $parameters, $packetClass, array $headers = array(), array $body = array())
    {
        $this->destination        = $destination;
        $this->operation          = $operation;
        $this->parameters         = $parameters;
        $this->class              = $packetClass;
        $this->additionnalHeaders = $headers;
        $this->additionnalBody    = $body;
    }

    /**
     * Build the packet's header
     *
     * @param int $destinationId
     */
    public function buildHeader($destinationId)
    {
        $this->headers = new \SabreAMF_TypedObject(null, array_merge(array(
            'DSRequestTimeout' => 60,
            'DSId'             => $destinationId,
            'DSEndpoint'       => 'my-rtmps'
        ), $this->additionnalHeaders));
    }

    /**
     * Build the packet's body
     */
    public function buildBody()
    {
        $remoteMessage = new \SabreAMF_AMF3_RemotingMessage();
        $this->data = new \SabreAMF_TypedObject($this->class, array_merge(array(
            'destination' => $this->destination,
            'operation'   => $this->operation,
            'source'      => null,
            'timestamp'   => 0,
            'messageId'   => $remoteMessage->generateRandomId(),
            'timeToLive'  => 0,
            'clientId'    => null,
            'headers'     => $this->headers,
            'body'        => $this->parameters
        ), $this->additionnalBody));
    }

    /**
     * Build the whole packet
     *
     * @param int $destinationId
     */
    public function build($destinationId)
    {
        $this->buildHeader($destinationId);
        $this->buildBody();
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}