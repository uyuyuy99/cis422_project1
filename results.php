<?php
	
	include("core/init.inc.php");
	
	// Get the data as it's stored in the MySQL database
	$sql_students = $db->query("
		SELECT * FROM students
	");
	$num_students = $sql_students->num_rows;
	
	$students = array(); // This is the nicely formatted students array
	
	/*
		=== Convert compressed data ($sql_students) into a better format ($students) ===
	*/
	for ($i = 0; $i < $num_students; $i++) {
		// First, just copy the student data into the students array
		$students[$i] = $sql_students->fetch_assoc();
		
		// Second, convert language proficiency data from binary to associative array
		$lang_data = $students[$i]["languages"];
		$lang_data = (ord($lang_data[0]) + (ord($lang_data[1]) << 8));
		$students[$i]["languages"] = array();
		
		for ($j = 0; $j < 7; $j++) {
			$students[$i]["languages"][$j] = ($lang_data >> $j*2) & 3; // 14 bits are used to store language data
		}
		$students[$i]["languages"] = array_combine($lang_names, $students[$i]["languages"]);
		
		// Third, convert schedule data from binary to 2D array
		$sched_data = $students[$i]["schedule"];
		$students[$i]["schedule"] = array(); // Create array of arrayus
		
		for ($j = 0; $j < 16; $j++) { // 16 hours to pick from
			$students[$i]["schedule"][$j] = array(); // Create inner arrays
			$hour = ord($sched_data[$j]);
			
			for ($k = 0; $k < 7; $k++) { // 7 days in a week
				$students[$i]["schedule"][$j][$k] = ($hour >> $k) & 1;
			}
		}
	}
	
?>

<!DOCTYPE html>
<html>
<head>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:300' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" href="include/main.css" />
	<script type="text/javascript" src="include/jquery.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#generate").click(function() {
				
			});
		});
	</script>
	<title>Student Team Generator [CIS 422]</title>
</head>
<body>
	<div id="main">
		<h1>Student Team Generator</h1>
		<hr><br>
		<button id="generate" class="greenbutt" type="button">Generate Teams</button>
	</main>
</body>
<html>