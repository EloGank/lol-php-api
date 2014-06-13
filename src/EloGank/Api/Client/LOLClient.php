<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client;

use EloGank\Api\Client\Exception\AuthException;
use EloGank\Api\Client\Exception\BadCredentialsException;
use EloGank\Api\Client\Exception\ClientException;
use EloGank\Api\Client\Exception\ClientKickedException;
use EloGank\Api\Client\Exception\RequestTimeoutException;
use EloGank\Api\Client\RTMP\RTMPClient;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Model\Region\RegionInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLClient extends RTMPClient implements LOLClientInterface
{
    const URL_AUTHENTICATE = '/login-queue/rest/queue/authenticate';
    const URL_TOKEN        = '/login-queue/rest/queue/authToken';
    const URL_TICKER       = '/login-queue/rest/queue/ticker';

    /**
     * @var Client
     */
    protected $redis;

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
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var bool
     */
    protected $isAuthenticated = false;

    /**
     * @var int
     */
    protected $lastCall = 0;

    /**
     * @var int The connected user account id, used by heartbeat call
     */
    protected $accountId;

    /**
     * @var int
     */
    protected $heartBeatCount = 0;


    /**
     * @param Client          $redis
     * @param LoggerInterface $logger
     * @param int             $clientId
     * @param RegionInterface $region
     * @param string          $username
     * @param string          $password
     * @param string          $clientVersion
     * @param string          $locale
     * @param int             $port
     */
    public function __construct(LoggerInterface $logger, Client $redis, $clientId, RegionInterface $region, $username, $password, $clientVersion, $locale, $port)
    {
        $this->redis         = $redis;
        $this->clientId      = $clientId;
        $this->region        = $region;
        $this->username      = $username;
        $this->password      = $password;
        $this->clientVersion = $clientVersion;
        $this->locale        = $locale;
        $this->port          = $port;

        parent::__construct($logger, $this->region->getServer(), 2099, '', 'app:/mod_ser.dat', null);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        try {
            $this->connect();
            $this->login();

            $this->isAuthenticated = true;
        }
        catch (ClientException $e) {
            $this->logger->error('Client ' . $this . ' cannot authenticate : ' . $e->getMessage());

            return false;
        }

        return true;
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

        // Checking errors
        if ('_error' == $response['result']) {
            $root = $response['data']->getData()->rootCause->getAMFData();
            if ('com.riotgames.platform.login.impl.ClientVersionMismatchException' == $root['rootCauseClassname']) {
                $newVersion = $root['substitutionArguments'][1];

                $updateVersion = function () use ($newVersion) {
                    $this->logger->alert('Your client version configuration is outdated, it will be automatically updated with this version : ' . $newVersion . ' (configuration key: client.version)');
                    $this->logger->alert('Automatic restart with the new client version...');

                    // Save the new version into the config.yml file
                    $filePath = __DIR__ . '/../../../../config/config.yml';
                    $configs = ConfigurationLoader::getAll();
                    $configs['config']['client']['version'] = $newVersion;

                    $dumper = new Dumper();
                    file_put_contents($filePath, $dumper->dump($configs, 99));
                };

                // Avoid multiple warnings in async
                if (true === ConfigurationLoader::get('client.async.enabled')) {
                    $key = ConfigurationLoader::get('client.async.redis.key') . '.clients.errors.wrong_version';
                    if (null === $this->redis->get($key)) {
                        $updateVersion();

                        $this->redis->set($key, true);
                    }
                }
                else {
                    $updateVersion();
                }

                $this->clientVersion = $newVersion;

                return $this->reconnect();
            }
            elseif ('com.riotgames.platform.login.LoginFailedException' == $root['rootCauseClassname']) {
                $this->logger->warning('Client ' . $this . ': error on authentication (normal in case of busy server). Restarting client...');
                sleep(1);

                return $this->reconnect();
            }
        }

        $data = $response['data']->getData();
        $body = $data->body->getAMFData();
        $token = $body['token'];
        $this->accountId = $body['accountSummary']->getAMFData()['accountId'];

        $authToken = strtolower($this->username) . ':' . $token;
        $authToken = base64_encode($authToken);

        $this->syncInvoke('auth', 8, $authToken, 'flex.messaging.messages.CommandMessage');

        $this->syncInvoke('messagingDestination', 0, null, 'flex.messaging.messages.CommandMessage', array(
            'DSSubtopic' => 'bc'
        ), array(
            'clientId' => 'bc-' . $this->accountId
        ));

        $this->syncInvoke("messagingDestination", 0, null, "flex.messaging.messages.CommandMessage", array(
            'DSSubtopic' => 'cn-' . $this->accountId
        ), array(
            'clientId' => 'cn-' . $this->accountId
        ));

        $this->syncInvoke("messagingDestination", 0, null, "flex.messaging.messages.CommandMessage", array(
            'DSSubtopic' => 'gn-' . $this->accountId
        ), array(
            'clientId' => 'gn-' . $this->accountId
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function reconnect()
    {
        $this->socket->shutdown();
        $this->socket = null;
        $this->token = null;
        $this->DSId = null;
        $this->accountId = null;
        $this->invokeId = 1;
        $this->heartBeatCount = 0;
        $this->startTime = time();
        $this->lastCall = 0;

        $this->authenticate();

        return true;
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
     * @return string
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
            $waitTime = ConfigurationLoader::get('client.authentication.busy.wait');
            $this->logger->alert('Client ' . $this . ': the server is currently busy. Restarting client in ' . $waitTime . ' seconds...');
            sleep($waitTime);

            return $this->reconnect();
        }

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

        $log = function ($regionName, $position) {
            $this->logger->info('Client ' . $this . ': in login queue (' . $regionName . '), #' . $position);
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

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; ' . str_replace('_', '-', $this->locale) . ' AppleWebKit/533.19.4 (KHTML, like Gecko) AdobeAIR/3.7');
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
     * {@inheritdoc}
     */
    public function invoke($destination, $operation, $parameters = array(), \Closure $callback = null, $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array())
    {
        $this->lastCall = microtime(true) + 0.03;

        try {
            return parent::invoke($destination, $operation, $parameters, $callback, $packetClass, $headers, $body);
        }
        catch (ClientKickedException $e) {
            $this->logger->warning($e->getMessage());
            $this->reconnect();
            sleep(1);

            return $this->invoke($destination, $operation, $parameters, $callback, $packetClass, $headers, $body);
        }
        catch (RequestTimeoutException $e) {
            $e->setClient($this);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doHeartBeat()
    {
        $this->syncInvoke('loginService', 'performLCDSHeartBeat', [
            $this->accountId,
            strtolower($this->DSId),
            ++$this->heartBeatCount,
            date('D M j Y H:i:s') . ' GMT' . date('O')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->region->getUniqueName();
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return $this->lastCall <= microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsOverloaded()
    {
        $this->lastCall += (int) ConfigurationLoader::get('client.request.overload.wait');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('#%d (%s)', $this->clientId, $this->getRegion());
    }
}