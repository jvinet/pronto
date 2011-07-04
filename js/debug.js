$(function(){
	$('#pronto_debug_bar').toggle(function(){
		$('#pronto_debug').animate({
			height: '80%'
		}, 200, 'linear', function(){ $('#pronto_debug_bar').html('CLOSE') });
	}, function(){
		$('#pronto_debug').animate({
			height: '0px'
		}, 200, 'linear', function(){ $('#pronto_debug_bar').html('DEBUG') });
	});
});

/**
* Dump the contents of a JavaScript variable.
* @param mixed arr The array/object/variable
* @param level int If set, only recurse to this depth of the array/object
*
* @return string The textual representation of the variable.
*/
function dump(arr, level) {
	var dumped_text = "";
	if(!level) level = 0;

	// the padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { // Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { // If it is an array...
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { // Strings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}
