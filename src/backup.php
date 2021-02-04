<?php

if (!defined('ABSPATH')) {
	header('Location: /');
	wp_die();
}

const BACKUP_FILE = GTP_DIR . '/backup.json';

/**
 * function load_backup()
 * Loads data from the BACKUP_FILE and writes it into the database.
 * Removes BACKUP_FILE from the filesystem.
 */
function load_backup(){

	if(!file_exists(BACKUP_FILE)) return;

	$data = json_decode(file_get_contents(BACKUP_FILE));

	foreach($data->teams as $team){
		insert_team(
			[
				'shortN'      => $team->short,
				'longN'       => $team->name,
				'league_link' => $team->league_link,
				'league_ogId' => $team->league_ogId,
				'league_lId'  => $team->league_lId,
				'league_tId'  => $team->league_tId,
				'cup_link'    => $team->cup_link,
				'cup_ogId'    => $team->cup_ogId,
				'cup_lId'     => $team->cup_lId
			]
		);
	}

	update_option('gtp_teamname', $data->teamname);
	update_option('gtp_clublink', $data->clublink);

	unlink('backup.json');

	/**
	 * refresh all teams; this is slow, but also necessary
	 */
	require_once GTP_DIR . '/src/extract_transform.php';
	extract_transform();
}

/**
 * function make_backup()
 * Reads teams and options from the database and puts everything in BACKUP_FILE.
 * @param bool $write - whether to write the backup to a file; if false the
 * backup is returned
 * @return string|null - the backup as JSON if $write; else or when no team is
 * in the database null
 */
function make_backup($write=true){

	$teams = get_teams();

	// only make a backup if at least one team is in the database
	if (!count($teams)) return;

	$result = new stdClass();
	$result->teams = $teams;

	$result->teamname = get_option('gtp_teamname');
	$result->clublink = get_option('gtp_clublink');

	$json = json_encode($result);

	if($write)
		file_put_contents(BACKUP_FILE, $json);
	else
		return $json;
}
