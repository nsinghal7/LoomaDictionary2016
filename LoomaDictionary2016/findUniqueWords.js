/** takes a string with the entire block of text, then returns an array of all the uniqu words*/
function findUniqueWordsFromString(originalString){
	var arrayOfSortedStrings = extractWordsFromString(originalString).sort();
	filteredArray = arrayOfSortedStrings.filter( function(v,i,o){return v!==o[i-1];});
	return filteredArray;
}

function extractWordsFromString(string) {
	return string.match(/[qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM]+/g);
}

debug(findUniqueWordsFromString("hello hello askdfjhaskdlf asdfkjasdf asf3435 (*S&D(*7ads(D*&f9s8"))