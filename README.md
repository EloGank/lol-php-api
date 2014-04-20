# League of Legends PHP API

RTMP API fully PHP for League of Legends.  
**Currently in development !**

## Installation

* Get [Composer](https://getcomposer.org) by copy/paste this line on your shell (in the project root directory) :  
`curl -sS https://getcomposer.org/installer | php`
* Install all dependancies :  
`php composer.phar install`

If you want to use Vagrant : use `vagrant up` in the root folder.

## Configuration

Open the `config/config.yml` file and edit the `client.accounts` part :

``` yml
# config/config.yml
client:
    accounts:
      - region:   ~ # The region unique name, currently EUW or NA
        username: ~ # Your test account username
        password: ~ # Your test account password
```

This API allow you to add more than one client account on several different server, example :

``` yml
# config/config.yml
client:
    accounts:
      - region:   EUW
        username: myEuwUsername
        password: myEuwPassword
      - region:   EUW
        username: mySecondEuwUsername
        password: mySecondEuwPassword
      - region:   NA
        username: myNaUsername
        password: myNaPassword
```

## How it works

To launch the server, use this command :

    php console elogank:api:start
    
Once your API is launched, you can easily connect to your server through socket *(a client example will be added soon, on another repository)*.  
To known which routes are available, use this command :

    php console elogank:router:dump
    
## TODO

* Redis logger
* Asynchronous API calls
* Antiflood system

## Contribute

Feel free to open an issue, fork this project or suggest an awesome new feature :)

## Note

Use a development account, and not your real live game account.

## Licence

[Creative Commons Attribution-ShareAlike 3.0](./LICENCE.md)

*League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends (c) Riot Games, Inc.*
