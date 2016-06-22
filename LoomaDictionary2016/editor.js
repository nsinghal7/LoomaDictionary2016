var processing = false;
var maxPage = 1;

function startup() {
	hideUploadDiv();
}
startup();

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
		jQuery.post("backend.php",
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
				}, "json");
	});
}

function submitSearch() {
	$("#searchButton").prop("disabled", true);
	jQuery.get("backend.php",
			{'loginInfo': {"allowed": true, 'user': 'me'},
			'searchArgs': {'text': $("#wordPart").val(),
						'added': $("#added").prop("checked"),
						'modified': $("#modified").prop("checked"),
						'accepted': $("#accepted").prop("checked")},
			'simplified': $("#simplified").prop("checked")},
			function(data, status, jqXHR) {
				// show data;
				// change maxPage
				console.log(data);
				$("#searchButton").prop("disabled", false);
			}, 'json');
}

function publish() {
	jQuery.get("backend.php", {'loginInfo': {'allowed': true, 'user': 'me'}, 'publish': true},
		function(data, status, jqXHR) {
			if(data['status']['type'] == 'success') {
				console.log('success, published');
			} else {
				console.log('fail to publish');
			}
		}, 'json');
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
	console.log("page changed to " + $("#pageInput").val());
}