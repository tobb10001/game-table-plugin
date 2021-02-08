<?php

/**
 * This scripts is meant to hold (almost) all database functionality for the
 * whole plugin. Only complex requests should be made from the consuming scripts.
 */

if (!defined("ABSPATH")) {
	header("Location: /");
	wp_die();
}

//region activation/deactivation
/**
 * Function create_db_tables()
 * Creates the tables needed for the plugin:
 * - gtp_teams
 * - gtp_games
 * - gtp_tables
 * Meant to be called at plugin registration.
 * Created Tables should be dropped by delete_db_tables()
 */
function create_db_tables () {
    global $wpdb;

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gtp_teams (
            shortN varchar(8) NOT NULL,
            longN varchar(16) NOT NULL,
			league_link varchar(140),
            league_ogId varchar(8),
            league_lId varchar(8),
            league_tId varchar(8),
			cup_link varchar(140),
            cup_ogId varchar(8),
            cup_lId varchar(8),
			last_update int DEFAULT 0,
			next_update int DEFAULT 0,
            PRIMARY KEY (shortN)
        );"
    );
    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gtp_games (
            origin_team varchar(8),
            gID varchar(8),
            sGID varchar(8),
            live bit,
            gToken varchar(255),
            start int,
            gym_name varchar(255),
            gym_post_code varchar(8),
            gym_town varchar(255),
            gym_street varchar(255),
            gym_no varchar(255),
            host varchar(255),
            guest varchar(255),
            host_goals int,
            guest_goals int,
            host_goals_ht int,
            guest_goals_ht int,
            host_points int(2),
            guest_points int(2),
            own_team ENUM('host', 'guest'),
            type ENUM('LEAGUE', 'CUP'),
            PRIMARY KEY (gID),
            CONSTRAINT fk_team_game
                FOREIGN KEY (origin_team)
                REFERENCES {$wpdb->prefix}gtp_teams(shortN)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        );"
    );
    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gtp_tables (
            origin_team varchar(8),
            place int,
            team varchar(255),
            games int,
            won int,
            tied int,
            lost int,
            goals_shot int,
            goals_recieved int,
            points_plus int,
            points_minus int,
            has_name tinyint,
            PRIMARY KEY (origin_team, place),
            CONSTRAINT fk_team_score
                FOREIGN KEY (origin_team)
                REFERENCES {$wpdb->prefix}gtp_teams(shortN)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        );"
    );
}

/**
 * function delete_db_tables()
 * Deletes all tables created by create_db_tables()
 */
function delete_db_tables () {
	global $wpdb;

	// tables dropped in the correct order such that no constraint is violated in the process
	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_games;");
	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_tables;");

	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_teams;");

}
//endregion

//region tools

/**
 * function db_prepare()
 * Wrapper for the $wpdb->prepare() function.
 * This should be called with all strings passed as $condition, where an SQL-injection would otherwise be possible.
 * @param string $query - the query
 * @param mixed...|array $args - the arguments to be inserted
 * @return string|void - the escaped query
 */
function db_prepare ($query, ...$args) {
	global $wpdb;
	return $wpdb->prepare($query, $args);
}
//endregion

//region generic

/**
 * function _sql_opt()
 * Generates the optional parts of a SELECT-statement. Includes a leading space.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return string - the complete query extension
 */
function _sql_opt($condition='', $order='') {
	$con = strlen($condition) ? ' WHERE ' . $condition : '';
	$ord = strlen($order) ? ' ORDER BY ' . $order : '';
	return $con . $ord;
}
/**
 * function _get_gen()
 * Fetch from the given (gtp-) table.
 * @param string $table - table to use
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass[] - found data, empty on error
 */
function _get_gen ($table, $condition='', $order='') {
	$ext = _sql_opt($condition, $order);
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gtp_{$table}{$ext};", OBJECT);
}

/**
 * function _get_gen_single()
 * Fetches a single row from a (gtp-) table.
 * If the query returns more than one row the first one is selected.
 * @param string $table - table to use
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass|null - the game object
 */
function _get_gen_single ($table, $condition='', $order='') {
	$ext = _sql_opt($condition, $order);
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}gtp_{$table}{$ext} LIMIT 1;", OBJECT);
}
//endregion

//region teams
/**
 * function insert_team()
 * Inserts the team given in team to the gtp_teams table.
 * @param array team - the team as meant to be going to the database as (column, value)-pairs
 * @return int|false - number of rows inserted, false on error
 */
function insert_team ($team) {
	global $wpdb;
	return $wpdb->insert($wpdb->prefix . 'gtp_teams', $team);
}

/**
 * function update_team()
 * Updates the given team with the values provided.
 * @param string $team - the shortN of the team
 * @param array $values - the updated data as (column, value)-pairs
 * @return int|false - the number of updated rows, false on error
 */
function update_team ($team, $values=[]) {
	global $wpdb;
	return $wpdb->update($wpdb->prefix . 'gtp_teams', $values, ['shortN'=>$team]);
}

/**
 * function delete_team()
 * Deletes the given team.
 * @param string $team - the shortN of the team to be removed
 * @return int|false - the number of rows affected (0/1) or false on error
 */
function delete_team ($team) {
	global $wpdb;
	return $wpdb->delete($wpdb->prefix . 'gtp_teams', ['shortN'=>$team]);
}

/**
 * function team_set_update()
 * Schedules the next update run for the team. Saves the when the last update was (current time).
 * @param string $team - the shortN of the desired team
 * @param int time - UNIX timestamp
 */
function team_set_update ($team, $time) {
	/**
	 * maximum timespan to next update is 24 hours
	 * if the given time is further in the future the update time is set to midnight
	 */
	$day_seconds = 24 * 3600;
	$time = ($time - time() < $day_seconds) ? $time : strtotime('tomorrow');
	global $wpdb;
	$wpdb->update($wpdb->prefix . 'gtp_teams', ['next_update'=>$time, 'last_update'=>time()], ['shortN'=>$team]);
}

/**
 * function get_teams()
 * Fetches teams from the database.
 * @param string $condition - string to be used as SQL-WHERE-clause
 * @param string $order - string to be used as SQL-ORDER-clause
 * @return stdClass[] - the fetched teams, empty array on error
 */

function get_teams ($condition='', $order='') {
	return _get_gen('teams', $condition, $order);
}

function get_teams_names ($condition='', $order='') {
	$opt = _sql_opt($condition, $order);
	global $wpdb;
	return $wpdb->get_results(
		"SELECT shortN, longN FROM {$wpdb->prefix}gtp_teams{$opt}",
		OBJECT
	);
}

/**
 * function get_last_update()
 * Get the time the team was last updated.
 * @param string $team - the shortN of the team
 * @return int - the time as UNIX timestamp
 */
function team_get_last_update ($team) {
	global $wpdb;
	return $wpdb->get_var(
		$wpdb->prepare("SELECT last_update FROM {$wpdb->prefix}gtp_teams WHERE shortN = %s", $team)
	);
}

/**
 * function get_teams_settings()
 * Fetches all teams from the database and preprocesses some data for the teams
 * settings page.
 * Also adds the '_new'-team needed for creation of a new team.
 * @return stdCLass[] - the teams
 */
function get_teams_settings () {
	global $wpdb;
	$sql =
		"SELECT
			shortN,
			longN,
			IF(ISNULL(league_link), '', league_link) as league_link,
			IF(ISNULL(league_link), '<em>NA</em>', CONCAT('[…]', SUBSTRING(league_link, 40))) as league_link_short,
			IF(ISNULL(cup_link), '', cup_link) as cup_link,
			IF(ISNULL(cup_link), '<em>NA</em>', CONCAT('[…]', SUBSTRING(cup_link, 40))) as cup_link_short
		FROM {$wpdb->prefix}gtp_teams
		ORDER BY shortN ASC";
	$teams = (array) $wpdb->get_results($sql, OBJECT);
	array_unshift($teams, (object) ['shortN' => '_new', 'longN' => '', 'league_link' => '', 'cup_link' => '']);
	return $teams;
}

/**
 * function get_team_link()
 * Fetches the link from a single team and competition from the database
 * @param string $team - the team to search for
 * @param cup|league $comp - the competition to select, returns null if the competition doesn't exist
 * @return string|null - the content of the requested cell
 */
function get_team_link ($team, $comp) {
	if (!($comp == 'cup' || $comp == 'league')) return null;
	$comp = $comp . '_link';
	global $wpdb;
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT $comp FROM {$wpdb->prefix}gtp_teams WHERE shortN = %s",
			$team
		)
	);
}
//endregion

//region games
/**
 * function update_game()
 * Updates a game in the games-table. Creates it, if it is not existent.
 * @param array $game - to be updated/inserted as (column, value)-pairs
 * @return int|false - number of affected rows, false on error
 */
function update_game ($game) {
	global $wpdb;
	return $wpdb->replace($wpdb->prefix . 'gtp_games', $game);
}

/**
 * function remove_games()
 * Deletes from the games-table that satisfy the condition
 * @param string[] $condition - set of conditions as (column=>value)-pairs
 * @return int|false - number of deleted rows, false on error
 */
function remove_games ($condition) {
	global $wpdb;
	return $wpdb->delete($wpdb->prefix . 'gtp_games', $condition);
}

/**
 * function get_game()
 * Fetches a single game from the database.
 * If the query returns more than one row the first one is selected.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass - the game object
 */
function get_game ($condition='', $order='') {
	return _get_gen_single('games', $condition, $order);
}

/**
 * function get_game_start()
 * Fetches the start of a single game from the database
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return int|null - the start as UNIX timestamp; null on failure
 */
function get_game_start ($condition='', $order='') {
	$opt = _sql_opt($condition, $order);
	global $wpdb;
	return $wpdb->get_var("SELECT start FROM {$wpdb->prefix}gtp_games{$opt} LIMIT 1");
}

/**
 * function get_games()
 * Fetches games from the database.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass[] - the fetched games, empty array on error
 */

function get_games ($condition='', $order='') {
	return _get_gen('games', $condition, $order);
}

/**
 * function get_games_teamnames()
 * Fetches games from the database; associates the teamname of
 * the corresponding team to the game.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass[] - the fetched games, empty on error
 */
function get_games_teamnames ($condition='', $order='') {
	if (strlen($condition)) $condition = ' AND (' . $condition . ')';
	if (strlen($order)) $order = ' ORDER BY ' . $order;
	global $wpdb;
	$games = $wpdb->prefix . 'gtp_games';
	$teams = $wpdb->prefix . 'gtp_teams';
	return $wpdb->get_results(
		"SELECT {$teams}.longN as origin_team_name, {$games}.*
		FROM {$games}, {$teams}
		WHERE {$teams}.shortN = {$games}.origin_team{$condition}{$order}",
		OBJECT
	);
}

/**
 * function get_gyms()
 * Returns all gyms where games take place.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass[] - the gyms
 */
function get_gyms ($condition='', $order='') {
	$opt = _sql_opt($condition, $order);
	global $wpdb;
	return $wpdb->get_results(
		"SELECT DISTINCT
			gym_name as name, gym_post_code as post_code, gym_town as town, gym_street as street, gym_no as num
		FROM {$wpdb->prefix}gtp_games
		{$opt}
	");
}
//endregion

//region tables
/**
 * function update_teamscore()
 * Updates a teamscore in the tables-table. Creates it if it is not existent.
 * @param array $teamscore - the teamscore to be inserted as (column, value)-pairs
 * @return int|false - number of affected rows, false on error
 */
function update_teamscore ($teamscore) {
	global $wpdb;

	return $wpdb->replace($wpdb->prefix . 'gtp_tables', $teamscore);
}

/**
 * function remove_teamscores()
 * Removes all teamscores from the tables-table that satisfy the condition.
 * @param array $condition - array of condition as (column, value)-pairs
 * @return int|false number of deleted rows, false on error
 */
function remove_teamscores ($condition) {
	global $wpdb;

	return $wpdb->delete($wpdb->prefix . 'gtp_tables', $condition);
}

/**
 * function get_teamscores()
 * Fetch teamscores from the tables-table.
 * @param string $condition - string to be used as WHERE-clause
 * @param string $order - string to be used as ORDER BY-clause
 * @return stdClass[] - fetched lines, empty on array
 */
function get_teamscores ($condition='', $order='') {
	return _get_gen('tables', $condition, $order);
}
//endregion
