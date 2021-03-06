League of Legends PHP API
=========================

## Routing

There is two different routes in this API.

#### Common Route

It's a route that is already provided by the RTMP LoL API (by the launcher, in short).
This route return the raw LoL API result, without any process between the request and the response.

All routes are requested by the `EloGank\Api\Controller\CommonController::commonCall()` controller's method.  
You can easilly add a common route by editing the `config/api_routes.yml` file : just provide the destination name, the service name & the parameters.

#### Custom Route

It's a custom route created the developper (you and me) with a custom controller.  
It was necessary to implement this custom route, and therefore custom controller, simply because we need to process some custom stuff after requesting one or more common routes. It's here that the asynchronous system is the most useful. In fact, you can call several common routes asynchronously, gain time querying, execute your callbacks and finally, return to your client API.  
For example, with the route `summoner.all_summoner_data_current_game`, you can try it with asynchronous mode enabled and disabled, the requesting time will down to ~0.4 second in asynchronous mode *(with 6 clients)*, against ~1.5 second in synchronous mode.

To implement your own custom route, please refer to the [How to use documentation](https://github.com/EloGank/lol-php-api/blob/master/doc/how_to_use.md#implement-your-own-api-route).

### Route List

According to the `elogank:router:dump` command, here the available route liste :

```
summoner :
  - summoner_by_name :                          [summonerName]
  - all_summoner_data_by_account :              [accountId]
  - all_public_summoner_data_by_account :       [accountId]
  - summoner_internal_name_by_name :            [summonerName]
  - summoner_names :                            [summonerIds[]]
  - all_summoner_data :                         [summonerData[], fetchers[]]
player_stats :
  - aggregated_stats :                          [accountId, gameMode (CLASSIC, ODIN, ARAM), seasonId (1-4)]
  - recent_games :                              [accountId]
  - retrieve_top_played_champions :             [accountId, gameMode (CLASSIC, ODIN, ARAM)]
  - team_aggregated_stats :                     [teamId]
  - team_end_of_game_stats :                    [teamId, gameId]
  - retrieve_player_stats_by_account_id :       [accountId, seasonId (1-4)]
spell_book :
  - spell_book :                                [summonerId]
mastery_book :
  - mastery_book :                              [summonerId]
inventory :
  - available_champions :                       []
  - available_free_champions :                  []
summoner_icon :
  - summoner_icon_inventory :                   [summonerId]
game :
  - retrieve_in_progress_spectator_game_info :  [summonerName]
leagues :
  - all_leagues_for_player :                    [summonerId]
  - challenger_league :                         [queueName (RANKED_SOLO_5x5, RANKED_TEAM_5x5, RANKED_TEAM_3x3)]
login :
  - store_url :                                 []
```

#### Route Parameters

**Routes have three main parameters :**

* **summonerName** : it's not the account name (login) but the summoner name
* **summonerId** : it's the summoner unique ID, it never changes
* **accountId** : it's the summoner account unique ID, it never changes *(not sure after a server transfer, need [help](https://github.com/EloGank/lol-php-api/issues) about this information)*

I can find the summoner & account ID with the route `summoner.summoner_existence [sumomnerName]` which returns the main information about the summoner, including these two parameters.

Please, refer to the [caching documentation](./caching.md) to learn how to cache summoner data.

**Possible other parameters :**

* **gameMode** : it's the game mode : "CLASSIC", "ODIN" (Dominion) or "ARAM"
* **seasonId** : it's the season ID : 1, 2, 3 or 4, increased by one each year. At this time : 4
* **teamId** : it's a summoner team unique ID
* **gameId** : it's a game unique ID

### Route Details

**Note :** there might be some missing errors declaration in this documentation. By the way, all routes might return an error : **you must handle this case**.

#### Summoner
* `summoner_by_name` : returns if the player exists or not. **If the player doesn't exist, a `NULL` response is returned**.
* `all_summoner_data_by_account` : returns only spellbooks & masteries **main** information.
* `all_public_summoner_data_by_account` : returns spellbooks **full** information AND all summoner spells used in all game modes.
* `summoner_internal_name_by_name` : returns the summoner internal name. Maybe a formatted summoner name, used by Riot.
* `summoner_names` : returns all summoner names for the selected summoner IDs.
* `all_summoner_data` : *(custom route)* returns all the main data about a summoner *(spellbooks, masteries, main champion & ranked 5x5 solo league)*. See method documentation in [SummonerController.php](/src/EloGank/Api/Controller/SummonerController.php) for more information.

#### Player Stats
* `aggregated_stats` : returns all information about a **ranked** game mode.
* `recent_games` : returns the information about the summoner recent games.
* `retrieve_top_played_champions` : returns the three most played champion for a **ranked** game mode.
* `team_aggregated_stats` : returns all information about a **ranked** game mode for a team.
* `team_end_of_game_stats` : returns all information about a game result for a team.
* `retrieve_player_stats_by_account_id` : returns all **main** information about all game mode (the profile page).

#### Spell Book
* `spell_book` : returns **full** information about the summoner spellbooks.

#### Mastery Book
* `mastery_book` : returns **full** information about the summoner masteries.

#### Inventory
* `available_champions` : returns all information about the available champions & skins.
* `available_free_champions` : returns all information about the available **free** champions *(usefull to know the free champions rotation week)*.

#### Summoner Icon
* `summoner_icon_inventory` : returns all information about the available summoner icons for a selected summoner.

#### Game
* `retrieve_in_progress_spectator_game_info` : returns all information about the current game for a selected summoner. **If the player doesn't exist or isn't in a game, a `NULL` response is returned**.

#### Leagues
* `all_leagues_for_player` : returns all information about the summoner leagues.
* `challenger_league` : returns all information about the summoner leagues in challenger tier for the given queue name (RANKED_SOLO_5x5, RANKED_TEAM_5x5 or RANKED_TEAM3x3).

#### Login
* `store_url` : returns the full store URL with the authentication token.

### Next

Last but not least, see the [caching documentation](./caching.md) to avoid useless calls.