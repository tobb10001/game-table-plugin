<?php

/**
 * This script holds the widget meant to display the table of one team.
 * This script MUST NOT be included from anywhere else than gtp-widget.php
 * Include gtp-widget.php to access this file.
 * @see src/widgets/gtp-widget.php
 */

class Table_Widget extends WP_Widget{

    // WP Standard constructor
    public function __construct(){
        parent::__construct(
            'Table_widget',
            __('Table Widget', 'text_domain'),
			[
				'classname'   => 'table_widget',
				'description' => 'Zeigt die Tabelle einer Mannschaft an.'
			]
        );
    }

    // WP standart form
    public function form($instance){

		// get current options
		$defaults = [
			'team'  => '',
			'title' => '',
			'view'  => 'standard',
			'link'  => '',
		];

        $instance = wp_parse_args((array) $instance, $defaults);

        // get available teams
        global $wpdb;
        $teams = $wpdb->get_results(
            "SELECT shortN, longN FROM {$wpdb->prefix}gtp_teams ORDER BY longN ASC;",
            OBJECT
        );
        ?>

        <!-- option: title -->
        <p>
            <label for="<?= esc_attr($this->get_field_id('title')); ?>">Überschrift</label>
            <input class="widefat" id="<?= esc_attr($this->get_field_id('title')); ?>"
                   name="<?= esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?= esc_attr($instance['title']); ?>"/>
        </p>

        <!-- option: team -->
        <p>
            <label for="<?= esc_attr($this->get_field_id('team')); ?>">Team</label>
            <select class="widefat" id="<?= esc_attr($this->get_field_id('team')); ?>"
                name="<?= esc_attr($this->get_field_name('team')); ?>" type="text">
                <?php foreach($teams as $team){ ?>
                    <option
                        value="<?= $team->shortN; ?>"
						<?= selected($instance['team'], $team->shortN, false); ?>
                    ><?= $team->longN; ?></option>
                <?php } ?>
            </select>
        </p>

        <!-- option: view -->
        <p>
            Ansicht: Legt fest, ob die Standartansicht oder eine schmalere (detailärmere) Version angezeigt wird.

			<br />

            <input
                type="radio"
                name="<?= $this->get_field_name('view'); ?>"
                id="<?= $this->get_field_id('view') . '_std' ?>"
                value="standard"
                <?= checked($instance['view'], 'standard', false); ?>
            />
            <label for="<?= $this->get_field_id('view') . '_std' ?>">Standard</label>

			<br />

            <input
                type="radio"
                name="<?= $this->get_field_name('view'); ?>"
                id="<?= $this->get_field_id('view') . '_slim' ?>"
                value="slim"
                <?= checked($instance['view'], 'slim', false); ?>
            />
            <label for="<?= $this->get_field_id('view') . '_slim' ?>">Schmal</label>
        </p>

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

        <?php
    }

    // WP standard update
    public function update($new, $old){

        $old['title'] = isset($new['title']) ? sanitize_text_field($new['title']) : '';
        $old['team'] = isset($new['team']) ? sanitize_text_field($new['team']) : '';
        $old['view'] = isset($new['view']) ? sanitize_text_field($new['view']) : 'standard';
		$old['link'] = isset($new['link']) ? sanitize_text_field($new['link']) : '';
        return $old;

    }

    // WP standard widget
    public function widget($args, $instance){

        // extract needed parameters
        extract($args);
        extract($instance);

        // query database
		$table = get_teamscores(db_prepare('origin_team = %s', $team), 'place ASC');

		if(strlen($link)){
			$link_url = get_team_link($team, 'league');
		}

        // open container
        echo $before_widget;
        echo "<div class='game-table-widget game-table-widget-table game-table-widget-table-$view'>";

        // show title if one is given
        if($title !== ''){
            echo $before_title . $title . $after_title;
        }

        // widget content
        ?>

        <table class='game-table-widget-table game-table-widget-table-<?= $view; ?>'>
            <thead>
                <tr>
                    <th>Platz</th>
                    <th>Mannschaft</th>
                    <th>Spiele</th>
                    <?php if($view == 'standard'){ ?>
                        <th>S:U:N</th>
                        <th>Tore</th>
                    <?php } ?>
                    <th>Punkte</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($table as $team){ ?>
                    <tr class='<?= ($team->has_name) ? 'game-table-widget-table-team' : ''; ?>'>
                        <td><?= $team->place; ?></td>
                        <td><?= $team->team; ?></td>
                        <td><?= $team->games; ?></td>
                        <?php if($view == 'standard'){ ?>
                            <td><?= "{$team->won}:{$team->tied}:{$team->lost}"; ?></td>
                            <td><?= "{$team->goals_shot}:{$team->goals_recieved}"; ?></td>
                        <?php } ?>
                        <td><?= $team->points_plus . ':' . $team->points_minus; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

		<?php

		// display a link if wanted
		if($link !== '' && isset($link_url)){
			echo "<a href='{$link_url}' target='_blank'>{$link}</a>";
		}

        // close containers
        echo "</div>";
        echo $after_widget;
    }
}
