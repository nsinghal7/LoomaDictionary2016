var processing = false;

function startup() {
	hideUploadDiv();
	var search = window.location.search;
	if(search.length > 0) {
		var pairs = search.substring(1).split("&");
		for(var i = 0; i < pairs.length; i++) {
			var pair = pairs[i];
			var kv = pair.split("=");
			if(kv[0] == "wordPart") {
				$("#wordPart").val(kv[1]);
			} else {
				$("#" + kv[0]).prop("checked", true);
			}
		}
	}
}

function showUploadDiv() {
	$("#uploadPDFDiv").show();
}

function hideUploadDiv() {
	if(!processing) {
		$("#uploadPDFDiv").hide();
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
		for(var i = 0; i < words.length; i++) {
			findAndAddDefinitions(words[i]);
		}
		progress.text("Success!");
		setTimeout(function() {
			$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", false);
			processing = false;
			hideUploadDiv();
			progress.text("");
		}, 1000)
	});
}

function findAndAddDefinitions(word) {
	
}