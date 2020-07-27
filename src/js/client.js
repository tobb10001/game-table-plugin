/**
 * This function shows a message to the visitor to remind him that the page
 * isn't on time anymore.
 * Before that it checks whether live games are displayed on the page.
 * It gets triggered on DOMContentLoaded.
 */
function reloadReminder(){
	if(document.querySelector('.game-table-widget-games.has-live') === null){
		return;
	}
	// there is at least one widget holding at leas one game that is live.

	// create a message to display
	let message = "<p>Jetzt aktualisieren, um aktuelle Spielstände zu bekommen! <br /><button type='button' onclick='location.reload();' class='button'>Aktualisieren!</button></p>";

	// find all of them and display a message to refresh the page
	[...document.querySelectorAll('.game-table-widget-games.has-live')].forEach(function(widget){
		setTimeout(function(){
			widget.insertAdjacentHTML('afterbegin', message);
		}, 5 * 60 * 1000); /* 5 minutes in milliseconds */
	});
}
document.addEventListener('DOMContentLoaded', reloadReminder);
