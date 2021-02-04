<?php

/**
 * This script holds the Gym_Widget class.
 * This script MUST NOT be included directly. To include widgets include
 * class-gtp-widget.php
 * @see src/widgets/class-gtp-widget.php
 */

class Gym_Widget extends Game_Widget{

    public function __construct(){
        parent::__construct(
            'Gym_Widget',
            'Gym Widget',
            [
				'classname'   => 'gym_widget',
				'description' => 'Zeigt Spiele an, die in einer bestimmten Halle stattfinden.'
			]
        );
    }

    // WP standard form
    public function form($instance){

		// get current options
		$defaults = [
			'gym_no' => '',
		];

        $instance = wp_parse_args((array) $instance, $defaults);

		$gyms = get_gyms("own_team = 'host'", 'gym_town ASC');
        ?>

        <!-- gym_no -->
        <p>
            <label for="<?= $this->get_field_id('gym_no'); ?>">Hallennummer:</label>
            <input class="widefat"
                id="<?= $this->get_field_id('gym_no'); ?>"
                name="<?= $this->get_field_name('gym_no'); ?>"
                value="<?= $instance['gym_no']; ?>"
            />
        </p>

		<details>
			<summary>Verfügbare Hallen</summary>
			<p><small>Nur jene Hallen, in denen Heimspiele stattfinden sind aufgeführt.</small></p>
			<table class='widefat'>
				<thead>
					<tr><th>Nummer</th><td>Halle</td></tr>
				</thead>
				<tbody>
					<?php foreach ($gyms as $gym){ ?>
						<tr><td><?= $gym->num; ?></td><td><?= $gym->name . ' ' . $gym->town; ?></td></tr>
					<?php } ?>
				</tbody>
			</table>
		</details>

        <hr />

        <?= parent::form($instance); ?>

        <?php
    }

    // WP standard update
    public function update($new, $old){

		$old = parent::update($new, $old);

        $old['gym_no'] = isset($new['gym_no']) ? $new['gym_no'] : '';

        return $old;
    }

    // WP standard widget
    public function widget($args, $instance){

        extract($instance);

        /**
         * Get data to display.
         * Extra conditions are calculated before.
         */
        $condition = db_prepare("gym_no = %s", $gym_no);

        echo parent::html($args, $instance, $condition);
    }
}
