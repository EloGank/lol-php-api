League of Legends PHP API
=========================

## Caching

To avoid useless API calls, you need to cache the call results. Before doing that, you must know some things :

#### Summoner name

The summoner name is not permanent ! A summoner can change his name by purchasing the rename ticket in the store thus, his name will be updated. If you create a sumomner name slug, **you must verify periodically if the name is still the same** and retrieve the related account information or, let a manual updater button.

For the same reason, **you must use the summoner ID as the primary key** or you will lost your database/cache relations if the summoner udpate his name *(or you will have to update all the relation primary keys, this is not the best idea)*.

#### Think about the players

This API requests the Riot servers, which are used by LoL players. **So, think about the players : cache your retrieved data to avoid servers flood**.

#### Complexity

A lot of data can be retrieved with this API, so take a coffee, and think about your database/cache schema.

### Next

Now you know everything about this API, you have the opportunity to [contribute to this project](./contribute.md).