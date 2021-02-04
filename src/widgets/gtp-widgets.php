<?php

/**
 * This script imports all widgets that belong to the plugin.
 * Instead of including all widgets individually this script should be included.
 */

require_once GTP_DIR . '/src/widgets/class-table-widget.php';
require_once GTP_DIR . '/src/widgets/class-game-widget.php';
require_once GTP_DIR . '/src/widgets/class-team-widget.php';

add_action('widgets_init', function () {
	register_widget('Team_Games_Widget');
	register_widget('Table_Widget');
	register_widget('Gym_Widget');
	register_widget('Team_Widget');
});
