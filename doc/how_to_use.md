League of Legends PHP API
=========================

## How to use

This API use the powerful [Symfony 2](http://symfony.com/) framework components, and the console system is a part of these.  
So you have some commands to do different jobs. To use the console, you must being in the project root directory.

### The Client

**This API need a socket client to communicate with your application**. Fortunately, there is a repository for this : https://github.com/EloGank/lol-php-api-client

Please, take a look on this client, **some examples are available**.
Assuming you're using this client, you can make a call in a few lines :

``` php
// Declare your client and the configuration
$client = new \EloGank\ApiClient\Client('127.0.0.1', 8080, 'json');

// Do your API request
try {
    $response = $client->send('EUW', 'summoner.summoner_existence', ['Foobar']);
} catch (\EloGank\ApiClient\Exception\ApiException $e) {
    // error
    var_dump($e->getCause(), $e->getMessage());
}
```

### Routes (API calls)

    php console elogank:router:dump
    
This command dump all available controllers and methods (routes) for the API.

The output looks like :

    controller_name :
        method_name [parameter1, parameter2, ...]
        
In this example, with your client, you must call the `controller_name.method_name` route, with two parameters to be able to execute the API.

A route must be called with these three (+ one as optional) parameters :

* `region` it's the client region short name (EUW, NA, ...)
* `route` the API route, in short it's the "`controller_name`.`method_name`"
* `parameters` it's the route parameters, it's an array
* `format` (optional) if you need a specific format for a specific route (see the configuration documentation for available formats)

You can see all available routes on the [routing documentation](./routing.md).

### The API

You will be pleased to learn that the API is fully logged on console and on files (`/logs` directory). The log verbosity can be set in the configuration file (key: `log.verbosity`).

    php console elogank:api:start

This command will start the API, connect all clients and listening for some future requests.  
If you have enabled the asynchronous system, the authentication process will be fast.

### The asynchronous client creation

This command is only used by the API itself, to create client worker for asynchronous purposes. But if you want to create another application and you want to use asynchronous clients, you can use this command :

    php console elogank:client:create [account_configuration_key] [client_id]
    
With these parameters :
* `account_configuration_key` is the key index in the configuration file (the first account configuration is 0, and 1, ...)
* `client_id` an identification id for logging and process communication purposes (an id per client, it must be unique and can be a string)

Example, for your first asynchronous client :

    php console elogank:client:create 0 1
    
### Implement your own API route

#### The workflow

Before starting, you need to understand that the API works with an event-driven workflow. There is no blocking statement, only [ReactPHP](https://github.com/reactphp/react) loop. This loop provides some methods to create periodic timed callbacks.  
For more information, take a look on the official [ReactPHP Github](https://github.com/reactphp/react) or directly in the [src/EloGank/Api/Component/Controller/Controller.php](../src/EloGank/Api/Component/Controller/Controller.php).

**There are two main events :**

* `api-response` : when the API response is emitted to the client
* `api-error` : when an API exception is emitted to the client and processed by the server

#### The code

First, you need to choice what is the main object of the API request : summoner, league, player_stats, etc.  
Then, create (if not already exists) the controller in the `src/EloGank/Api/Controller` directory, for example `SummonerController` :

``` php
// src/EloGank/Api/Controller/SummonerController.php

<?php

namespace EloGank\Api\Controller;

use EloGank\Api\Component\Controller\Controller;

class GameController extends Controller
{
    /* ... */
}
```

Note that the controller must extends `EloGank\Api\Component\Controller\Controller` abstract class to be recognized as a route controller.

Finally, implement your method. The method name must ending by `Action`, example :

``` php
// Controller/MyCustomController.php

// Method parameters are automaticly added as API route parameters in the "elogank:router:dump" command
public function getSomeDataAction($myParameter, $mySecondParameter)
{
    // Invoke id is used to retrieve result later. A call can have an optional callback to format/process the result
    $this->onClientReady(function (LOLClientInterface $client) use ($myParameter, $mySecondParameter) {
        // Note that $myParameter & $mySecondParameter are added to the "use" statement above
        // Without that, we can't use them in this callback
        $this->fetchResult($client->invoke('summonerService', 'getSomeData', [$myParameter, $mySecondParameter], function ($result) {
            var_dump('my callback');
            
            return $myInvokeResult;
        }));
    });
    
    // sendResponse() has only one optional parameter, a callback to format the response
    $this->sendResponse(function ($myControllerResponse) {
        // In the case where we have more than one invoke, "$myControllerResponse" will be an indexed array of invoke results.
        // If we have only one invoke (like in this example), it will be the invoke result (an associative array of data)
        var_dump($myControllerResponse);
    });
}
```

You can create a callback class in `Callback` folder to replace the callback to avoid duplicate code. Your class must extends `EloGank\Api\Component\Callback\Callback`.

``` php
// Callback/MyCustomCallback.php

class SummonerActiveMasteriesCallback extends Callback
{
    /**
     * Parse the API result and return the new content
     *
     * @param array|string $result
     *
     * @return mixed
     */
    public function getResult($result)
    {
        foreach ($result['property'] as $data) {
            if (true === $data['foo']) {
                return ['custom' => $data];
            }
        }


        return ['custom' => []];
    }
    
    /**
     * Set your required options here, if one or more options are missing, an exception will be thrown
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [
            'my_option'
        ];
    }
}
```


``` php
// Controller/MyCustomController.php

public function getSomeDataAction($myParameter, $mySecondParameter)
{
    // MyClassCallback::getResult() will be automatically called by the Controller class
    $this->onClientReady(function (LOLClientInterface $client) use ($myParameter) {
        $this->fetchResult($client->invoke('summonerService', 'getSomeData', [$myParameter], new MyClassCallback([
            'my_option' => 'foo bar'
        ])));
    });
    
    $this->sendResponse();
}
```


Now, run the elogank:router:dump command to see your new API route.  
If you want to know about the make asynchronous calls in a same controller method, see the [GameController::getAllSummonerDataCurrentGameAction()](../src/EloGank/Api/Controller/GameController.php) method.

### Next

Before using this API, you must know how works the routing component through the [routing documentation](./routing.md).