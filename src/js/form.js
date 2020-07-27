/**
 * This script holds functions that are needed on the options page.
 */

/**
 * Click listener for the details#team-editor.
 * This catches all clicks on the 'Edit'-Buttons within it and handles them
 * by filling and showing the form meant to edit the corressponding team.
 */
document.querySelector('#team-editor').addEventListener('click', function editTeam(click) {
	let team = event.target.dataset.team;
    if(typeof team === 'undefined'){
        return;
    }

	// hide all teamforms to make sure only one is displayed at a time
	let forms = document.getElementsByClassName('team-form');
	for(form of forms){
		form.style.display = 'none';
	}

	let teamForm = document.getElementById('team-form-' + team);
    // display the requested form
    teamForm.style.display = 'initial';

	// put focus on first field
	teamForm.elements['short'].focus();
});
