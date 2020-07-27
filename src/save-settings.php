<?php

/**
 * This script is responsible for saving the form data from the options page.
 * This script must hook itself to the 'admin_post_gtp_{function}'-hook called
 * by the forms.
 */

/**
 * This function redirects the user to the settings page and puts a notice
 * into the 'gtp_notice'-option that is displayed on the next load, i.e. the
 * notice will be shown as soon as the user is redirected.
 * @param string $message - the message to put into the admin notice.
 * @param string $type - the type of admin-notice to create; one of
 * 'error', 'warning', 'success', 'info'
 * @param boolean $dismissable - whether the notice should be dismissable
 */

function return_with_notice($message, $type='info', $dismissible=true){

	add_option('gtp_notice', [
		'message'     => $message,
		'type'        => $type,
		'dismissible' => (int) $dismissible
	]);

	wp_redirect(admin_url('tools.php?page=game_table_plugin_options'));
	exit;
}

/**
 * This function saves a team.
 * It's called in both cases: If a team is updated or a new one is created.
 */
add_action('admin_post_gtp_save', 'save');
function save() {


	// collect data
	$short_before = sanitize_text_field($_REQUEST['short-before']);

	$short = sanitize_text_field($_REQUEST['short']);
	$name  = sanitize_text_field($_REQUEST['name']);

	$league_link = sanitize_text_field($_REQUEST['link-league']);
	$cup_link    = sanitize_text_field($_REQUEST['link-cup']);

	// check nonce
	check_admin_referer('gtp_save_' . $short_before);

	// extract data from links if given

	if($league_link !== ''){

		// create copy to preserve the original input
		$league_link_cp = $league_link;

		$query = explode('?', $league_link_cp);
		parse_str(end($query), $league_link_cp);
		/**
		 * end(explode('?', $league_link_cp)) *should* be equivalent to
		 * parse_url($league_link_cp, PHP_URL_QUERY), but the URLs generated by
		 * Handball4All are in a way malformed that it has to be done manually.
		 */

		if(array_key_exists('ogId', $league_link_cp) && array_key_exists('lId', $league_link_cp) && array_key_exists('tId', $league_link_cp)){
			$league_ogId = $league_link_cp['ogId'];
			$league_lId  = $league_link_cp['lId'];
			$league_tId  = $league_link_cp['tId'];
		}else{
			return_with_notice('Der Ligalink ist ungültig.', 'error', false);
		}

	}else{
		$league_link = null;
		$league_ogId = null;
		$league_lId  = null;
		$league_tId  = null;
	}
	if($cup_link !== ''){

		// create a copy to preserve the original
		$cup_link_cp = $cup_link;

		$query = explode('?', $cup_link_cp);
		parse_str(end($query), $cup_link_cp);
		// similar to the league link above

		if(array_key_exists('ogId', $cup_link_cp) && array_key_exists('lId', $cup_link_cp)){
			$cup_ogId = $cup_link_cp['ogId'];
			$cup_lId  = $cup_link_cp['lId'];
		}else{
			return_with_notice('Der Pokallink ist ungültig.', 'error', false);
		}

	}else{
		$cup_link = null;
		$cup_ogId = null;
		$cup_lId  = null;
	}

	// insert into database
	global $wpdb;
	$db_res = null;

	if($short_before !== '_new'){
		$db_res = $wpdb->update(
			$wpdb->prefix . 'gtp_teams',
			[ /* values */
				'shortN'      => $short,
				'longN'       => $name,
				'league_link' => $league_link,
				'league_ogId' => $league_ogId,
				'league_lId'  => $league_lId,
				'league_tId'  => $league_tId,
				'cup_link'    => $cup_link,
				'cup_ogId'    => $cup_ogId,
				'cup_lId'     => $cup_lId
			],
			[ /* where */
				'shortN' => $short_before
			]
		);
	}else{
		$db_res = $wpdb->insert(
			$wpdb->prefix . 'gtp_teams',
			[
				'shortN'      => $short,
				'longN'       => $name,
				'league_link' => $league_link,
				'league_ogId' => $league_ogId,
				'league_lId'  => $league_lId,
				'league_tId'  => $league_tId,
				'cup_link'    => $cup_link,
				'cup_ogId'    => $cup_ogId,
				'cup_lId'     => $cup_lId
			]
		);
	}

	if($db_res === false){
		return_with_notice('Es gab einen Datenbankfehler.', 'error', false);
	}elseif($db_res === 0){
		return_with_notice('Das zu ändernde Team existiert nicht.', 'warning', true);
	}else{
		return_with_notice('Das Team wurde erfolgreich gespeichert.', 'success', true);
	}
}

/**
 * This function deletes a team from the database.
 */
add_action('admin_post_gtp_delete', 'delete');
function delete() {

	$short_before = sanitize_text_field($_REQUEST['short']);

	// check nonce
	check_admin_referer('gtp_delete_' . $short_before);

	global $wpdb;

	$db_res = $wpdb->delete( $wpdb->prefix . 'gtp_teams', [ 'shortN' => $short_before ] );

	if ($db_res === false) {
		return_with_notice('Es gab einen Datenbankfehler.', 'error', false);
	}elseif($db_res === 0){
		return_with_notice('Das zu löschende Team existiert nicht.', 'warning', true);
	}else{
		return_with_notice('Das Team wurde erfolgreich gelöscht.', 'success', true);
	}
}

/**
 * This function saves the name of the Teams in the Club.
 */
add_action('admin_post_gtp_save_name', 'save_name');
function save_name() {

    // check nonce
    check_admin_referer('gtp_save_name');

	$name = sanitize_text_field($_REQUEST['teamname']);

	$wp_res = update_option( 'gtp_teamname', $name );

	if($wp_res !== false){
		return_with_notice('Der Teamname wurde erfolgreich geändert.', 'success', true);
	}else{
		return_with_notice('Es wurde keine Änderung vorgenommen.', 'info', true);
	}
}

/**
 * This function saves the clublink from Handball4All.
 */
add_action('admin_post_gtp_save_club_link', 'save_club_link');
function save_club_link() {

    // check nonce
    check_admin_referer('gtp_save_club_link');

	$link = sanitize_text_field($_REQUEST['clublink']);

	$wp_res = update_option( 'gtp_clublink', $link );

	if($wp_res !== false){
		return_with_notice('Der Teamname wurde erfolgreich geändert.', 'success', true);
	}else{
		return_with_notice('Es wurde keine Änderung vorgenommen.', 'info', true);
	}
}

/**
 * This function saves the style settings.
 */
add_action('admin_post_gtp_style_save', 'save_style');
function save_style(){

	// check nonce
	check_admin_referer('gtp_save_style');

	if(array_key_exists('save', $_REQUEST)){
		$style = sanitize_textarea_field($_REQUEST['style']);
		$res = file_put_contents(GTP_DIR . '/src/css/widgets.css', $style);
	}elseif(array_key_exists('reset', $_REQUEST)){
		$res = file_put_contents(
			GTP_DIR . '/src/css/widgets.css',
			file_get_contents(GTP_DIR . '/src/css/widgets.css.initial')
		);
	}else{
		$res = false;
	}

	if($res !== false){
		return_with_notice('Der Style wurde erfolgreich gespeichert.', 'success', true);
	}else{
		return_with_notice('Der Style konnte nicht gespeichert werden.', 'error');
	}
}