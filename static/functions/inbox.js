//Using this instead of comments as comments has pertty damn strict requirements on the variable names required

function Quick_Preview() {
	$('#buttons').raw().innerHTML = "<input type='button' value='Editor' onclick='Quick_Edit();' /><input type='submit' value='Send Message' />";
	ajax.post("ajax.php?action=preview","messageform", function(response){
		$('#quickpost').hide();
		$('#preview').raw().innerHTML = response;
		$('#preview').show();
	});
}

function Quick_Edit() {
	$('#buttons').raw().innerHTML = "<input type='button' value='Preview' onclick='Quick_Preview();' /><input type='submit' value='Send Message' />";
	$('#preview').hide();
	$('#quickpost').show();
}


function Inbox_Preview(appendid) {
      if (appendid == undefined ) appendid = '';
	if ($('#preview'+appendid).has_class('hidden')) {
		ajax.post('ajax.php?action=preview_newpm', "messageform"+appendid, function (response) {
                  $('#preview'+appendid).raw().innerHTML = response;
                  $('#preview'+appendid).show();
			$('#quickpost'+appendid).hide();
			$('#previewbtn'+appendid).raw().value = "Edit Message";
		});
	} else {
		$('#preview'+appendid).hide();
		$('#quickpost'+appendid).toggle();
		$('#previewbtn'+appendid).raw().value = "Preview";
	}
}

