/**
 * True if a pdf is being processed and other changes should be prevented, false for normal
 */
var processing = false;

/**
 * the list of words in BOTH tables
 */
var words = [];

/**
 * Notes the text specified by the last search so the search can be repeated if necessary
 */
var prevText = "";

/**
 * Notes whether the last search specified 'added' so the search can be repeated
 */
var prevAdded = false;

/**
 * Notes whether the last search specified 'modified' so the search can be repeated
 */
var prevModified = false;

/**
 * Notes whether the last search specified 'accepted' so the search can be repeated
 */
var prevAccepted = false;

/**
 * Notes whether the last search specified 'simplified' so the search can be repeated
 */
var prevSimplified = false;


/**
 * To be called on startup. Sets up the screen and pulls data from the backend.
 */
function startup() {
	hideUploadDiv();
	submitSearch();
}


/**
 * Shows the div that allows users to upload PDFs, and disables the background area
 */
function showUploadDiv() {
	$("#uploadPDFDiv").show();
	$("#progressDisplay").text("");
	$("#menuArea, #viewArea").addClass("disableButtons");
}


/**
 * Hides the div that allows users to upload PDFs, and enables the background area
 */
function hideUploadDiv() {
	if(!processing) {
		$("#uploadPDFDiv").hide();
		$("#menuArea, #viewArea").removeClass("disableButtons");
	}
}


/**
 * processes the PDF selected in the uploadPDFDiv, finding all unique words and sending
 * them to the server to be processed and added to the dictionary. Currently does not
 * handle skipped words. After adding to the dictionary, this function reloads the data table
 * on the current page to get new results.
 */
function processPDF() {
	// lock process and prevent user resubmission
	processing = true;
	var progress = $("#progressDisplay");
	$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", true);
	$("#processPDFButton").prop("disabled", true);
	
	// convert file to text
	var file = document.getElementById("pdfInput").files[0];
	if(file == null || !("name" in file) || !file.name.endsWith(".pdf")) {
		progress.text("No file selected or invalid format");
	}
	progress.text("Converting file to text");
	Pdf2TextClass().convertPDF(file, function(page, total) {}, function(text) {
		// called when the pdf is fully converted to text. Finds all unique words
		progress.text("Processing text");
		var words = findUniqueWordsFromString(text);
		
		// uploads the words to the backend to be added to the dictionary
		$.post("backend.php",
				{'loginInfo': {"allowed": true, 'user': 'me'},
				'wordList': JSON.stringify(words)},
				function(data, status, jqXHR) {
					// called when the post request returns (whether successful or not)
					if('status' in data && data['status']['type'] == 'error') {
						progress.text("Failed with error: " + data['status']['value']);
					} else {
						progress.text("Success!");
						submitSearch(true);
					}
					
					// unlocks the process and reallows user submission
					$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", false);
					$("#processPDFButton").prop("disabled", false);
					processing = false;
					
					// reload with in case new data affected current search
					submitSearch(true);
				}, "json");
	});
}


function parseBoolean(input) {
	return input == true || input == 'true';
}

/**
 * Searches the database for all entries matching the parameters of text, added, modified, etc.
 * then formats these results and adds them into the results table.
 * @param oldSearch If true, searches using the previously SUBMITTED arguments, not those
 * currently displayed. defaults to false;
 */
function submitSearch(oldSearch) {
	// send request to server
	if(!oldSearch) {
		prevText = $("#wordPart").val();
		prevAdded = $("#added").prop("checked")
		prevModified = $("#modified").prop("checked");
		prevAccepted = $("#accepted").prop("checked");
	}
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'text': prevText,
						'added': prevAdded,
						'modified': prevModified,
						'accepted': prevAccepted,
						'page': oldSearch?$("#pageInput").val():1}},
			function(input, status, jqXHR) {
				// called when server responds
				// handle errors after the format for error responses is determined
				if(input == null) {
					alert("Search failed");
					return;
				}
				
				var data = input['data'];
				
				// clears the tables
				var newTable = $("#newTable");
				var oldTable = $("#oldTable");
				newTable.find("tr:gt(0)").remove();
				oldTable.find("tr:gt(0)").remove();
				
				// processes each word's data, copies it into global variable
				words = data['words'];
				for(var i = 0; i < words.length; i++) {
					// ensure that booleans were not converted to strings in json formatting
					var staging = words[i]['stagingData'];
					staging['modified'] = parseBoolean(staging['modified']);
					staging['added'] = parseBoolean(staging['added']);
					staging['accepted'] = parseBoolean(staging['accepted']);
					staging['deleted'] = parseBoolean(staging['deleted']);
					
					
					// creates a new entry and adds the new row to the appropriate table
					(isNewWord(words[i]) ? newTable : oldTable)
							.append(createTableEntry(words[i], i));
				}
			}, 'json');
}


/**
 * Creates a <tr> in jquery that is in the correct format for the results tables (#new/oldTable)
 * @param word The word to create it for
 * @param i The index of the word in its array
 * @returns The <tr>
 */
function createTableEntry(word, i) {
	var row = $('<tr>');
	
	/**
	 * Creates editable fields in the table
	 * @returns A jQuery object representing a td tag
	 */
	function createEditableTd(type, index, value) {
		return $('<td class="' + type + 'Col"></td>')
				.append($('<input type="text" id="'
						+ type + "_" + index + '" onchange="edit(\'' + type
						+ '\', ' + index + ')" value="' + value
						+ '" class="resultsTableInput">'));
	}
	
	// add each field
	row.append(createEditableTd("word", i, word["wordData"]["word"]));
	var stat;
	if(word['stagingData']['deleted']) {
		stat = "de<wbr>let<wbr>ed";
		row.find("td input").addClass("strikethrough");
	} else if(word['stagingData']['accepted']) {
		stat = "acc<wbr>ept<wbr>ed";
	} else if(word['stagingData']['modified']) {
		stat = "mod<wbr>if<wbr>ied";
	} else if(word['stagingData']['added']) {
		stat = "add<wbr>ed";
	} else {
		stat = "un<wbr>ed<wbr>it<wbr>ed";
	}
	
	//adds data to the row from the word object
	row.append($('<td class="statCol"><button onclick="edit(\'stat\', '
				+ i + ')" id="stat_' + i + '" class="statButton">' + stat
				+ '</button><button class="cancelButton" onclick="edit(\'cancel\', ' + i
				+ ')">re<wbr>vert</button><button onclick="edit(\'delete\', '
				+ i + ')" class="entryDeleteButton">'
				+ (word['stagingData']['deleted']?'re add':'de<wbr>lete')+'</button></td>'));
	row.append(createEditableTd("root", i, word["wordData"]["root"] || ""));
	row.append(createEditableTd("pos", i, word["wordData"]["pos"]));
	row.append(createEditableTd("nep", i, word["wordData"]["nep"]));
	row.append(createEditableTd("def", i, word["wordData"]["def"]));
	row.append($('<td class="modCol"><p>'
			+ (word['wordData']["mod"]) + '</p></td>'));
	row.append($('<td class="dateCol"><p>'
			+ (word['wordData']["date"]) + '</p></td>'));
	row.append($('<td class="otherCol"><p>'
			+ (word['wordData']["other"] || "") + '</p></td>'));
	return row;
}


/**
 * Asks the user for specific permission and then publishes accepted changes to the official
 * database
 */
function publish() {
	if(confirm("Are you sure you want to publish these changes?")) {
		// tells server to publish
		$.get("backend.php", {'loginInfo': {'allowed': true, 'user': 'me'}, 'publish': true},
				function(data, status, jqXHR) {
					// called on server response, alerts the user to the success or failure
					// TODO replace alert with a less intrusive notification
					if(data['status']['type'] == 'success') {
						alert("published successfully");
						//since this causes lots of changes, just reload the table
						submitSearch(true);
					} else {
						alert("publishing failed");
					}
				}, 'json');
	}
}


/**
 * Checks if a word belongs in newTable
 * @param word The word to check
 * @returns true if new, false otherwise
 */
function isNewWord(word) {
	return (word['stagingData']["added"] || word['stagingData']['modified']
					|| word['stagingData']['accepted'] || word['stagingData']['deleted']);
}

/**
 * Called when an editable cell in the table is changed and should be transmitted to the server
 * @param type The column of the cell changed. For statCol, also allows 'delete' and 'cancel'
 * @param index The row of the cell changed (corresponds to the index in the word list
 * @param newTable True if the word is in the newtable, false if in the old.
 */
function edit(type, index, newTable) {
	// get correct element
	var id_type = (type == 'cancel' || type == 'delete') ? 'stat' : type;
	var elem = $("#" + id_type + "_" + index);
	// confirm a cancel, which takes immediate effect in removing a definition from staging
	if(type == 'cancel' && !confirm(
			"Are you sure you want to revert all unpublished changes to this entry?")) {
		// didn't mean to change
		return;
	}
	// disable all of screen until the response so that there won't be any collisions
	$("#menuArea, #viewArea").addClass("disableButtons");
	
	// request change
	$.post('backend.php', {'loginInfo': {'allowed': true, 'user': 'me'},
							'mod': {'wordId': words[index]['wordData']['id'],
								'field': type, 'new': elem.val(),
								'deleteToggled': (words[index]['stagingData']['deleted']
											&& type == "stat") || type == 'delete'}},
			function(data, status, jqXHR) {
				// called on server response
				if(data['status']['type'] == 'success') {
					// don't alert user, since success is assumed, and reload data
					submitSearch(true);
				} else {
					// alert user of failure and revert change
					elem.parent().parent().replaceWith(createTableEntry(words[index], index));
					alert("The change you made to the word "
							+ words[index]['wordData']['word'] + " ("
							+ words[index]['wordData']['pos'] + ", "
							+ words[index]['wordData']['id'] + ") failed and were reverted");
				}
				
				// unlock screen so the user can continue
				$("#menuArea, #viewArea").removeClass("disableButtons");
			}, 'json');
}