var processing = false;

function startup() {
	hideUploadDiv();
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
	$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", true);
	var file = document.getElementById("pdfInput").files[0];
	if(file == null || !("name" in file) || !file.name.endsWith(".pdf")) {
		$("#progressDisplay").text("No file selected or invalid format");
	}
	Pdf2TextClass().convertPDF(file, function(page, total) {
		$("#progressDisplay").text("Converting page " + page + " / " + total);
	}, function(text) {
		$("#progressDisplay").text("Processing text");
		var words = findUniqueWordsFromString(text);
		for(var i = 0; i < words.length; i++) {
			$("#progressDisplay").text("Creating definitions for word " + i + " / " + words.length);
			findAndAddDefinitions(words[i]);
		}
		$("#progressDisplay").text("Success!");
		setTimeout(function() {
			$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", false);
			processing = false;
			hideUploadDiv();
			$("#progressDisplay").text("");
		}, 1000)
	});
}

function findAndAddDefinitions(word) {
	
}

function findUniqueWordsFromString(text) {
	return ["test"];
}