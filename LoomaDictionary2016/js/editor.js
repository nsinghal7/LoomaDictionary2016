/*
 * File: editor.js
 * Author: Nikhil Singhal
 * Date: July 1, 2016
 * 
 * this is the javascript that controls the functionality of editor.html.
 * 
 * The function of this code is specific to the current setup of the html and may need to
 * be modified with it.
 * 
 * Requires that the html file have already imported jQuery, js/pdfToText.js (which requires
 * js/pdfjs/pdf.js), and js/findUniqueWords.js
 * 
 * Also relies on css classes defined in css/editor.css, and specifications on data
 * transfer format from backend.php
 * 
 * 
 */











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
 * The list of official definitions for the currently selected word
 */
var officialDefs = [];

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
 * The word currently selected and displayed in the bottom bar
 */
var selectedWord = "";

/**
 * If true, official searches and word selections should show overwritten entries, if false,
 * act as normal
 */
var showOverwritten = false;

/**
 * The id returned by setInterval while using it to update the progress bar
 */
var progressTimer;


/**
 * Keeps track of the furthest the progress of the upload has reportedly gotten since
 * some requests return too late and end up making it look like it went backwards
 */
var maxProgress;

/**
 * To be called on startup. Sets up the screen and pulls data from the backend.
 */
function startup() {
	hideUploadDiv();
	hideAddWordDiv();
	submitSearch();
	$("#cancelUploadButton").hide();
}


/**
 * Shows the div that allows users to upload PDFs, and disables the background area
 */
function showUploadDiv() {
	$("#uploadPDFDiv").show();
	$("#progressDisplay").text("");
	$("#menuArea, #viewArea, #officialViewer").addClass("disableButtons");
}


/**
 * Hides the div that allows users to upload PDFs, and enables the background area
 */
function hideUploadDiv() {
	if(!processing) {
		$("#uploadPDFDiv").hide();
		$("#menuArea, #viewArea, #officialViewer").removeClass("disableButtons");
	}
}



function changeAutoGen() {
	if($("#autoChidCheck").prop("checked")) {
		$("#chidInputLabel").text("Chapter prefix: ");
		$("#chapInput").prop("placeholder", "ex. Lesson");
	} else {
		$("#chidInputLabel").text("Page numbers: ");
		$("#chapInput").prop("placeholder", "ex. 12, 14, 17, 23");
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
	$("#uploadPDFDiv").addClass("disableButtons");
	
	// convert file to text
	var file = document.getElementById("pdfInput").files[0];
	if(file == null || !("name" in file) || !file.name.endsWith(".pdf")) {
		progress.text("No file selected or invalid format");
	}
	progress.text("Converting file to text");
	Pdf2TextClass().convertPDF(file, function(page, total) {}, function(pages) {
		// called when the pdf is fully converted to text. Finds all unique words
		progress.text("finding unique words and chapters");
		var words = findUniqueWordsFromString(pages, $("#autoChidCheck").prop("checked"),
											$("#chapInput").val(), $("#prefixInput").val());
		
		maxProgress = 0;
		// uploads the words to the backend to be added to the dictionary
		$.post("backend.php",
				{'loginInfo': {"allowed": true, 'user': 'me'},
				'wordList': JSON.stringify(words)},
				function(data, status, jqXHR) {
					// called when the post request returns (whether successful or not)
					if('status' in data && data['status']['type'] == 'error') {
						progress.text("Failed with error: " + data['status']['value']);
					} else {
						progress.text("Success!" + (data['skipped'] ? " Skipped: "
															+ data["skipped"] : ""));
						submitSearch(true);
					}
					
					$("#cancelUploadButton").hide();
					// stop updating the progress bar
					clearInterval(progressTimer);
					maxProgress = 0;
					
					// unlocks the process and reallows user submission
					$("#uploadPDFDiv").removeClass("disableButtons");
					processing = false;
					submitSearch(true);
				}, "json");
		
		// start allowing cancelation
		$("#cancelUploadButton").show();
		
		// start updating the progress bar
		progressTimer = setInterval(function() {
			$.get("backend.php",
					{'loginInfo': {"allowed": true, 'user': 'me'}, "progress": true},
					function(data, status, jqXHR) {
						var output = data['progress'];
						if(!output || output["position"] < maxProgress) {
							return;
						}
						maxProgress = output["position"];
						progress.text("Adding definition: " + output["position"] + " / " + output['length']);
					}, "json");
		}, 1000);
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
	}
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'text': prevText,
						'added': prevAdded,
						'modified': prevModified,
						'accepted': prevAccepted,
						'page': oldSearch?$("#pageInput").val():1},
			'staging': true},
			function(data, status, jqXHR) {
				// called when server responds
				//TODO handle errors after the format for error responses is determined
				
				// sets current page and maxPage so the display is consistent with the state
				data = data['data'];
				$("#pageInput").val(data['page']);
				maxPage = data['maxPage'];
				$("#maxPage").text(maxPage);
				
				// clears the table
				var table = $("#resultsTable");
				table.find("tr:gt(0)").remove();
				
				// processes each word's data
				words = data['words'].sort(function(a, b) {
					if(a["wordData"]["word"] == b["wordData"]["word"]) {
						return 0;
					} else if(a["wordData"]["word"] < b["wordData"]["word"]) {
						return -1;
					} else {
						return 1;
					}
				});
				for(var i = 0; i < words.length; i++) {
					var word = words[i];
					
					// deal with possible boolean to string conversions in JSON transfer
					["modified", "added", "accepted", "deleted"].forEach(function(field, i, a) {
						word['stagingData'][field] = isTrue(word['stagingData'][field]);
					});
					
					//creates a new row for the table and fills it with the data from the word
					var row = createTableEntry(word, i);
					
					// adds the new row to the table
					table.append(row);
				}
				// reload officialTable
				loadOfficialTable();
			}, 'json');
}


/**
 * Creates a table entry for the staging table with all necessary fields in the right format
 * @param word The word object to create it for
 * @param i The index of that word object in the stored list
 * @returns the new entry
 */
function createTableEntry(word, i) {
	var row = $('<tr>');
	
	/**
	 * Creates editable fields in the table
	 * @returns A jQuery object representing a td tag
	 */
	function createEditableTd(type, index, value) {
		return $('<td class="' + type + 'Col"></td>')
				.append($('<textarea id="' + type + "_" + index + '" onchange="edit(\'' + type
						+ '\', ' + index + ')" class="resultsTableInput">'
						+ value + "</textarea></td>"));
	}
	
	// add each field
	row.append($('<td class="selectedCol"> <button onclick="selectWord(\''
				+ words[i]['wordData']['word'] + '\')" class="'
				+ (words[i]['wordData']['word'] == selectedWord ? "" : "un")
				+ 'selectedWord" word="' + words[i]['wordData']['word']
				+ '">selected</button></td>'));
	row.append(createEditableTd("word", i, word["wordData"]["word"]));
	var stat;
	var colorClass;
	if(word['stagingData']['deleted']) {
		stat = "de<wbr>let<wbr>ed";
		row.find("td input").addClass("strikethrough"); // strike through word field
		colorClass = 'statColorDeleted';
	} else if(word['stagingData']['accepted']) {
		stat = "acc<wbr>ept<wbr>ed";
		colorClass = 'statColorAccepted';
	} else if(word['stagingData']['modified']) {
		stat = "mod<wbr>if<wbr>ied";
		colorClass = 'statColorModified';
	} else if(word['stagingData']['added']) {
		stat = "add<wbr>ed";
		colorClass = 'statColorAdded';
	} else {
		stat = "un<wbr>ed<wbr>it<wbr>ed";
		colorClass = 'statColorUnedited';
	}
	
	//adds data to the row from the word object
	row.append($('<td class="statCol"><button onclick="edit(\'stat\', '
				+ i + ')" id="stat_' + i + '" class="statButton ' + colorClass + '">' + stat
				+ '</button><button class="cancelButton" onclick="edit(\'cancel\', ' + i
				+ ')">re<wbr>vert</button><button onclick="edit(\'delete\', '
				+ i + ')" class="entryDeleteButton">'
				+ (word['stagingData']['deleted']?'re add':'de<wbr>lete')+'</button></td>'));
	row.append(createEditableTd("root", i, word["wordData"]["root"] || ""));
	row.append(createEditableTd("pos", i, word["wordData"]["pos"]));
	row.append(createEditableTd("nep", i, word["wordData"]["nep"]));
	row.append(createEditableTd("def", i, word["wordData"]["def"]));
	row.append(createEditableTd("ch_id", i, word["wordData"]["ch_id"]));
	row.append($('<td class="primCol"><input type="checkbox" onchange="edit(\'prim\', ' + i +
						')" id="prim_' + i + '" '
						+ (isTrue(word["wordData"]["primary"]) ? 'checked ' : '') + '></td>'));
	row.append($('<td class="modCol"><p>'
			+ (word['wordData']["mod"]) + '</p></td>'));
	row.append($('<td class="dateCol"><p>'
			+ (word['wordData']["date"]) + '</p></td>'));
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
 * @param type The column of the cell changed. For statCol, also allows 'delete' and 'cancel'
 * @param index The row of the cell changed (corresponds to the index in the word list
 */
function edit(type, index) {
	
	// disable all of screen until the response so that there won't be any collisions
	$("#menuArea, #viewArea, #officialViewer").addClass("disableButtons");
	
	// get correct id to get statCol button element (delete and cancel don't have ids)
	var id_type = (type == 'cancel' || type == 'delete') ? 'stat' : type;
	// get correct element
	var elem = $("#" + id_type + "_" + index);
	// confirm a cancel, which takes immediate effect in removing a definition from staging
	if(type == 'cancel' && !confirm(
			"Are you sure you want to revert all unpublished changes to this entry?")) {
		$("#menuArea, #viewArea, #officialViewer").removeClass("disableButtons");
		return;
	}
	
	// if published, stat button should have no effect (can't be accepted without a change)
	if(type == 'stat' && elem.text() == 'unedited') {
		$("#menuArea, #viewArea, #officialViewer").removeClass("disableButtons");
		return;
	}
	
	// request change
	$.post('backend.php', {'loginInfo': {'allowed': true, 'user': 'me'},
							'mod': {'wordId': words[index]['wordData']['id'],
								'field': type, 'new': elem.val(),
								'deleteToggled': (words[index]['stagingData']['deleted']
											&& type == "stat") || type == 'delete'}},
			function(data, status, jqXHR) {
				// called on server response
				if(data['status']['type'] == 'success') {
					// don't alert user, since success is assumed, and keep server's change
					words[index] = data['new'];
					if(words[index] == true) {
						// the change was a removal (cancel), so reload the page
						submitSearch(true);
					} else {
						// standard edit.
						elem.parent().parent().replaceWith(createTableEntry(words[index],
								index));
					}
				} else {
					// alert user of failure and revert change
					elem.parent().parent().replaceWith(createTableEntry(words[index], index));
					alert("The change you made to the word "
							+ words[index]['wordData']['word'] + " ("
							+ words[index]['wordData']['pos'] + ", "
							+ words[index]['wordData']['id'] + ") failed and were reverted");
				}
				
				// unlock screen so the user can continue
				$("#menuArea, #viewArea, #officialViewer").removeClass("disableButtons");
			}, 'json');
}

/**
 * Called when a word is selected in the staging table and should be displayed in the official
 * table
 * @param word The word selected as a string
 */
function selectWord(word) {
	selectedWord = word;
	// select correct words
	$(".selectedWord").addClass("unselectedWord").removeClass("selectedWord");
	$(".unselectedWord[word='" + selectedWord + "']").addClass("selectedWord")
										.removeClass("unselectedWord");
	
	loadOfficialTable();
}

/**
 * Submits a search for an official (published) word
 */
function submitOfficialSearch() {
	selectWord($("#officialSearchBox").val());
	loadOfficialTable();
}

/**
 * loads the official table using the selected word (global variable)
 */
function loadOfficialTable() {
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'word': selectedWord, 'overwritten': showOverwritten},
			'staging': false},
			function(data, status, jqXHR) {
				if(data != null) {
					officialDefs = data['data'];
					function createOfficialTd(word, field) {
						return $("<td class='" + field + "Col'> <p>"
									+ (word['wordData'][field] || "") + "</p></td>");
					}
					var table = $("#officialTable");
					table.find("tr:gt(0)").remove();
					for(var i = 0; i < officialDefs.length; i++) {
						var row = $("<tr>");
						row.append($("<td class='editCol'><button id='edit_" + i
								+ "' onclick='moveOfficial(" + i + ");'>Edit</button></td>"));
						row.append(createOfficialTd(officialDefs[i], "word"));
						row.append($("<td class='statCol'><p>unedited</p></td>"));
						row.append(createOfficialTd(officialDefs[i], "root"));
						row.append(createOfficialTd(officialDefs[i], "pos"));
						row.append(createOfficialTd(officialDefs[i], "nep"));
						row.append(createOfficialTd(officialDefs[i], "def"));
						row.append(createOfficialTd(officialDefs[i], "ch_id"));
						row.append(createOfficialTd(officialDefs[i], "primary"));
						row.append(createOfficialTd(officialDefs[i], "mod"));
						row.append(createOfficialTd(officialDefs[i], "date"));
					}
					
					// update margin-bottom of resultsTable
					$("#viewArea").css("margin-bottom",
							$("#officialViewer").height() + "px");
				} else {
					alert("loading from official database failed");
				}
			}, 'json');
}

/**
 * moves the word at the given index in the officialDefs array to the staging dictionary
 * with no other changes
 * @param index The index of the word to move
 */
function moveOfficial(index) {
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'moveId': officialDefs[index]['wordData']['id']},
		function(data, status, jqXHR) {
			if(data['status']['type'] == 'success') {
				// reload page, don't notify, since success is expected
				submitSearch(true);
			} else {
				// notify that it failed and leave page
				alert("moving word to staging failed");
			}
		}, 'json');
}

/**
 * Toggles the show/hide overwritten button and resubmits the table with the new setting
 */
function toggleOverwrite() {
	function getClass() {
		return showOverwritten ? "toggledOn" : "toggledOff";
	}
	$("#showOverwriteButton").removeClass(getClass());
	showOverwritten = !showOverwritten;
	$("#showOverwriteButton").addClass(getClass());
	loadOfficialTable();
}

/**
 * Checks if the input is true or "true"
 * @param input The input to check
 */
function isTrue(input) {
	return input === true || input == "true";
}

/**
 * Checks to confirm the user's intent, and then removes all entries from the staging database
 */
function revertStaging() {
	if(confirm("Are you sure you want to revert all staging changes? This cannot be undone.")) {
		$.get("backend.php", {'loginInfo': {"allowed": true, 'user': 'me'}, 'revertAll': true},
			function(data, status, jqXHR) {
				submitSearch(true);
			}, 'json');
	}
}


/**
 * Shows the add word div and hides everything else
 */
function showAddWordDiv() {
	$("#addWordDiv").show();
	$("#menuArea, #viewArea, #officialViewer").addClass("disableButtons");
}

/**
 * Hides the add word div and shows everything else
 */
function hideAddWordDiv() {
	$("#addWordDiv").hide();
	$("#menuArea, #viewArea, #officialViewer").removeClass("disableButtons");
}

/**
 * Adds a single word to the staging dictionary with only the english word inputted.
 * Nothing is autogenerated. Then searches for this exact word by id
 */
function addSingleWord() {
	var word = $("#newWordInput").val().toLowerCase();
	$("#addWordDiv").addClass("disableButtons");
	$.get("backend.php", {'loginInfo': {"allowed": true, 'user': 'me'}, 'newWord': word},
		function(data, status, jqXHR) {
			if(data && data['status'] && data['status']['type'] == 'success') {
				$("#newWordInput").val("");
				$("#wordPart").val(word);
				$("#added").prop("checked", false);
				$("#modified").prop("checked", false);
				$("#accepted").prop("checked", false);
				submitSearch();
				hideAddWordDiv();
			} else {
				alert("failed to add word");
			}
			$("#addWordDiv").removeClass("disableButtons");
		}, 'json');
}

/**
 * Cancels the current upload
 */
function cancelUpload() {
	$.get("backend.php", {'loginInfo': {"allowed": true, 'user': 'me'}, "cancelUpload": true},
		function(data, status, jqXHR) {
			// don't do anything. it will continue to run until it skips all entries. then
			// the normal processPDF() handler will finish it
		});
}

