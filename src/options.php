<?php

/**
 * This script is the options page that is displayed to an administrator.
 * The PHP-Part handles an Input that was send from the administrator;
 * the HTML-Part actually displays the options page.
 * The data needed to change the actual options are recieved via the POST method.
 */

// checking for the needed capability
if(!current_user_can('manage_options')) {
	wp_die('Du hast keine gültige Berechtigung zu dieser Seite.');
}

// get database to display later
global $wpdb;

$teams = (array) $wpdb->get_results(
	"SELECT
		shortN,
		longN,
		IF(ISNULL(league_link), '', league_link) as league_link,
		CONCAT('[…]', SUBSTRING(IF(ISNULL(league_link), '', league_link), 40)) as league_link_short,
		IF(ISNULL(cup_link), '', cup_link) as cup_link,
		CONCAT('[…]', SUBSTRING(IF(ISNULL(cup_link), '', cup_link), 40)) as cup_link_short
	FROM {$wpdb->prefix}gtp_teams
	ORDER BY shortN ASC",
	OBJECT
);

// add an empty entity to enable adding a new team
array_unshift($teams, (object) ['shortN' => '_new', 'longN' => '', 'league_link' => '', 'cup_link' => '']);

// get style for the style setting
$style = file_get_contents(GTP_DIR . '/src/css/widgets.css');

?>
<h2>Einstellungen</h2>
<h3>Teamname</h3>
<form method='POST' action='<?= admin_url('admin-post.php'); ?>'>
    <input type="hidden" name="action" value="gtp_save_name" />
	<?php wp_nonce_field('gtp_save_name'); ?>
    <p>
		Gib hier den Teamnamen ein, unter dem die Teams in den Tabellen und Spielen gefunden werden.<br/>
		Falls das Team unter mehreren Namen vorkommen kann trenne diese durch Kommata.<br/>
		Die Zahl, die eine weitere Mannschaft kennzeichnet wird automatisch verarbeitet und muss nicht nochmals eingegeben werden, d.h. bei der Eingabe von <em>Teamname</em> wird das System auch
        <em>Teamname 2</em> und <em>Teamname 3</em> finden.<br />
		Der Teamname wird in den Spiel- und Tabellendaten gesucht um das zum Verein gehörende Team zu finden. Das ermöglicht, dass bei der Spielansicht der Name des eigenen Teams durch den internen Namen ersetzt werden kann, dass das eigene Team in einer Tabelle hervorgehoben werden können. Außerdem werden Pokalspiele identifiziert, da diese im Gegensatz zu Ligaspielen nicht für eine Mannschaft angefragt werden können.
	</p>
    <label for="teamname">Teamname:</label>
	<input
		type="text"
		name="teamname"
		value='<?= esc_attr(get_option('gtp_teamname')); ?>'
		id='teamname'
	>
	<?php submit_button('Speichern'); ?>
</form>
<hr />
<h3>Vereinslink</h3>
<form method='POST' action='<?= admin_url('admin-post.php'); ?>'>
    <input type="hidden" name="action" value="gtp_save_club_link">
	<?php wp_nonce_field('gtp_save_club_link'); ?>
    <p>Gib hier den Vereinslink ein, der auf <a href='handball4all.de' target='_blank'>Handball4All</a> zu jener Seite führt, auf der die Zusammenfassung deines Vereins zu sehen ist.<br />
	Du findest diese, indem du den Bereich auswählst und dann deinen Vereinsnamen im Suchfeld <em>Vereinssuche</em> eingbst und aus der Liste auswählst.</p>
    <label for="clublink">Clublink:</label>
    <input
        type="text"
        name="clublink"
        value='<?= esc_attr(get_option('gtp_clublink')); ?>'
		id='clublink'
       >
    <br/>
    <?php submit_button('Speichern'); ?>
</form>
<hr />
<div id='team-editor'>
	<h3 style='display: inline-block;'>Mannschaften</h3>
	<button
		type='button'
		class='page-title-action edit-team-button'
		data-team='_new'
	>
		Team hinzufügen
	</button>
	<noscript>
		<strong>Hinweis:</strong><br />
		JavaScript ist deaktiviert. Die Bearbeitung der Teams ist ohne JavaScript nicht möglich.
	</noscript>
	<!--
		The following forms are created for each team and made visible
		(JS; form.style.display = 'initial';) when needed.
		@see /src/js/form.js
	-->
	<?php foreach($teams as $team){ ?>
	<form method='POST' action='<?= admin_url('admin-post.php'); ?>' id="team-form-<?= esc_attr($team->shortN); ?>" class='team-form' style='display: none;'>
		<input type="hidden" name="action" value="gtp_save" />
		<?php wp_nonce_field('gtp_save_' . $team->shortN); ?>
		<input type="hidden" name="short-before" value="<?= esc_attr($team->shortN); ?>" />
		<p>
			<strong id='team-form-name-<?= esc_attr($team->shortN); ?>'><?= $team->longN; ?></strong>
		</p>
		<p>
			<label for="short-<?= esc_attr($team->shortN); ?>">Kürzel:</label>
			<input
				type="text"
				name="short"
				value="<?= ($team->shortN !== '_new') ? esc_attr($team->shortN) : ''; ?>"
				pattern='[^, ]{1,8}'
				id="short-<?= esc_attr($team->shortN); ?>"
				required
			/>
			<small>Darf keine Kommata oder Leerzeichen enthalten.</small>
		</p>
		<p>
			<label for="name-<?= esc_attr($team->shortN); ?>">Name:</label>
			<input type="text" name="name" value="<?= esc_attr($team->longN); ?>"	size="16" id="name-<?= esc_attr($team->shortN); ?>"	required />
		</p>
		<p>
			<label for="link-league-<?= esc_attr($team->shortN); ?>">Link (Liga):</label>
			<input name="link-league" type="text" value="<?= esc_attr($team->league_link); ?>" size="90" id="link-league-<?= esc_attr($team->shortN); ?>" />
		</p>
		<p>
			<label for="link-cup">Link (Pokal):</label>
			<input name="link-cup" type="text" value="<?= esc_attr($team->cup_link); ?>" size="90" id="link-cup-<?= esc_attr($team->shortN); ?>" />
		</p>
		<?php submit_button('Speichern'); ?>
	</form>
	<?php } ?>
	<?php if(count($teams) > 1) {?>
	<table id='teams-table' class='widefat'>
		<thead>
			<tr>
				<th>Name</th>
				<th>Kürzel</th>
				<th>Link (Liga)</th>
				<th>Link (Pokal)</th>
				<th>Bearbeiten</th>
				<th>Löschen</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach(array_slice($teams, 1) as $team){ ?>
			<tr id='row-team-<?= $team->shortN; ?>'>
				<td><?= $team->longN; ?></td>
				<td><?= $team->shortN; ?></td>
				<td><?= ($team->league_link != '') ? $team->league_link_short : '<em>NA</em>'; ?></td>
				<td><?= ($team->cup_link != '') ? $team->cup_link_short : '<em>NA</em>' ; ?></td>
				<td>
					<button
						type='button'
						class=' button edit-team-button'
						data-team='<?= $team->shortN; ?>'
					>
						Bearbeiten
					</button>
				</td>
				<td>
					<form method='POST' action='<?= admin_url('admin-post.php'); ?>'>
						<input type='hidden' name='action' value='gtp_delete' />
						<?php wp_nonce_field('gtp_delete_' . $team->shortN); ?>
						<input type="hidden" name='short' value='<?= esc_attr($team->shortN); ?>' />
						<?php submit_button('Löschen', 'primary', 'submit', false); ?>
					</form>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } ?>
	<p>
		<details>
		    <summary><strong>Anleitung:</strong> Eine Mannschaft abspeichern</summary>
		    <ol>
		        <li>Klicke hier auf <em>Mannschaft hinzufügen</em>.</li>
		        <li>Trage ein eindeutiges, maximal 8 Zeichen langes Mannschaftskürzel (z.B. <em>h1</em> für Herren
		            1 oder <em>wC2</em> für weibliche C-Jugend 2) ein. Dieses ist wichtig für die korrekte Speicherung.
		        </li>
		        <li>Trage einen maximal 16 Zeichen langen Mannschaftsnamen ein. Dieser wird bei entsprechender
		            Einstellung in manchen Widgets zu sehen sein.
		        </li>
		        <li>Ligabetrieb:
		        <li>
					Rufe die Seite
					<a href="https://handball4all.de" target="_blank">https://handball4all.de</a>
		            auf.
		        </li>
		        <li>Klicke auf den Verband/Bereich, in dem Ihre Mannschaft zu finden ist.</li>
		        <li>Für den Ligabetrieb:
		            <ol type="i">
		                <li>Klicke in der Spalte <em>Staffel</em> auf der linken Seite auf die Staffel, in der die
		                    Mannschaft zu finden ist.
		                </li>
		                <li>Klicke auf der nun angezeigten Seite auf den Mannschaftsnahmen, welcher in der Tabelle unter
		                    <em>Aktueller Tabellenstand</em> zu finden ist.
		                </li>
		            </ol>
		        </li>
		        <li>Für den Pokalbetrieb
		            <ol type="i">
		                <li>Klicke in der Spalte <em>Staffel</em> auf den entsprechenden Pokalwettbewerb.</li>
		            </ol>
		        <li>Übernehme jeweils den Link aus der Adresszeile und füge ihmn an der entsprechenden Stelle ein.</li>
		        <li>Klicke auf <em>Speichern</em>.</li>
		    </ol>
		</details>
	</p>
</div>
<hr />
<div>
	<h3>Style</h3>
	<p>
		Das Aussehen der Widgets kann mithilfe von CSS verwaltet werden.<br />
		Dieses CSS kann überall auf der Seite eingebettet werden, es wird aber empfohlen, das CSS in der dafür vorgesehenen Datei zu halten, damit die Übersichtlichkeit erhalten bleibt.<br />
		Diese Datei kann hier bearbeitet werden.
	</p>
	<details>
		<summary>Stylesheet bearbeiten</summary>
		<form method='POST' action='<?= admin_url('admin-post.php'); ?>'>
			<input type='hidden' name='action' value='gtp_style_save' />
			<?php wp_nonce_field('gtp_save_style'); ?>
			<textarea class='widefat' rows=20 name='style'><?= $style; ?></textarea>
			<p>
				<?php submit_button('Speichern', 'primary', 'save', false); ?>
				<?php submit_button('Zurücksetzen', 'small', 'reset', false); ?>
			</p>
		</form>
	</details>
</div>

<!-- script to provide functionality of the page -->
<script src='<?= plugins_url('/js/form.js', __FILE__); ?>' type='text/javascript'></script>
