League of Legends PHP API
=========================

## Configuration

**Note:** you can find the whole configuration list in the [config/config.yml.dist](../config/config.yml.dist) file.

After doing a `php composer install` a new file appears : `config/config.yml`, open this file.

### Asynchronous configuration

The asynchronous system allow you to have more than one connected client. It usefull when having a big trafic website or using a custom API route with with simultaneous calls.  
If the asynchrnous system is disabled, you don't need to install the dependencies, and all API calls must wait for the previous before being executed.

Simply edit the `client.async.enabled` key to `true`.

``` yml
# config/config.yml
client:
    async:
      enabled: true
```

### Account configuration

``` yml
# config/config.yml
client:
    accounts:
      - region:   ~ # The region unique name, currently EUW or NA
        username: ~ # Your test account username
        password: ~ # Your test account password
```

This API allow you to add more than one client account on several different servers (usefull only in asynchronous configration), example :

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

### Server configuration

``` yml
# config/config.yml
server:
    port:   8080
    bind:   ~
    format: json
```

You can allow only one IP address to connect to your fresh new API. Just add the IP address after the `bind` key :

``` yml
# config/config.yml
server:
    bind: 127.0.0.1 # access only to myself
```

You can change the output format, for these values : `json`, `php` (using `serialize` function) or `xml`. Simply edit the `format` key.  
If you need another format, [ask for it](https://github.com/EloGank/lol-php-api/issues), or contribute !

### Next

Now you can start the API, see the [how it works documentation](./how_it_works.md).