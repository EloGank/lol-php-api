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
     * @var \SabreAMF_TypedObject
     */
    private $headers;

    /**
     * @var \SabreAMF_TypedObject
     */
    private $data;


    /**
     * @param string $destination
     * @param string $operation
     * @param array  $parameters
     */
    public function __construct($destination, $operation, $parameters)
    {
        $this->destination = $destination;
        $this->operation   = $operation;
        $this->parameters  = $parameters;
    }

    /**
     * Build the packet's header
     *
     * @param int $destinationId
     */
    public function buildHeader($destinationId)
    {
        $this->headers = new \SabreAMF_TypedObject(null, array(
            'DSRequestTimeout' => 60,
            'DSId'             => $destinationId,
            'DSEndpoint'       => 'my-rtmps'
        ));
    }

    /**
     * Build the packet's body
     */
    public function buildBody()
    {
        $remoteMessage = new \SabreAMF_AMF3_RemotingMessage();
        $this->data = new \SabreAMF_TypedObject('flex.messaging.messages.RemotingMessage', array(
            'destination' => $this->destination,
            'operation'   => $this->operation,
            'source'      => null,
            'timestamp'   => 0,
            'messageId'   => $remoteMessage->generateRandomId(),
            'timeToLive'  => 0,
            'clientId'    => null,
            'headers'     => $this->headers,
            'body'        => $this->parameters
        ));
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