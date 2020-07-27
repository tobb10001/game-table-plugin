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
        $condition = "gym_no = '{$gym_no}'";

        echo parent::html($args, $instance, $condition);
    }
}
