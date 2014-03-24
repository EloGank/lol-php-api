<?php

namespace EloGank\Api\Client;

use EloGank\Api\Client\Exception\AuthException;
use EloGank\Api\Client\Exception\BadCredentialsException;
use EloGank\Api\Client\Exception\ClientException;
use EloGank\Api\Client\Exception\ServerBusyException;
use EloGank\Api\Client\RTMP\RTMPClient;
use EloGank\Api\Logger\LoggerFactory;
use EloGank\Api\Region\RegionInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLClient extends RTMPClient
{
    const URL_AUTHENTICATE = '/login-queue/rest/queue/authenticate';
    const URL_TOKEN        = '/login-queue/rest/queue/authToken';
    const URL_TICKER       = '/login-queue/rest/queue/ticker';

    /**
     * @var int
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var RegionInterface
     */
    protected $region;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $clientVersion;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var bool
     */
    protected $isAuthenticated = false;


    /**
     * @param int             $clientId
     * @param RegionInterface $region
     * @param string          $username
     * @param string          $password
     * @param string          $clientVersion
     * @param string          $locale
     */
    public function __construct($clientId, RegionInterface $region, $username, $password, $clientVersion, $locale)
    {
        $this->clientId      = $clientId;
        $this->region        = $region;
        $this->username      = $username;
        $this->password      = $password;
        $this->clientVersion = $clientVersion;
        $this->locale        = $locale;

        parent::__construct($this->region->getServer(), 2099, '', 'app:/mod_ser.dat', null);
    }

    /**
     * Connect & auth to the login server
     */
    public function auth()
    {
        try {
            $this->connect();
            $this->login();

            $this->isAuthenticated = true;
        }
        catch (ClientException $e) {
            $this->getLogger()->critical('Cannot auth client : ' . $e->getMessage());
        }
    }

    /**
     * Login process
     */
    protected function login()
    {
        $ipAddress = $this->getIpAddress();
        $this->token = $this->getAuthToken();

        $body = new \SabreAMF_TypedObject('com.riotgames.platform.login.AuthenticationCredentials', array(
            'username'           => $this->username,
            'password'           => $this->password,
            'authToken'          => $this->token,
            'clientVersion'      => $this->clientVersion,
            'ipAddress'          => $ipAddress,
            'locale'             => $this->locale,
            'domain'             => 'lolclient.lol.riotgames.com',
            'operatingSystem'    => 'LoLRTMPSClient',
            'securityAnswer'     => null,
            'oldPassword'        => null,
            'partnerCredentials' => null
        ));

        $response = $this->syncInvoke('loginService', 'login', array(
            $body
        ));

        // TODO here, do some actions
        var_dump($response);
    }

    /**
     * Return the login server ip address
     *
     * @return string
     */
    protected function getIpAddress()
    {
        $response = file_get_contents('http://ll.leagueoflegends.com/services/connection_info');

        // In case of site down
        if (false === $response) {
            return '127.0.0.1';
        }

        $data = json_decode($response, true);

        return $data['ip_address'];
    }

    /**
     * @return mixed
     *
     * @throws \RuntimeException                 When a configuration error occured
     * @throws Exception\AuthException           When an unknown auth error occured
     * @throws Exception\ServerBusyException     When server is too busy
     * @throws Exception\BadCredentialsException When credentials are wrong
     */
    protected function getAuthToken()
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL is needed, please install the php-curl extension');
        }

        // Create the parameters query
        $response = $this->readURL(self::URL_AUTHENTICATE, array(
            'user' => $this->username,
            'password' => $this->password
        ));

        if ('FAILED' == $response['status']) {
            if ('invalid_credentials' == $response['reason']) {
                throw new BadCredentialsException('Bad credentials');
            }

            throw new AuthException('Error when logging : ' . $response['reason']);
        }
        elseif ('BUSY' == $response['status']) {
            // TODO implement a scheduled retry

            throw new ServerBusyException('The server is currently busy, please try again later');
        }

        // FIXME need more tests
        // Login queue process
        if (!isset($response['token'])) {
            $response = $this->queueProcess($response);
        }

        return $response['token'];
    }

    /**
     * The queue process, retry until we got the token
     *
     * @param array $response
     *
     * @return array
     */
    protected function queueProcess($response)
    {
        $username = $response['user'];
        $id = 0; $current = 0;
        $delay = $response['delay'];
        $tickers = $response['tickers'];

        // TODO implement Logger
        $log = function ($regionName, $position) {
            echo 'In login queue (' . $regionName . '), #' . $position . PHP_EOL;
        };

        foreach ($tickers as $ticker) {
            $tickerNode = $ticker['node'];
            if ($tickerNode != $response['node']) {
                continue;
            }

            $id = $ticker['id'];
            $current = $ticker['current'];

            break;
        }

        $log($this->region->getUniqueName(), $id - $current);

        // Retry
        while (($id - $current) > $response['rate']) {
            usleep($delay);

            $response = $this->readURL(self::URL_TICKER . '/' . $response['champ']); // champ = queue name
            if (null == $response) {
                continue;
            }

            $current = hexdec($response['node']);

            $log($this->region->getUniqueName(), max(1, $id - $current));
        }

        // Retry for the token
        $response = $this->readURL(self::URL_TOKEN . '/' . $username);
        while (null == $response || !isset($response['token'])) {
            usleep($delay / 10);

            $response = $this->readURL(self::URL_TOKEN . '/' . $username);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array  $parameters
     *
     * @return array
     *
     * @throws \RuntimeException       When a configuration error occured
     * @throws Exception\AuthException When an unknown auth error occured
     */
    protected function readURL($url, array $parameters = null)
    {
        $ch = curl_init();
        if (false === $ch) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        // TODO implement other langs ?
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; ' . str_replace('_', '-', 'en_GB') . ' AppleWebKit/533.19.4 (KHTML, like Gecko) AdobeAIR/3.7');
        curl_setopt($ch, CURLOPT_REFERER, 'app:/LolClient.swf/[[DYNAMIC]]/6');

        curl_setopt($ch, CURLOPT_URL, sprintf('%s%s', $this->region->getLoginQueue(), $url));
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if (null != $parameters) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'payload=' . http_build_query($parameters));
        }

        $response = curl_exec($ch);
        if (false === $response) {
            throw new AuthException('Fail to get the login response, error : ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * @throws \EloGank\Api\Client\Exception\AuthException
     */
    protected function doHandshake()
    {
        parent::doHandshake();
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region->getUniqueName();
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return LoggerFactory::create('LOLClient #' . $this->clientId);
    }
}