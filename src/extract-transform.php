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
		. 'T' . (($game->gTime != '') ? $game->gTime : '00:00') . ':00' // time
	);

	return $start_time;
}

/**
 * $names will hold the names that are associated with the club (editable in the
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
 * function index()
 * Returns the index of a given team.
 * @param string $name - the name of the team
 * @return int - the index of the team
 */
function index ($name) {
	/* find the last integer number in the given string */
	preg_match_all('/[1-9][0-9]*/', $name, $matches);
	$index = (int) end($matches[0]);
	return $index == 0 ? 1 : $index;
}

/**
 * function has_own_team
 * Determines whether one of the teams in the game has the own team.
 * @param stdClass $game - the game to consider
 * @return bool
 */
function has_own_team ($game) {
	return has_name($game->gHomeTeam) || has_name($game->gGuestTeam);
}

/**
 * function get_own_team()
 * Determines which one of the teams in the game contains the name of the own team.
 * It's got the ability to respect the index of the given team.
 * @param string $teamname - the name to search for
 * @param stdClass $game - the game to consider
 * @return false|'host'|'guest' - the identifier where the name was found, false
 * if the name wasn't found
 */
function get_own_team($teamname, $game) {

	$host = $game->gHomeTeam;
	$guest = $game->gGuestTeam;

	// find the team index
	$team_index = index($teamname);

	// initialize the result
	$res = false;

	if (has_name($host) && $team_index == index($host)) {
		$res = 'host';
	}

	if (has_name($guest) && $team_index == index($guest)) {
		$res = 'guest';
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
	$own_team = get_own_team($teamname, $game);

	// update game
	update_game([
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
 * This function inserts one team into the tables-table.
 * @param stdClass $team - the team to insert
 * @param string $orignin_team - the team the table is associated with
 * @return void
 */
function insert_db_teamscore($team, $origin_team){

	update_teamscore([
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
		'has_name'       => (int) has_name($team->tabTeamname),
	]);
}

/**
 * This function is directly hooked to the cronjob.
 * It is responsible for keeping the games and tables stored in the databse
 * up to date.
 * @param string[]|vararg ...$teams - the teams to refresh; if the first
 * argument is an array further ones are ignored
 */
function extract_transform(...$teams){

	/**
	 * determine which teams to refresh and load them
	 */
	if (count($teams)) {
		/**
		 * $placeholders is a string of multiple '%s' separated by ', '
		 * the construction used prevents a trailing ', '
		 */
		$placeholders = implode(', ', array_fill(0, count($teams), '%s'));
		$teamselect = db_prepare(" AND shortN IN ({$placeholders})", ...$teams);
	} else {
		$teamselect = '';
	}
	$teams = get_teams("next_update <= " . time() . $teamselect);

	/**
	 * cycle through teams and save all games to the database
	 */
	foreach($teams as $team){

		/**
		 * League Data
		 */
		if($team->league_link !== null) {
			//configure endpoint
			$link = "https://spo.handball4all.de/service/if_g_json.php?ca=0&cl={$team->league_lId}&cmd=ps&ct={$team->league_tId}&og={$team->league_ogId}";

			// request and store data
			$content = json_decode(wp_remote_retrieve_body(
				wp_remote_get($link)
			))[0]->content;

			// retrieve relevant data
			$game_list = $content->futureGames->games;
			$table_list = $content->score;

			// remove all games of the current team
			remove_games(['origin_team' => $team->shortN]);

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
			remove_teamscores(['origin_team' => $team->shortN]);
			foreach($table_list as $index => $teamscore) {
				if((int) $teamscore->tabScore == 0) {
					/**
					 * For the current team no score is provided.
					 * This is the case whenever two teams share one score
					 * therefore the score is equal to the one of the team in
					 * front.
					 * The database table however does not allow two teams to
					 * have the same position due to it's primary key-constraint.
					 * @see src/database.php
					 * Therefore, 1 is added so that no collision will occur.
					 * The positionis will then be correct if goals are
					 * considered.
					 */
					$teamscore->tabScore = $table_list[$index-1]->tabScore + 1;
				}
				insert_db_teamscore($teamscore, $team->shortN);
			}
		}
		/**
		 * Cup Data
		 */
		if($team->cup_link !== null) {
			// configuring endpoint
			$link = "https://spo.handball4all.de/service/if_g_json.php?ca=0&cl={$team->cup_lId}&cmd=ps&og={$team->cup_ogId}";

			// requesting and retrieving relevant data
			$game_list = json_decode(wp_remote_retrieve_body(
				wp_remote_get($link)
			))[0]->content->futureGames->games;

			// write games to database
			foreach($game_list as $game) {
				// in cup games it is needed to verify, that the team happens to be in the particular game
				// because when requesting cup data all games from all teams are delivered
				if(has_own_team($game)) {
					update_db_game($game, $team->shortN, $team->longN, 'CUP');
				}
			}
		}

		/**
		 * determine when the next update should be made
		 * write this time to the database
		 * the next update is oriented on the first game that hasn't got a press
		 * report yet. if it is in the past, the next possible update will
		 * refresh this team again.
		 * if the game is more than 24 hours in the past it is not taken into
		 * consideration here, because it is unlikely to have something
		 * happen to this game just as quick.
		 */
		$day_seconds = 24 * 3600;
		$game_start = get_game_start(db_prepare("origin_team = %s AND sGID = '0' AND start > " . (time() - $day_seconds), $team->shortN), 'start ASC');
		if ($game_start == null) $game_tart = strtotime('tomorrow');
		team_set_update($team->shortN, $game_start);
	}
}
