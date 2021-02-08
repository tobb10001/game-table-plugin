<?php

/**
 * This script holds the Team_Widget-class.
 * This script MUST NOT be included from anywhere else than gtp-widget.php
 * Include gtp-widgets.php to access this file.
 * @see src/widgets/gtp-widgets.php
 */

class Team_Widget extends WP_Widget {

	// WP standard constructor
	public function __construct() {
		parent::__construct(
			'Team_Widget',
			__('Team Widget', 'text_domain'),
			[
				'classname' => 'team_widget',
				'description' => 'Zeigt alle Daten zu einem vom Nutzer ausgewählten Team an.'
			]
		);
	}

	// WP standard form
	public function form ($instance) {
		// no options, just a hint for the user
		?>
		<p>Platzieren von mehr als einem Widget dieser Art kann zu unerwartetem Verhalten führen und sollte daher vermieden werden.</p>
		<?php
		return parent::form($instance); // prevent displaying a submit button
	}

	// WP standard update
	// omitted, as no settings are to be made yet

	// WP standard widget
	public function widget ($args, $instance) {

		// extract needed parameters
		extract($args);

		$update_thresh = 3600; // amount of time the user cannot force an update

		// find team to display
		$sel_team = isset($_REQUEST['team']) ? sanitize_text_field($_REQUEST['team']) : null;

		// update team if wanted
		if ($sel_team !== null) {
			$last_update = team_get_last_update($sel_team);
		}
		if ($sel_team !== null && isset($_REQUEST['update']) && time() - $last_update > $update_thresh) {
			extract_transform(true, $sel_team);
		}

		// get all user-selectable teams
		$teams = get_teams_names();

		// open containers
		echo $before_widget;
		echo '<div class="game-table-widget game-table-widget-team">';

		// user team selection
		?>
		<form action="#team-widget-content">
		<fieldset>
			<legend>Teamauswahl</legend>
			<select name="team">
				<?php foreach($teams as $team){?>
					<option value="<?= esc_attr($team->shortN); ?>" <?php selected($sel_team, $team->shortN); ?>>
						<?= $team->longN; ?>
					</option>
				<?php } ?>
			</select>
			<button type="submit">Anzeigen</button>
		</fieldset>
		</form>
		<?php

		$args = [
			'before_widget' => '<div class="%s">',
			'after_widget'  => '</div>',
			'before_title'  => '<p><strong>',
			'after_title'   => '</strong></p>',
		];

		if ($sel_team !== null){
			// make sure that weekdays are displayed in the right language
			setlocale(LC_TIME, get_locale());
			?>
			<hr id='team-widget-content' style='display: none;'/>
			<p>
				Letztes Update dieses Teams: <?= strftime("%a, %d.%m., %H:%M", $last_update); ?>
				<?php if (time() - $last_update > $update_thresh) { ?>
					<a href="?team=<?= $sel_team; ?>&amp;update"><button>Aktualisieren</button></a>
				<?php } ?>
			</p>
			<?php
			/**
			 * register nested widgets
			 * this could be needed if this widget is created from somewhere other
			 * than a WP-sidebar
			 * otherwise this has no effect
			 */
			register_widget('Table_Widget');
			register_widget('Team_Games_Widget');
			// display the nested widgets
			the_widget(
				'Table_Widget',
				[
					'team'  => $sel_team,
					'title' => 'Tabelle',
					'view'  => 'standard',
					'link'  => '',
				],
				$args
			);
			the_widget(
				'Team_Games_Widget',
				[
					'teams'         => $sel_team,
					'link'          => '',
					'title'         => 'Spiele',
					'replace_names' => false,
					'direction'     => 'tab',
					'select'        => Game_Widget::BOTH,
					'time_select'   => '',
					'time_before'   => 0,
					'time_after'    => 0,
				],
				$args
			);
			// link(s) to handball4all
			$league_link = get_team_link($sel_team, 'league');
			$cup_link = get_team_link($sel_team, 'cup');
			if ($league_link !== null || $club_link !== null) {
				echo "<p>Team auf Handball4all ansehen: ";
				if ($league_link !== null)
					echo "<a href='{$league_link}' target=_blank>Liga</a>";
				if ($cup_link !== null)
					echo "<a href='{$cup_link}' target=_blank>Pokal</a>";
				echo "</p>";
			}

		}

		$clublink = get_option('gtp_clublink');
		if (strlen($clublink))
			echo "<p><a href='{$clublink}' target=_blank>Verein auf Handball4All</a> ansehen.</p>";

		// close containers
		echo '</div>';
		echo $after_widget;
	}
}
