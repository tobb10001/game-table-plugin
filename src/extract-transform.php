<?php

/**
 * This script holds functions to refresh the data used for displaying the
 * content and functions to help for that.
 */

/**
 * This function is called with a game object returned by the API.
 * It returns the start of that game as a UNIX timestamp.
 * If no time is given the time is set to midnight to provide a minimum level
 * of information.
 * @param stdClass $game - the game to calculate the timestamp for
 * @return int UNIX timestamp representing the start of the game
 */

function game_start_time($game) {
	$start_str = $game->gDate;
	$start_time = strtotime(
		'20' . substr($start_str, 6, 2) //year
		. substr($start_str, 3, 2) // month
		. substr($start_str, 0, 2) // day
		. 'T' . (($gamet->gTime != '') ? $game->gTime : '00:00') . ':00' // time
	);

	return $start_time;
}

/**
 * This function gets called with the name of the own team, the host team name
 * and the guest team name.
 * It returns which one contains the name of the own team, false if none does.
 * It's got the ability to respect the index of the given team.
 * @param string $teamname - the name to search for
 * @param string $host - the host team
 * @param string $guest - the guest team
 * @return false/string - the identifier where the name was found, false
 * if the name wasn't found
 */

function get_own_team($teamname, $host, $guest) {

	// find the team index
	preg_match('/[0-9]/', $teamname, $matches);
	$team_index = (int) end($matches);

	// safe as array to access the index in the corresponding given name
	$host_expl  = explode(' ', $host);
	$guest_expl = explode(' ', $guest);

	// initialize the result
	$res = false;

	/**
	 * The outer if statement finds out whether the team in the game is from the
	 * club.
	 * The inner if-statement look at the last part of the teams playing and the
	 * teamname to find out, whether the inices are the same.
	 * If it is the first team it won't have a number. In this case (int) of a
	 * string returns 0 (because a string without number is given), so if it is
	 * 0 team nr. one is meant.
	 */
	if (has_name($host)) {
		if ((int) end($host_expl) === $team_index || ((int) end($host_expl) === 0 && $team_index === 1)) {
			$res = 'host';
		}
	}
	if (has_name($guest)) {
		if ((int) end($guest_expl) === $team_index || ((int) end($guest_expl) === 0 && $team_index === 1)) {
			$res = 'guest';
		}
	}

	return $res;
}


/**
 * This function updates a single game in the database.
 * Parameters are the game itself, the orignin team where it belongs to, the
 * name of the team and the type of game (whether it's a league or cup game)
 * @param stdClass $game - the game to update in the database
 * @param string $origin_team the team the game belongs to
 * @param string $teamname - the name of the team the game belongs to
 * @param string type - the type of game, either 'LEAGUE' or 'CUP'
 * @return void
 */
function update_db_game($game, $origin_team, $teamname, $type){

	/**
	 * Preprocessing game data.
	 */

	$start_time = (int) game_start_time($game); // to UNIX

	$live = $game->live; // bool to bit

	// str to int
	$game->gHomeGoals = (int) $game->gHomeGoals;
	$game->gGuestGoals = (int) $game->gGuestGoals;
	$game->gHomeGoals_1 = (int) $game->gHomeGoals_1;
	$game->gGuestGoals_1 = (int) $game->gGuestGoals_1;
	$game->gHomePoints = (int) $game->gHomePoints;
	$game->gGuestPoints = (int) $game->gGuestPoints;

	// find the own team
	$own_team = get_own_team($teamname, $game->gHomeTeam, $game->gGuestTeam);

	// update game
	global $wpdb;

	$wpdb->replace($wpdb->prefix . 'gtp_games',[
		'origin_team'    => $origin_team,
		'gID'            => $game->gID,
		'sGID'           => $game->sGID,
		'live'           => $live,
		'gToken'         => $game->gToken,
		'start'          => $start_time,
		'gym_name'       => $game->gGymnasiumName,
		'gym_post_code'  => $game->gGymnasiumPostal,
		'gym_town'       => $game->gGymnasiumTown,
		'gym_street'     => $game->gGymnasiumStreet,
		'gym_no'         => $game->gGymnasiumNo,
		'host'           => $game->gHomeTeam,
		'guest'          => $game->gGuestTeam,
		'host_goals'     => $game->gHomeGoals,
		'guest_goals'    => $game->gGuestGoals,
		'host_goals_ht'  => $game->gHomeGoals_1,
		'guest_goals_ht' => $game->gGuestGoals_1,
		'host_points'    => $game->gHomePoints,
		'guest_points'   => $game->gGuestPoints,
		'own_team'       => $own_team,
		'type'           => $type,
	]);
}

/**
 * $names holds the names that are associated with the club (editable in the
 * plugin settings). It is used in the has_name-function, but only needs to be
 * determined once.
 */

$names = get_option('gtp_teamname');

/**
 * The names are stored in a commata separated string which needs to be
 * converted to an array and freed from spaces.
 */
if($names != ''){

	$names = explode(',', $names);

	foreach($names as &$name){
		$name = trim($name);
	}
	unset($name);

}else{
	$names = [];
}

/**
 * This function determines whether the given string contains at least one of
 * the associated teamnames.
 * @param string $string - the input to check
 * @return boolean whether the input contains one of the teamnames
 */
function has_name($string){

	global $names;

	$result = false;

	foreach($names as $name){
		if(strpos($string, $name) !== false){
			$result = true;
		}
	}

	return $result;
}

/**
 * This function inserts one team into the tables-table.
 * @param stdClass $team - the team to insert
 * @param string $orignin_team - the team the table is associated with
 * @return void
 */
function insert_db_teamscore($team, $origin_team){

	global $wpdb;

	$has_name = has_name($team->tabTeamname);

	$wpdb->replace($wpdb->prefix . 'gtp_tables', [
		'origin_team'    => $origin_team,
		'place'          => $team->tabScore,
		'team'           => $team->tabTeamname,
		'games'          => $team->numPlayedGames,
		'won'            => $team->numWonGames,
		'tied'           => $team->numEqualGames,
		'lost'           => $team->numLostGames,
		'goals_shot'     => $team->numGoalsShot,
		'goals_recieved' => $team->numGoalsGot,
		'points_plus'    => $team->pointsPlus,
		'points_minus'   => $team->pointsMinus,
		'has_name'       => $has_name,
	]);
}

/**
 * This function is directly hooked to the cronjob.
 * It is responsible for keeping the games and tables stored in the databse
 * up to date.
 * @return void
 */
function extract_transform(){

	global $wpdb;

	// load teamdata

	$teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gtp_teams", OBJECT);

	// cycle through teams and save all games to the database

	foreach($teams as $team){
		/**
		 * League Data
		 */
		if($team->league_ogId !== null) {
			//configure endpoint
			$link = "https://spo.handball4all.de/service/if_g_json.php?ca=0&cl=$team->league_lId&cmd=ps&ct={$team->league_tId}&og={$team->league_ogId}";

			// request and store data
			$content = json_decode(wp_remote_retrieve_body(
				wp_remote_get($link)
			))[0]->content;

			// retrieve relevant data
			$game_list = $content->futureGames->games;
			$table_list = $content->score;

			// remove all games of the current team
			$wpdb->delete($wpdb->prefix . 'gtp_games', ['origin_team' => $team->shortN]);

			// cycle through and reinsert games
			foreach($game_list as $game) {
				if(has_name($game->gHomeTeam) || has_name($game->gGuestTeam)){
					update_db_game($game, $team->shortN, $team->longN, 'LEAGUE');
				}
			}

			/**
			 * rebuild table
			 * all old data is deleted and rewritten
			 */
			$wpdb->delete($wpdb->prefix . 'gtp_tables', ['origin_team' => $team->shortN]);
			foreach($table_list as $index => $teamscore) {
				if((int) $teamscore->tabScore == 0) {
					/**
					 * For the current team no score is provided.
					 * This is the case whenever two teams share one score
					 * therefore the score is equal to the one of the team in
					 * front.
					 */
					$teamscore->tabScore = $table_list[$index-1]->tabScore;
				}
				insert_db_teamscore($teamscore, $team->shortN);
			}
		}
		/**
		 * Cup Data
		 */
		if($team->cup_ogId !== null) {
			// configuring endpoint
			$link = "https://spo.handball4all.de/service/if_g_json.php?ca=0&cl={$team->cup_lId}&cmd=ps&og={$team->cup_ogId}";

			// requesting and retrieving relevant data
			$game_list = json_decode(wp_remote_retrieve_body(
				wp_remote_get($link)
			))[0]->content->futureGames->games;

			// write games to database
			foreach($game_list as $game) {
				// in cup games it is needed to verify, that the team happens to be in the particular game
				if(has_name($game->gHomeTeam) || has_name($game->gGuestTeam)) {
					update_db_game($game, $team->shortN, $team->longN, 'CUP');
				}
			}
		}
	}
}
