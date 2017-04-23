<?php
	
	$lang_names = array("py", "java", "c", "php", "sql", "html", "sh"); // Languages asked about in survey
	
	function print_err($errors, $err) {
		if (isset($errors[$err])) echo "
			<div class='tooltip'>
				<img src='include/img/error.png' width=24 height=24><span class='tooltiptext'>"
					. $errors[$err] .
				"</span>
			</div>
		";
	}
	
	function autofill($field) {
		if (isset($_POST[$field])) echo htmlentities($_POST[$field]);
	}
	
?>