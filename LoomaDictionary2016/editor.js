var processing = false;
var maxPage = 1;
var words = [];

function startup() {
	hideUploadDiv();
	submitSearch();
}

function showUploadDiv() {
	$("#uploadPDFDiv").show();
	$("#progressDisplay").text("");
	$("#menuArea, #viewArea").addClass("disableButtons");
}

function hideUploadDiv() {
	if(!processing) {
		$("#uploadPDFDiv").hide();
		$("#menuArea, #viewArea").removeClass("disableButtons");
	}
}

function processPDF() {
	processing = true;
	var progress = $("#progressDisplay");
	$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", true);
	var file = document.getElementById("pdfInput").files[0];
	if(file == null || !("name" in file) || !file.name.endsWith(".pdf")) {
		progress.text("No file selected or invalid format");
	}
	progress.text("Converting file to text");
	Pdf2TextClass().convertPDF(file, function(page, total) {}, function(text) {
		progress.text("Processing text");
		var words = findUniqueWordsFromString(text);
		$.post("backend.php",
				{'loginInfo': {"allowed": true, 'user': 'me'},
				'wordList': JSON.stringify(words)},
				function(data, status, jqXHR) {
					if('status' in data && data['status']['type'] == 'error') {
						progress.text("Failed with error: " + data['status']['value']);
					} else {
						progress.text("Success!");
					}
					$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", false);
					processing = false;
					submitSearch();
				}, "json");
	});
}

function submitSearch() {
	$.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'text': $("#wordPart").val(),
						'added': $("#added").prop("checked"),
						'modified': $("#modified").prop("checked"),
						'accepted': $("#accepted").prop("checked")},
			'simplified': $("#simplified").prop("checked")},
			function(data, status, jqXHR) {
				data = data['data'];
				$("#pageInput").val(data['page']);
				maxPage = data['maxPage'];
				
				var table = $("#resultsTable");
				table.find("tr:gt(0)").remove();
				
				words = data['words'];
				for(var i = 0; i < words.length; i++) {
					var word = words[i];
					var row = $('<tr>');
					
					function createEditableTd(type, index, value) {
						return $('<td class="' + type + 'Col"></td>')
								.append($('<input type="text" id="'
										+ type + "," + index + '" onchange="edit(\'' + type
										+ '\', ' + index + ')" value="' + value
										+ '" class="resultsTableInput">'));
					}
					
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
					row.append($('<td class="statCol">' + stat + '</td>'));
					row.append(createEditableTd("root", i, word["wordData"]["root"]));
					row.append(createEditableTd("pos", i, word["wordData"]["pos"]));
					row.append(createEditableTd("nep", i, word["wordData"]["nep"]));
					row.append(createEditableTd("def", i, word["wordData"]["def"]));
					row.append(createEditableTd("mod", i, word["wordData"]["mod"]));
					row.append(createEditableTd("date", i, word["wordData"]["date"]));
					row.append(createEditableTd("other", i, word["wordData"]["other"]));
					table.append(row);
				}
			}, 'json');
}

function publish() {
	if(confirm("Are you sure you want to publish these changes?")) {
		$.get("backend.php", {'loginInfo': {'allowed': true, 'user': 'me'}, 'publish': true},
				function(data, status, jqXHR) {
					if(data['status']['type'] == 'success') {
						alert("published successfully");
					} else {
						alert("publishing failed");
					}
				}, 'json');
	}
}


function switchPage(change) {
	var elem = $("#pageInput");
	var val = elem.val();
	val += change < 0 ? (change == -1 ? -val : -1) : (change == 1 ? maxPage - val : 1);
	val = Math.max(0, Math.min(maxPage, val));
	elem.val(val);
	pageChange();
}

function pageChange() {
	// load new data
	console.log("page changed to " + $("#pageInput").val());
}

function edit(type, index) {
	console.log("change");
	// send changes. if doesn't work, replace modified text with original, and warn the user
	// if works, change the words list officially
}