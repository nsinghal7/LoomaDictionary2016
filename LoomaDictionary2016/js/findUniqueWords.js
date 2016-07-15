/** takes a string with the entire block of text, then returns an array of all the uniqu words*/
function findUniqueWordsFromString(pages, isChPre, helpString, prefix){
	pages = pages.map(extractWordsFromString); // string[][]
	var words = [];
	if(helpString == "") {
		// no chapters
		for(var i = 0; i < pages.length; i++) {
			for(var j = 0; j < pages[i].length; j++) {
				words.push({"word": pages[i][j], "ch_id": prefix});
			}
		}
	} else if(isChPre) {
		helpString = helpString.trim().toLowerCase();
		var chapter = 0;
		var lastWasPrefix = false;
		var contents = true;
		
		for(var i = 0; i < pages.length; i++) {
			words = words.concat(pages[i]);
		}
		for(var i = 0; i < words.length; i++) {
			if(lastWasPrefix) {
				if(parseInt(words[i]) == chapter + 1) {
					chapter++;
				} else if(parseInt(words[i]) == 1){
					// exit contents section
					chapter = 1;
					contents = false;
				}
			}
			lastWasPrefix = (words[i] == helpString);
			words[i] = {"word": words[i], "ch_id": prefix + (contents ? "00" : (chapter < 10 ? "0" : "") + chapter)};
		}
	} else {
		var chapter = 0;
		var pageNums = helpString.split(",").map(function(num) {
			return parseInt(num.trim());
		});
		var pageIndex = 0;
		for(var i = 0; i < pages.length; i++) {
			if(pageNums[pageIndex] == i + 1) {
				chapter++;
				pageIndex++;
			}
			for(var j = 0; j < pages[i].length; j++) {
				words.push({"word": pages[i][j], "ch_id": prefix + (chapter < 10 ? "0" : "") + chapter});
			}
		}
	}
	var sorted = words.sort(function(a, b) {
		if(a.word == b.word) {
			if(a.ch_id <= b.ch_id) {
				return -1;
			} else {
				return 1;
			}
		} else if(a.word < b.word) {
			return -1;
		} else {
			return 1;
		}
	});
	return sorted.filter( function(v,i,o){return !/^(0|[1-9]\d*)$/.test(v.word) && (i==0 || v.word!=o[i-1].word);});
}

function extractWordsFromString(string) {
	return (string.match(/[qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789]+/g) || [])
						.map(function(word) { return word.toLowerCase()});
}
