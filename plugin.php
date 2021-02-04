<?php
/**
 * Plugin Name: Game Table Widget
 * Description: Speichert Spiele und Tabellen von Teams, die auf Handball4All registriert sind in der eigenen Datenbank und stellt Wigets und Shortcodes zur verfügung, mit denen diese angezeigt werden können.
 * Version: 5.0.0
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

/**
 * Include and register shortcode functionality.
 * All shortcodes are defined in shortcodes.php
 * @see src/shortcodes.php
 */
require_once GTP_DIR . '/src/shortcodes.php';

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
	load_backup();
	register_cron();
});

require_once GTP_DIR . '/src/database.php';
require_once GTP_DIR . '/src/backup.php';

function create_options(){
	add_option('gtp_clublink');
    add_option('gtp_teamname');
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
