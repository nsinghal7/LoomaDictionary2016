/** takes a string with the entire block of text, then returns an array of all the uniqu words*/
function findUniqueWordsFromString(pages, isChPre, helpString, prefix){
	pages = pages.map(extractWordsFromString); // string[][]
	var words = [];
	if(isChPre) {
		helpString = helpString.trim();
		var chapter = 0;
		var lastWasPrefix = false;
		var contents = true;
		
		for(var i = 0; i < pages.length; i++) {
			words += pages[i];
		}
		
		for(var i = 0; i < words.length; i++) {
			if(lastWasPrefix && parseInt(words[i]) == chapter) {
				chapter++;
			}
			lastWasPrefix = (words[i] == helpString);
			words[i] = {"word": words[i], "ch_id": prefix + (contents ? 0 : chapter)};
		}
	} else {
		var chapter = 0;
		var pageNums = helpString.split(",").map(function(num) {
			return parseInt(num.trim());
		});
		var pageIndex = 0;
		for(var i = 0; i < pages.length; i++) {
			if(pageNums[pageIndex] == i) {
				chapter++;
				pageIndex++;
			}
			for(var j = 0; j < pages[i].length; j++) {
				words.push({"word": pages[i][j], "ch_id": prefix + chapter});
			}
		}
	}
	filteredArray = words.sort().filter( function(v,i,o){return v.word!=o[i-1].word;});
	return filteredArray;
}

function extractWordsFromString(string) {
	return string.match(/[qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789]+/g)
				.map(function(word) { return word.toLowreCase()});
}
