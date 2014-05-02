# League of Legends PHP API

Unofficial RTMP API fully PHP and asynchronous for League of Legends.  
With this API you can retrieve some data about summoners in real-time directly from Riot servers.

### Features

* A ready-to-use API server
* A ready-to-use Virtual Machine
* Use the powerful Symfony 2 framework components
* Allow multi client to improve the response speed
* Fully aynschronous (with ZeroMQ & mutli process)
* Multi region (EUW, NA)
* Anti-overload system (avoid temporary client ban when you make too many request)
* Allow to use native RTMP API or custom API with our controllers
* Fully logged in file, redis, and console (usefull for developpers)
* Automatic restart when server is busy
* Automatic update when client version is outdated
* Allow mutliple output format (JSON, PHP native (serialized) and XML)
* Allow to bind the server to a specific IP address (allow-only)
* Easy to override

## Documentation

The document is stored in the `doc` folder of this repository.
Here, the main titles :

* [Installation](./doc/installation.md)
* [Configuration](./doc/configuration.md)
* [How to use](./doc/how_to_use.md)
* [Contribute](./doc/contribute.md)

## Installation

[How to install this API](./doc/installation.md)

## Configuration

[How to configure this API](./doc/configuration.md)

## Important notes

Use a **development account** for your tests, and **not your real live game account**.  
Be aware that only one API/person can be connected at the same time with the same account. If you have production server and development server, use two distinct accounts.

Please, **do not use the route** `summoner.summoner_by_name` **to check a summoner existence**, it causes timeout issue with the overloaded system when the sumomner is not found (because the response body is empty), **use** `summoner.player_existence` **instead**, which return the same information and, in general, **be sure of the existence of your summoner before calling another route**.

## TODO

* Implement other regions
* Add information about `supervisor`
* Create & catch exception when a controller return a wrong view
* Unit tests

## Reporting an issue or a feature request

Feel free to open an issue, fork this project or suggest an awesome new feature in the [issue tracker](https://github.com/EloGank/lol-php-api/issues).  
When reporting an issue, please include your asynchronous configuration (enabled or not).

## Credit

See the list of [contributors](https://github.com/EloGank/lol-php-api/graphs/contributors).  
The RTMP client class is a PHP partial rewrite of the awesome [Gabriel Van Eyck's work](https://code.google.com/p/lolrtmpsclient/source/browse/trunk/src/com/gvaneyck/rtmp/RTMPSClient.java).

## Licence

[Creative Commons Attribution-ShareAlike 3.0](./LICENCE.md)

*League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends (c) Riot Games, Inc.*