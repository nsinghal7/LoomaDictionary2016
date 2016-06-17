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
	var progress = $("#progressDisplay");
	$("#uploadPDFDiv").find(".closePopupButton").prop("disabled", true);
	var file = document.getElementById("pdfInput").files[0];
	if(file == null || !("name" in file) || !file.name.endsWith(".pdf")) {
		progress.text("No file selected or invalid format");
	}
	Pdf2TextClass().convertPDF(file, function(page, total) {
		if(page % 5 == 0 || page == total) {
			progress.text("Converting page " + page + " / " + total);
		}
	}, function(text) {
		progress.text("Processing text");
		var words = findUniqueWordsFromString(text);
		for(var i = 0; i < words.length; i++) {
			console.log(i)
			if(i % 100 == 0 || i == words.length - 1) {
				progress.text("Creating definitions for word " + i + " / " + words.length);
			}
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