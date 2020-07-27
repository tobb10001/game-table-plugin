<?php

/**
 * This script holds the Game_Widget class base class.
 * This script MUST NOT be included directly. To include widgets include
 * gtp-widgets.php
 * @see src/widgets/gtp-widgets.php
 */

/**
 * The class holds everything that all game widgets use.
 * This class is held absctract because there is no use case for a widget
 * without extra conditions.
 */

abstract class Game_Widget extends WP_Widget{

    /**
     * Constants to define which competition is requested.
     */

    public const LEAGUE = 'league';
    public const CUP = 'cup';
    public const BOTH = 'both';

    /**
     * Constants to define the links.
     */

    private const TICKER_LINK = 'http://spo.handball4all.de/service/ticker.html?appid=&token=';
    private const REPORT_LINK = 'https://spo.handball4all.de/misc/sboPublicReports.php?sGID=';

    /**
     * Constant to set default settings for shared settings.
     */

    protected const SHARED_DEFAULTS = [
		'title'         => '',
        'replace_names' => false,
        'direction'     => 'hor',
        'select'        => self::LEAGUE,
        'time_select'   => '',
        'time_before'   => 0,
        'time_after'    => 0,
    ];

    /**************************************************************************/
    /**
     * Functions in the following section belong to the admin settings
     */

    /**
     * This function provides form elements shared by all game widgets.
     * Those are the options defined in the SHARED_DEFAULTS constant.
	 * @param array $instance - Representation of the widget's current settings
	 * @return string HTML-fragment containing the form elements
     */

    public function form($instance){
        $instance = wp_parse_args((array) $instance, self::SHARED_DEFAULTS);

        // precalculate data needed in the form
        $directions = [
            'hor' => 'Horizontal',
            'ver' => 'Vertikal',
            'tab' => 'Tabelle',
        ];
        $selections = [
            self::LEAGUE => 'Liga',
            self::CUP => 'Pokal',
            self::BOTH => 'Beides'
        ];
        $time_select = [
            '' => 'Keine Einschränkung',
            'man' => 'Manuell',
            'today' => 'Heute',
            'last_we' => 'Letztes Wochenende',
            'next_we' => 'Nächstes Wochenende',
            'last_next_we' => 'Letztes und nächstes Wochenende',
        ];

        // capture output
        ob_start();
        ?>

        <!-- title -->
        <p>
            <label for='<?= $this->get_field_id('title'); ?>'>Titel</label>
            <input class="widefat"
                id='<?= $this->get_field_id('title'); ?>'
                name='<?= $this->get_field_name('title'); ?>'
                value='<?= esc_attr($instance['title']); ?>'
            />
        </p>

        <!-- replace_names -->
        <p>
            <input
                id="<?= esc_attr($this->get_field_id('replace_names')); ?>"
                name="<?= esc_attr($this->get_field_name('replace_names')); ?>"
                type="checkbox" value='1'
                <?= checked('1', $instance['replace_names']); ?>
            />
            <label for="<?= esc_attr($this->get_field_id('replace_names')); ?>">
                Namen ersetzen
            </label>
        </p>
        <p>
            Legt fest, ob der geladene Vereinsname durch den vereinsinternen Mannschaftsnamen ersetzt werden soll. Nützlich, wenn verschiedene Mannschaften angezeigt werden.
        </p>

        <!-- direction -->
        <p>
            <label for="<?= $this->get_field_id('direction'); ?>">
                Richtung (Legt fest, ob Gegner neben- oder übereinander erscheinen.):
            </label>
            <select
                name="<?= $this->get_field_name('direction'); ?>"
                id="<?= $this->get_field_id('direction'); ?>"
                class="widefat"
            >
                <?php foreach ($directions as $key => $value){ ?>
                    <option
                        value="<?= esc_attr($key); ?>"
                        <?= selected($instance['direction'], $key, false); ?>
                    >
                        <?= $value; ?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <!-- select -->
        <p>
            <label for="<?= $this->get_field_id('select'); ?>">Wettbewerbe</label>
            <select
                name="<?= $this->get_field_name('select'); ?>"
                id="<?= $this->get_field_id('select'); ?>"
                class="widefat"
            >
                <?php foreach($selections as $key => $value){ ?>
                    <option
                        value="<?= esc_attr($key); ?>"
                        <?= selected($instance['select'], $key, false); ?>
                    >
                        <?= $value; ?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <!-- time_select -->
        <p>
            <label for="<?= $this->get_field_id('time_select'); ?>">
                Zeitliche Einschränkung
            </label>
            <select
                name="<?= $this->get_field_name('time_select'); ?>"
                id="<?= $this->get_field_id('time_select'); ?>"
                class="widefat"
            >
                <?php foreach($time_select as $key => $value){ ?>
                    <option
                        value="<?= esc_attr($key); ?>"
                        <?= selected($instance['time_select'], $key, false); ?>
                    >
                        <?= $value; ?>
                    </option>
                <?php } ?>
            </select>
        </p>

        <!-- time_before -->
        <p>
            <input
                id="<?= $this->get_field_id('time_before'); ?>"
                name="<?= $this->get_field_name('time_before'); ?>"
                type="number" min=0
                value="<?= esc_attr($instance['time_before']); ?>"
            />
            <label
                for="<?= $this->get_field_id('time_before'); ?>"
            >
                vergangene Tage
            </label>
        </p>

        <!-- time_after -->
        <p>
            <input
                id="<?= $this->get_field_id('time_after'); ?>"
                name="<?= $this->get_field_name('time_after'); ?>"
                type="number" min=0
                value="<?= esc_attr($instance['time_after']); ?>"
            />
            <label
                for="<?= $this->get_field_id('time_after'); ?>"
            >
                nächste Tage
            </label>
        </p>
        <?php
        //capture and return output
        return ob_get_clean();
    }

    /**
     * Updates shared settings.
     * Compare shared_form() for detail.
	 * @param array $new - Representation of the instance's new settings.
	 * @param array $old - Representation of the instance's old settings.
	 * @return array the instance with updated values
     */

    public function update($new, $old){

        $old['title'] = isset($new['title']) ? wp_strip_all_tags($new['title']) : '';
        $old['replace_names'] = isset($new['replace_names']);
        $old['direction'] = isset($new['direction']) ? $new['direction'] : 'hor';
        $old['select'] = isset($new['select']) ? $new['select'] : self::LEAGUE;
        $old['time_select'] = isset($new['time_select']) ? $new['time_select'] : '';
        $old['time_before'] = isset($new['time_before']) ? $new['time_before'] : '';
        $old['time_after'] = isset($new['time_after']) ? $new['time_after'] : '';

        return $old;

    }

    /**************************************************************************/
    /**
     * Functions in the following section belong to the widget display.
     */

    /**
     * This function calculates the interval (start and endtime) of which games to
     * select by the current time and the options given.
     * It returns an stdObj with $start and $end set to UNIX timestamps.
	 * @param string $select - the selection of the mode made by the user.
	 * @param int $before - the time before now in days (if given)
	 * @param int $after - the time after now in days (if given)
	 * @return stdClass object with $start and $end as UNIX imestamps
     */

    private final static function interval($select, $before=0, $after=0){

        // initialize variables
        $interval = new stdClass();

        $current_date = new DateTime(date('Y-m-d') . ' 00:00:00');
        $dow = intval($current_date->format('N')); // dow = day of week

        if($select == 'man'){

            /**
             * Period defined manually.
             * The $before and $after parameters define how many days in the past/
             * future should be considered.
             * The time is calculated by converting the number of days into a
             * DateInterval and applying it to the current date.
             */

            $int_before = new DateInterval('P' . $before . 'D');
            $int_before->invert = 1;
            $interval->start = clone($current_date)->add($int_before);

            $int_after = new DateInterval('P' . strval(intval($after) + 1) . 'D');
            // one spot further, because it is placed ad 00:00
            $interval->end = clone($current_date)->add($int_after);

        }elseif($select == 'today'){

            /**
             * Period defined to show all games starting today.
             */
            $interval->start = $current_date;
            $interval->end = new DateTime('tomorrow');

        }elseif($select == 'last_we'){

            /**
             * Period defined to the last weekend.
             * This means that all games are shown that start on/after last friday
             * and before/on the following monday.
             * If the current day is within friday to monday this returns the
             * current weekend.
             */
            $interval->start = ($dow == 5) ? $current_date : new DateTime('last friday');
            if($dow == 2){ // during tuesday
                $interval->end = $current_date;
            }elseif($dow > 2 && $dow < 5){ // during wednesday or thursday
                $interval->end = new DateTime('last tuesday');
            }else{ // during friday, saturday, sunday or monday (= weekend)
                $interval->end = new DateTime('next tuesday');
            }

        }elseif($select == 'next_we'){

            /**
             * Period defined to the next weekend. See 'last weekend' for detail.
             */
            if($dow == 5){ // during friday
                $interval->start = $current_date;
            }elseif($dow > 5 || $dow == 1){ // during saturday, sunday or monday
                $interval->start = new DateTime('last friday');
            }else{ // during tuesday, wednesday, thursday
                $interval->start = new DateTime('next friday');
            }

            $interval->end = ($dow == 2) ? $current_date : new DateTime('next tuesday');

        }elseif($select == 'last_next_we'){
            /**
             * Period defined to the last AND next weekend. See 'last weekend' for
             * detail.
             */
            $interval->start = new DateTime('last friday');
            $interval->end = new DateTime('next tuesday');
        }

        $interval->start = $interval->start->getTimestamp();
        $interval->end = $interval->end->getTimestamp();

        return $interval;
    }

    /**
     * Wrapper for the interval()-function that returns the interval as an SQL-
     * condition.
	 * @param string $select - the selection of the mode made by the user.
	 * @param int $before - the time before now in days (if given)
	 * @param int $after - the time after now in days (if given)
	 * @return string SQL-Condition matching the requirements
	 *
	 * @see self::interval()
     */

    public final static function interval_condition($select, $before=0, $after=0){
        $interval = self::interval($select, $before, $after);
        return "start BETWEEN {$interval->start} AND {$interval->end}";
    }

    /**
     * Queries the database to make sure the games are represented in the
     * correct form.
     * $condition is expected to be an SQL condition (non escaped) that can be
     * linked to existing conditions with AND.
     * Also calculates additional data needed in html().
	 * @param array $instance - Representation of the instance's settings.
	 * @param string $added_condition - SQL-Condition applied on top of existing
	 * ones.
	 * @return array list of games fitting the condition
     */
    private final static function get_games($time, $added_condition=''){

        // additional conditions
        $conditions = [];
        if($time->select != ''){
            $conditions[] = self::interval_condition(
				$time->select, $time->before, $time->after
            );
        }
        if($added_condition != ''){
            $conditions[] = '(' . $added_condition . ')';
        }
        $conditions = (count($conditions) > 0) ? 'AND ' . implode(' AND ', $conditions) : '';

        global $wpdb;

        $games_table = $wpdb->prefix . 'gtp_games';
        $teams_table = $wpdb->prefix . 'gtp_teams';

        $additional = ($added_condition != '') ? "AND ({$added_condition})" : '';

        $games = $wpdb->get_results((
            "SELECT {$teams_table}.longN as origin_team_name, {$games_table}.*
            FROM {$games_table}, {$teams_table}
            WHERE {$teams_table}.shortN = {$games_table}.origin_team
                {$conditions}
            ORDER BY start ASC;"
        ), OBJECT);

        foreach($games as $game){
            $game->has_score = self::has_score($game);
            $game->has_score_ht = self::has_score($game, true);
            $game->cancelled = self::cancelled($game);
        }
        return $games;
    }

    /**
     * Returns if $game has a score or not as boolean.
     * If $ht is true only halftime is considered.
	 * @param stdClass $game - the game object to consider
	 * @param boolean $ht (optional) whether to consider the halftime only
	 * @return boolean whether the game has a score
     */
    private final static function has_score($game, $ht=false){
        if (!$ht) {
            return $game->host_goals > 0 || $game->guest_goals > 0;
        } else {
            return $game->host_goals_ht > 0 || $game->guest_goals_ht > 0;
        }
    }

    /**
     * Returns if the provided game is cancelled.
	 * @param stdClass $game - the game object to consider
	 * @return boolean whether the given game is cancelled
     */

    private final static function cancelled($game){
    	/**
		 * NOTE: At the moment there is no way to tell that a game has been
		 * cancelled. However, this may be possible in the future.
		 * ATM false is returned to treat every game as if it wasn't cancelled.
		 */
        return false;
    }

    /**
     * Given a cancelled game this function returns a text to tell the user
     * what happend with the game.
     * Only call this function if the game is definetly cancelled, as it won't
     * check again.
	 * @param stdClass $game - the game to consider
	 * @return string alternative to the result after a cancelled game
     */
    private final static function cancelled_alt($game){
        $res = false;
        $text = false;

        if ($game->host_points > 0 || $game->guest_points > 0) {
            if ($game->host_points > $game->guest_points) $res = 'host';
            elseif ($game->host_points < $game->guest_points) $res = 'guest';
            else $res = 'tie';
        }

        if ($res !== false) {
            if ($text !== 'tie') {
                $text = "Spiel abgesagt. Sieger: {$game->$res}";
            } else {
                $text = 'Spiel abgesagt. Punkteteilung';
            }
        }

        return $text;
    }

	/**
	 * Returns if in a given list of games at least one game is currently being
	 * live.
	 * @param object[] $games - the games to check
	 * @return boolean
	 */
	private static function has_live_game($games){
		foreach($games as $game){
			if($game->live) return true;
		}
		return false;
	}

    /**
     * This function produces an HTML output for given games.
     * It takes an array as parameter which has
     * - the components of the $args-array
     * - the database condition to fetch the games (as ['condition'])
     * - (optional) the widgets title (as ['title'])
	 * @param array $args - The WP-standard args
	 * @param array $instance - The Widget's instance
	 * @param string $condition - The Additional condition used to query the games (added to SQL-WHERE)
	 * @param string $appending - an HTML-fragment added after the games (inside the widget's container)
	 * @return string HTML-fragment holding the widget's code
     */
    protected final static function html($args, $instance, $condition='', $appending=''){

        extract($args);
		extract($instance);

        $to_show = self::get_games(
			(object) [
				'select' => $time_select,
				'before' => $time_before,
				'after'  => $time_after
			],
			$condition
		);

        // make sure that weekdays are displayed in the right language
        setlocale(LC_TIME, get_locale());

        // capture output
        ob_start();
        ?>
        <!-- open containers -->
        <?= $before_widget ?>
        <div class='game-table-widget game-table-widget-games game-table-widget-games-<?= $direction; ?> <?= self::has_live_game($to_show) ? 'has-live' : ''; ?>'>

        <!-- display title if given -->
        <?php
            if(isset($title) && $title != ''){
                echo $before_title . $title . $after_title;
            }
        ?>

        <?php
		if(!count($to_show) > 0){ ?>
			<p>Keine Spiele.</p>
		<? }elseif($direction == 'hor'){
            foreach($to_show as $game){
            ?>
                <table class='game-table-widget-games game-table-widget-games-hor'>
                    <tr>
                        <!-- host -->
                        <td <?= ($game->own_team == 'host') ? "class='game-table-widget-games-team'" : ''; ?>>
                            <?= ($game->own_team == 'host' && $replace_names) ? $game->origin_team_name : $game->host; ?>
                        </td>
                        <!-- guest -->
                        <td <?= ($game->own_team == 'guest') ? "class='game-table-widget-games-team'" : ''; ?>>
                            <?= ($game->own_team == 'guest' && $replace_names) ? $game->origin_team_name : $game->guest; ?>
                        </td>
                        <!-- time -->
                        <td>
                            <?= ($game->start > 0) ? strftime('%a, %d.%m.%y, %H:%M', $game->start) : '<em>Keine Zeitangabe</em>'; ?>
                        </td>
                    </tr>
                    <?php if($game->live){ ?>
                        <tr>
                            <td class='live-standing'><?= $game->host_goals; ?></td>
                            <td class='live-standing'><?= $game->guest_goals; ?></td>
                            <td><a href="<?= self::TICKER_LINK . $game->gToken; ?>" target="_blank">zum Liveticker!</a></td>
                        </tr>
                    <?php }elseif($game->has_score){ ?>
                        <tr>
                            <td><?= $game->host_goals;?><?= $game->has_score_ht ? ' <small>(' . $game->host_goals_ht . ')</small>' : ''; ?></td>
                            <td><?= $game->guest_goals;?><?= $game->has_score_ht ? ' <small>(' . $game->host_goals_ht . ')</small>' : ''; ?></td>
                        </tr>
                    <?php }elseif(self::cancelled($game)){ ?>
                        <tr>
                            <td colspan=3><?= self::cancelled_alt($game); ?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan=3>
                            <a
                                href='https://www.google.com/maps/search/?api=1&query=<?= urlencode("{$game->gym_name}, {$game->gym_street}, {$game->gym_post_code} {$game->gym_town}"); ?>'
                                target='_blank'
                            >
                                <?= "{$game->gym_name}, {$game->gym_street}, {$game->gym_post_code} {$game->gym_town}"; ?>
                            </a>
                        </td>
                    </tr>
                </table>
            <?php }
        }elseif($direction == 'ver'){
            foreach($to_show as $game){
            ?>
                <table class='game-table-widget-games game-table-widget-games-ver'>
                    <!-- host -->
                    <tr>
                        <td class='<?=($game->own_team == 'host') ? 'game_table_widget_games_team' : ''; ?>'>
                            <?= ($game->own_team == 'host' && $replace_names) ? $game->origin_team_name : $game->host ?>
                        </td>
                        <?php if($game->has_score){ ?>
                            <td><?= $game->host_goals; ?></td>
                        <?php } ?>
                        <?php if($game->has_score_ht){ ?>
                            <td><small><?= $game->host_goals_ht; ?></small></td>
                        <?php } ?>
                    </tr>
                    <!-- guest -->
                    <tr>
                        <td class='<?=($game->own_team == 'guest') ? 'game_table_widget_games_team' : ''; ?>'>
                            <?= ($game->own_team == 'guest' && $replace_names) ? $game->origin_team_name : $game->guest ?>
                        </td>
                        <?php if($game->has_score){ ?>
                            <td><?= $game->guest_goals; ?></td>
                        <?php } ?>
                        <?php if($game->has_score_ht){ ?>
                            <td><small><?= $game->guest_goals_ht; ?></small></td>
                        <?php } ?>
                    </tr>
                    <!-- time -->
                    <tr>
                        <td colspan='<?= $game->has_score ? ($game->has_score_ht ? '3' : '2') : '1'; ?>'>
                            <?= ($game->start > 0) ? strftime('%a, %d.%m.%y, %H:%M', $game->start) : '<em>Keine Zeitangabe</em>'; ?>
                            <?php if($game->live){ ?>
                                <a href='<?= self::TICKER_LINK . $game->gToken; ?>' target='_blank'>Liveticker!</a>
                            <?php }elseif($game->sGID){ ?>
                                <a href='<?= self::REPORT_LINK . $game->sGID; ?>' target='_blank'>Spielbericht</a>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            <?php }
        }elseif($direction == 'tab'){
            $only_upcoming = ($to_show[0]->start > time());
            ?>
                <table class='game-table-widget-games game-table-widget-games-tab'>
                    <?php foreach($to_show as $game){ ?>
                        <tr>
							<?php if($game->start > 0){ ?>
	                            <td><?= strftime('%a, %d.%m.%y', $game->start); ?></td>
	                            <td><?= date('G:i', $game->start); ?></td>
							<?php }else{ ?>
								<td colspan="2"><em>Keine Zeitangabe</em></td>
							<?php } ?>
                            <td class='<?= ($game->own_team == 'host') ? 'game-table-widget-games-team' : ''; ?>'>
                                <?= ($game->own_team == 'host' && $replace_names) ? $game->origin_team_name : $game->host; ?>
                            </td>
                            <?php if($game->has_score){ ?>
                                <td>
                                    <?= "{$game->host_goals}:{$game->guest_goals}"; ?>
                                    <?php if($game->has_score_ht){ ?>
                                        <br /><small>(<?= "{$game->host_goals_ht}:{$game->guest_goals_ht}"; ?>)</small>
                                    <?php } ?>
                                </td>
                            <?php }elseif(!$only_upcoming){ ?>
                                <td></td>
                            <?php } ?>
                            <td class='<?= ($game->own_team == 'guest') ? 'game-table-widget-games-team' : ''; ?>'>
                                <?= ($game->own_team == 'guest' && $replace_names) ? $game->origin_team_name : $game->guest; ?>
                            </td>
                            <td>
                                <a href='https://www.google.com/maps/search/?api=1&query=<?= urlencode("{$game->gym_name}, {$game->gym_street}, {$game->gym_post_code} {$game->gym_town}"); ?>' target="_blank">
                                    <?= "{$game->gym_name}, {$game->gym_street}, {$game->gym_post_code} {$game->gym_town}" ?>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php
        }
        ?>

		<?= $appending; ?>

        <!-- close containers -->
        </div>
        <?= $after_widget; ?>

        <?php
        // capture output
        $code = ob_get_clean();
        return $code;
    }
}

/**
 * Include the extending classes.
 */

require_once GTP_DIR . '/src/widgets/class-team-widget.php';
require_once GTP_DIR . '/src/widgets/class-gym-widget.php';
