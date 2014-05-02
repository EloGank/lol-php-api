League of Legends PHP API
=========================

## How to use

This API use the powerful [Symfony 2](http://symfony.com/) framework components, and the console system is a part of these.  
So you have some commands to do different jobs. To use the console, you must being in the project root directory.

### Routes (API calls)

    php console elogank:router:dump
    
This command dump all available controllers and methods (routes) for the API.

The output looks like :

    controller_name :
        method_name [parameter1, parameter2, ...]
        
In this example, with your client, you must call the `controller_name.method_name` route, with two parameters to be able to execute the API.

A route must be called with these three (+ one as optionnal) parameters :

* `route` the API route, in short it's the "`controller_name`.`method_name`"
* `region` it's the client region short name (EUW, NA, ...)
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
    
### Next

Now you know everything about this API, you have the opportunity to [contribute to this project](./contribute.md).