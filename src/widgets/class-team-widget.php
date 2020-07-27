<?php

/**
 * This script holds the Team_Widget class.
 * This script MUST NOT be included directly. To include widgets include
 * class-gtp-widget.php
 * @see src/widgets/class-gtp-widget.php
 */

class Team_Widget extends Game_Widget{

    // WP standard __construct
    public function __construct(){
        parent::__construct(
            'Team_widget',
            'Team Widget',
			[
				'classname'   => 'team_widget',
				'description' => 'Zeigt die Spiele einer oder mehrerer Mannschaften an.'
			]
        );
    }

    // WP standard form
    public function form($instance){

		// get current options
		$defaults = [
			'teams' => '',
			'link'  => '',
		];

        $instance = wp_parse_args((array) $instance, $defaults);

		// get teams from database to display available teams
		global $wpdb;

		$teams = $wpdb->get_results("SELECT shortN, longN FROM {$wpdb->prefix}gtp_teams", OBJECT);
        ?>

        <!-- option: teams -->
        <p>
            <label for="<?= $this->get_field_id('teams'); ?>">Teams (durch Kommata getrennte Liste von Teamkürzeln):</label>
            <input class="widefat"
                id="<?= $this->get_field_id('teams'); ?>"
                name="<?= $this->get_field_name('teams'); ?>"
                value="<?= esc_attr($instance['teams']); ?>"
            />
        </p>

		<details>
			<summary>Verfügbare Teams</summary>
			<table class='widefat'>
				<thead>
					<tr><th>Teamkürzel</th><th>Team</th></tr>
				</thead>
				<tbody>
					<?php foreach($teams as $team){ ?>
						<tr>
							<td><?= $team->shortN; ?></td>
							<td><?= $team->longN; ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</details>

		<!-- option: link -->
		<p>
			<label for='<?= $this->get_field_id('link'); ?>'>Linktext:</label>
			Erzeugt einen Link zur Seite auf Handball4All, wenn ein Text gegeben ist.
			<input
				type="text"
				name="<?= $this->get_field_name('link'); ?>"
				id="<?= $this->get_field_id('link'); ?>"
				value="<?= $instance['link']; ?>"
				class="widefat"
			/>
		</p>

        <hr />

        <?= parent::form($instance); ?>

        <?php
    }

	// WP standard update
    public function update($new, $old){

		$old = parent::update($new, $old);

        if(isset($new['teams']) && $new['teams'] != ''){
            $teams = explode(',', $new['teams']);
            foreach($teams as &$team){
                $team = trim($team);
            }
            unset($team);
            $old['teams'] = implode(',', $new['teams']);
        }else{
            $old['teams'] = '';
        }

		$old['link'] = isset($new['link']) ? sanitize_text_field($new['link']) : '';

        return $old;

    }

    // WP widget
    public function widget($args, $instance){
        extract($instance);

        /**
         * Get data to display.
         * Extra conditions are calculated before.
         */
        $conditions = [];
        // teams
        if(!$teams == ''){
            $conditions[] = "origin_team IN ('" . implode("', '", explode(',', $teams)) . "')";
        }
        // select
        if($select == self::LEAGUE){
            $conditions[] = "type = 'LEAGUE'";
        }elseif($select == self::CUP){
            $condition[] = "type = 'CUP'";
        }

		/**
		 * Decide whether to display a link and generate it.
		 */
		$appending = '';
		if($link !== ''){
			/**
			 * If only one team is selected and not both competitions are
			 * selected the corresponding link is taken from the database.
			 * Otherwise it's always the clublink.
			 */
			if($teams !== '' && strpos($teams, ',') === false&& $select !== self::BOTH){
				// exactly one team (not all and no enumeration); not both comps
				$field = (($select == 'league') ? 'league' : 'cup') . '_link';
				global $wpdb;
				$link_url = $wpdb->get_var(
					$wpdb->prepare("SELECT {$field} FROM {$wpdb->prefix}gtp_teams WHERE shortN = %s", $teams)
				);
			}else{
				$link_url = get_option('gtp_clublink');
			}
			if($link_url){
				$appending ="<a href='{$link_url}' target='_blank'>{$link}</a>";
			}
		}

        echo parent::html($args, $instance, implode( ' AND ', $conditions), $appending);

    }
}
