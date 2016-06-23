/**
 * True if a pdf is being processed and other changes should be prevented, false for normal
 */
var processing = false;

/**
 * The max page number for the current search results. Should be updated with each query.
 */
var maxPage = 1;

/**
 * The list of words from the current page of the current query. Should always be updated
 * after successful modifications to the database's representation of the data, but stay
 * the same until then so that, if the update fails, the user's view can be restored to the
 * version on the cloud without having to reload the page
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
					}
					
					// unlocks the process and reallows user submission
					$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", false);
					$("#processPDFButton").prop("disabled", false);
					processing = false;
					submitSearch(true);
				}, "json");
	});
}

/**
 * Searches the database for all entries matching the parameters of text, added, modified, etc.
 * then formats these results and adds them into the results table.
 * @param oldSearch If true, searches for the results using the previously SUBMITTED
 * (by the user or another function where oldSearch is set to false) search
 * on the current page, rather than the new search on page 1. Defaults to false, and therefore
 * searches for the parameters currently on screen with page=1 and updates the previous
 * values
 */
function submitSearch(oldSearch) {
	// send request to server
	if(!oldSearch) {
		prevText = $("#wordPart").val();
		prevAdded = $("#added").prop("checked")
		prevModified = $("#modified").prop("checked");
		prevAccepted = $("#accepted").prop("checked");
		prevSimplified = $("#simplified").prop("checked");
	}
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'text': prevText,
						'added': prevAdded,
						'modified': prevModified,
						'accepted': prevAccepted,
						'page': oldSearch?$("#pageInput").val():1},
			'simplified': prevSimplified},
			function(data, status, jqXHR) {
				// called when server responds
				//TODO handle errors after the format for error responses is determined
				
				// sets current page and maxPage so the display is consistent with the state
				data = data['data'];
				$("#pageInput").val(data['page']);
				maxPage = data['maxPage'];
				
				// clears the table
				var table = $("#resultsTable");
				table.find("tr:gt(0)").remove();
				
				// processes each word's data
				words = data['words'];
				for(var i = 0; i < words.length; i++) {
					var word = words[i];
					
					//creates a new row for the table and fills it with the data from the word
					var row = createTableEntry(word, i);
					
					// adds the new row to the table
					table.append(row);
				}
			}, 'json');
}


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
	if(word['metaData']['deleted']) {
		stat = "deleted";
	} else if(word['metaData']['accepted']) {
		stat = "accepted";
	} else if(word['metaData']['modified']) {
		stat = "modified";
	} else if(word['metaData']['added']) {
		stat = "added";
	} else {
		stat = "published";
	}
	
	//adds data to the row from the word object
	row.append($('<td class="statCol"><button onclick="edit(\'stat\', '
				+ i + ', true)" id="stat_' + i + '" class="statButton">' + stat
				+ '</button><button onclick="edit(\'stat\', '
				+ i + ', false)" class="entryDeleteButton">'
				+ (word['metaData']['deleted']?'+':'X')+'</button></td>'));
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
 * Allows the buttons to the side of the page number to change the page number by signalling
 * this method. It then reloads the table on the new page if the page changed. 
 * @param change Specifies the change requested:
 * 	-2: go to page 1
 * 	-1: go back 1 page
 * 	1: go forward 1 page
 * 	2: go to last page
 * 	any other value: stay on same page
 */
function switchPage(change) {
	var elem = $("#pageInput");
	var val = elem.val();
	var prev = val;
	if(change == -2) {
		val = 1;
	} else if(change == -1) {
		val--;
	} else if(change == 1) {
		val++;
	} else if(change == 2) {
		val = maxPage;
	}
	val = Math.max(1, Math.min(maxPage, val));
	elem.val(val);
	if(val != prev) {
		pageChange();
	}
}

/**
 * Reloads the table on the new page
 */
function pageChange() {
	submitSearch(true);
}

/**
 * Called when an editable cell in the table is changed and should be transmitted to the server
 * @param type The column of the cell changed
 * @param index The row of the cell changed (corresponds to the index in the word list
 * @param spec Indicates more specifications. So far should only be used for the statCol
 * buttons, where true means the accepted/normal button was toggled, and false means the
 * delete button was toggled. Defaults to undefined.
 */
function edit(type, index, spec) {
	console.log("change " + words[index]['metaData']['deleted']);
	var elem = $("#" + type + "_" + index);
	$.post('backend.php', {'loginInfo': {'allowed': true, 'user': 'me'},
							'mod': {'wordId': words[index]['wordData']['id'],
								'field': type, 'new': elem.val(),
								'deleteToggled': (words[index]['metaData']['deleted']
											&& type == "stat") || spec == false}},
			function(data, status, jqXHR) {
				// called on server response
				if(data['status']['type'] == 'success') {
					// don't alert user, since success is assumed, and keep server's change
					words[index] = data['new'];
					console.log(words[index]);
					elem.parent().parent().replaceWith(createTableEntry(words[index], index));
				} else {
					// alert user of failure and revert change
					elem.parent().parent().replaceWith(createTableEntry(words[index], index));
					alert("The change you made to the word "
							+ words[index]['wordData']['word'] + " ("
							+ words[index]['wordData']['pos'] + ", "
							+ words[index]['wordData']['id'] + ") failed and were reverted");
				}
			}, 'json');
}