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

A route must be called with these three (+ one as optionnal) parameters :

* `region` it's the client region short name (EUW, NA, ...)
* `route` the API route, in short it's the "`controller_name`.`method_name`"
* `parameters` it's the route parameters, it's an array
* `format` (optionnal) if you need a specific format for a specific route (see the configuration documentation for available formats)

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
// Method parameters are automaticly added as API route parameters in the "elogank:router:dump" command
public function getSomeDataAction($myParameter, $mySecondParameter)
{
    // Invoke id is used to retrieve result later. A call can have an optionnal callback to format/process the call result
    $invokeId = $this->getClient()->invoke('summonerService', 'getSomeData', [$myParameter, $mySecondParameter], function ($result) {
        var_dump('my callback');

        return $result;
    });
    
    return $this->view($this->getResult($invokeId));
}
```

Now, run the elogank:router:dump command to see your new API route.  
If you want to know about the make asynchronous calls in a same controller method, see the [GameController::getAllSummonerDataCurrentGameAction()](../src/EloGank/Api/Controller/GameController.php) method.

### Important note

Please, **do not use the route** `summoner.summoner_by_name` **to check a summoner existence**, it causes timeout issue with the overloaded system when the sumomner is not found (because the response body is empty), **use** `summoner.player_existence` **instead**, which return the same information and, in general, **be sure of the existence of your summoner before calling another route**.
    
### Next

Now you know everything about this API, you have the opportunity to [contribute to this project](./contribute.md).