<?php
/**
 * Plugin Name: Game Table Widget
 * Description: Speichert Spiele und Tabellen von Teams, die auf Handball4All registriert sind und stellt Wigets zur verfügung, mit denen diese angezeigt werden können.
 * Version: 4.1.0
 * Author: Tobias Fischer
 */

if (!defined("ABSPATH")) {
    header("Location: /");
    wp_die();
}

/**
 * Define the plugin path.
 */
const GTP_DIR = __DIR__;

/**
 * Include and register widget functionality.
 * All widgets that belong to this plugin are imported with gtp-widgets.php
 * @see src/widgets/gtp-widgets.php
 */

require_once GTP_DIR . '/src/widgets/gtp-widgets.php';
add_action('widgets_init', function () {
	register_widget('Team_Widget');
	register_widget('Table_Widget');
	register_widget('Gym_Widget');
});

/**
 * Include and register shortcode functionality.
 * All shortcodes are defined in shortcodes.php
 * @see src/shortcodes.php
 */
require_once GTP_DIR . '/src/shortcodes.php';

add_shortcode('gtp_teams', 'teams_shortcode');
add_shortcode('gtp_gym'  , 'gym_shortcode'  );
add_shortcode('gtp_table', 'table_shortcode');

add_shortcode('gtp_clublink', 'clublink_shortcode');
add_shortcode('gtp_team_link', 'teamlink_shortcode');


/**
 * Register the option page.
 * @see src/options.php
 */

add_action('admin_menu', function () {
    add_submenu_page('tools.php', 'Game Table Plugin', 'Game Table Plugin', 'manage_options', 'game_table_plugin_options', 'options');
});

function options() {

    if(! current_user_can('manage_options')){
        wp_die('Keine Berechtigung!');
    }

	echo "<div class='wrap'>";

	echo "<h1>Game Table Plugin</h1>";

	include GTP_DIR . '/src/options.php';

	echo "<hr />";

	include GTP_DIR . '/src/readme.html';

	echo "</div>";
}

/**
 * Show admin notice if requested.
 */

add_action('admin_notices', function (){
	$notice = get_option('gtp_notice', false);
	if($notice){
		?>
			<div class='notice notice-<?= esc_attr($notice['type']); ?> <?= ($notice['dismissible']) ? 'is-dismissible' : ''; ?>'>
				<p><?= $notice['message']; ?></p>
			</div>
		<?php
		delete_option('gtp_notice');
	}
});

/**
 * Register actions to save the settings made on the settings page.
 */
require_once GTP_DIR . '/src/save-settings.php';

/**
 * Handle plugin activation
 * When the plugin is activated the needed database tables are created, the
 * backup is loaded (if available), the options are registered and the cronjobs
 * is registered.
 */
register_activation_hook( __FILE__, function (){
	create_db_tables();
	create_options();
	get_backup();
	register_cron();
});

function create_db_tables() {
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
            own_team ENUM('host', 'guest'), -- varchar(8),
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
            has_name bit,
            PRIMARY KEY (origin_team, place),
            CONSTRAINT fk_team_score
                FOREIGN KEY (origin_team)
                REFERENCES {$wpdb->prefix}gtp_teams(shortN)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        );"
    );
}

function get_backup(){

	if(!file_exists('backup.json')) return;

	$data = json_decode(file_get_contents('backup.json'));

	global $wpdb;

	foreach($data->teams as $team){
		$wpdb->insert(
			$wpdb->prefix . 'gtp_teams',
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
	 * Since it is possible that teams were taken from the backup it is
	 * immediately checked for data from Handball4All, to provide functionality
	 * as soon as the plugin is activated again.
	 * NOTICE: This might slow the activation down massively depending on the
	 * amount of teams.
	 */
	require_once GTP_DIR . '/src/extract-transform.php';
	extract_transform();
}

function create_options(){
	add_option('gtp_clublink');
    add_option('gtp_teamname');
	add_option('gtp_next_force', time() - 1);
}

function register_cron(){
	if (!wp_next_scheduled(ACTUALIZATION_HOOK)){
		wp_schedule_event(time(), 'five_minutes', ACTUALIZATION_HOOK);
	}
}

/**
 * Handle plugin deactivation.
 * When the plugin is deleted all tables are deleted.
 * A backup of the teams table is made and stored as JSON.
 */
register_deactivation_hook(__FILE__, function () {
	make_backup();
	delete_db_tables();
	remove_options();
	unregister_cron();
});

function delete_db_tables(){
	global $wpdb;

	// tables dropped in the correct order so that no constraint is violated in the process
	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_games;");
	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_tables;");

	$wpdb->query("DROP TABLE {$wpdb->prefix}gtp_teams;");

}

function make_backup(){
	global $wpdb;

	$teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gtp_games;", OBJECT);

	if(count($teams)){
		// only make a backup if at least one team is in the database

		$result = new stdClass();
		$result->teams = $teams;

		$result->teamname = get_option('gtp_teamname');
		$result->clublink = get_option('gtp_clublink');

		file_put_contents('backup.json', json_encode($result));
	}
}

function remove_options(){
	delete_option('gtp_clublink');
	delete_option('gtp_teamname');
	delete_option('gtp_next_force');
}

function unregister_cron(){
	wp_clear_scheduled_hook(ACTUALIZATION_HOOK);
}

/**
 * Cronjob functionality.
 * Here a filter is created to enable the cron to be run every five minutes and
 * the actual function is linked to the hook.
 * The registration/unregistration is done on plugin activation/deactivation.
 */

// add a new filter to enable a cron to be called every five minutes
add_filter( 'cron_schedules', 'add_game_actualization_filter' );
function add_game_actualization_filter($schedules) {
	$schedules['five_minutes'] = [
		'interval' => 300,
		'display'  => esc_html__('Every Five Minutes')
	];

	return $schedules;
}

// link the actualization function to the corresponding hook
const ACTUALIZATION_HOOK = 'game_actualization_hook';

require_once GTP_DIR . '/src/extract-transform.php';
add_action(ACTUALIZATION_HOOK, 'extract_transform');

/**
 * Include the scripts and styles needed.
 */

// visitor side
add_action('wp_enqueue_scripts', function () {
	wp_enqueue_style('game_table_widget_style', plugins_url('src/css/widgets.css', __FILE__));
	wp_enqueue_script('game_table_widget_script', plugins_url('src/js/client.js', __FILE__));
});
