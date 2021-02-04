<?php

/**
 * This script holds the shortcode functionality.
 */
/**
 * Wrapper for the the_widget()-function.
 * While the_widget() echoes the widget's output shortcodes need to return the
 * output. This function captures the widgets output and returns it as a string.
 * The widget registration is also handeled.
 * @param string $widget - the widget's class name
 * @param array $instance - the widget's parameters
 * @return string with the widget's output
 * @see https://developer.wordpress.org/reference/functions/the_widget/
 */
function capture_widget($widget, $instance=[]){
	register_widget($widget);
	$args = [
		'before_widget' => '<div class="%s">',
		'after_widget'  => '</div>',
		'before_title'  => '<strong>',
		'after_title'   => '</strong>',
	];
	ob_start();
	the_widget($widget, $instance, $args);
	return ob_get_clean();
}

/**
 * The following shortcodes are wrappers for the widgets provided in this
 * plugin.
 * @see src/widgtets/class-gtp-widget.php
 */

function team_games_shortcode($atts, $content, $tag){
    $atts = shortcode_atts([
            'title'         => '',
            'teams'         => '',
			'link'          => '',
            'replace_names' => false,
            'direction'     => 'hor',
            'select'        => Game_Widget::LEAGUE,
            'time_select'   => '',
            'time_before'   => 0,
            'time_after'    => 0,
        ], $atts);

	return capture_widget('Team_Games_Widget', $atts);
}

function gym_shortcode($atts, $content, $tag){
    $atts = shortcode_atts([
        'title'         => '',
        'gym_no'        => '',
        'replace_names' => false,
        'direction'     => 'hor',
        'time_select'   => null,
        'time_before'   => null,
        'time_after'    => null,
    ], $atts);

	return capture_widget('Gym_Widget', $atts);
}

function table_shortcode($atts, $content, $tag){
    $atts = shortcode_atts([
        'title' => '',
        'team'  => '',
        'view'  => 'standard',
		'link'  => '',
    ], $atts);

	return capture_widget('Table_Widget', $atts);
}

function team_shortcode($atts, $content, $tag) {
	return capture_widget('Team_Widget');
}

/**
 * Function for a shortcode to wrap the content into a link heading to the
 * Handball4All club page.
 */
function clublink_shortcode($atts, $content, $tag){

	$atts = shortcode_atts([
		'target' => '_blank',
	], $atts);

	$link = get_option('gtp_clublink');

	if(is_null($link) || $link === '') return $content;
	// no clublink is set in the settings

	$target = esc_attr($atts['target']);

	return "<a href='{$link}' target='{$target}'>{$content}</a>";
}

/**
 * Function for a shortcode to wrap the content into a link that is given for
 * a particular team.
 */
function teamlink_shortcode($atts, $content, $tag){

	$atts = shortcode_atts([
		'team'   => '',
		'comp'   => 'league',
		'target' => '_blank'
	], $atts);

	if($atts['team'] == '') return $content;
	// no team is given: this shortcode is useless

	/**
	 * This array holds the fields that can be selected from the database.
	 * This makes sure that only the strings in this array can be used and no
	 * SQL-Injection can be driven through the SELECT-statement.
	 */
	$fields_possible = [
	 'league' => 'league_link',
	 'cup'    => 'cup_link'
	];

	if(array_key_exists($atts['comp'], $fields_possible)){
		$field = $fields_possible[$atts['comp']];
	}else{
		return $content;
		// no valid competition is selected: this shortcode is useless
	}

	$link = get_team_link($atts['team']);

	if(is_null($link)) return $content;
	// no link is set for the requested team and competition

	$target = esc_attr($atts['target']);

	return "<a href='{$link}' target='{$target}'>{$content}</a>";
}

/**
 * register all shortcodes created above
 */
add_shortcode('gtp_team_games', 'team_games_shortcode');
add_shortcode('gtp_gym'  , 'gym_shortcode'  );
add_shortcode('gtp_table', 'table_shortcode');
add_shortcode('gtp_team', 'team_shortcode');

add_shortcode('gtp_clublink', 'clublink_shortcode');
add_shortcode('gtp_team_link', 'teamlink_shortcode');
