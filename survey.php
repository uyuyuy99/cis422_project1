<?php
	
	include("core/init.inc.php");
	
	$errors = array();
	
	// Handle form input
	if (isset($_POST["name"], $_POST["course"], $_POST["password"])) {
		$name = $_POST["name"];
		
		// Produce error messages in form if necessary
		if (strlen($name) < 4 || strlen($name) > 64) {
			$errors["name"] = "Must be between 4 and 64 characters";
		} else if (ctype_alpha(str_replace(" ", "", $name)) == false) {
			$errors["name"] = "Must contain only letters (and spaces)";
		}
		if (!isset($_POST["role"])) {
			$errors["role"] = "You must select at least 1 team role";
		} else if ($_POST["role"] < 1 || $_POST["role"] > 3) {
			$errors["role"] = "Invalid value";
		}
		if (!isset($_POST["schedule"]) || sizeof($_POST["schedule"]) < 3) {
			$errors["schedule"] = "You must pick at least 3 possible meeting hours per week";
		}
		$lang_total = 0;
		foreach ($lang_names as $l) {
			$lang_total += $_POST["lang-$l"];
		}
		if ($lang_total < 2) {
			$errors["languages"] = "You must choose at least 2 languages (or 1 if you know a lot)";
		}
		
		// Only proceed if no errors
		if (empty($errors)) {
			// Convert languages to binary data
			// 2 bytes total - each 2 bits (00, 01 or 10) represents 1 language
			$lang_data = 0;
			$i = 0;
			foreach ($lang_names as $l) {
				$lang_data |= $_POST["lang-$l"] << $i;
				echo base_convert($_POST["lang-$l"] << $i, 10, 2) . "<br>";
				$i += 2;
			}
			echo base_convert($lang_data, 10, 2) . "<br>";
			$lang_data = $db->real_escape_string(chr($lang_data % 256) . chr($lang_data >> 8)); // Convert from int to string
			
			// Convert schedule to binary data
			// 16 bytes total - each byte represents 1 hour - each bit represents 1 day of the week
			$schedule = array();
			for ($i = 0; $i < 16; $i++) $schedule[$i] = 0; // Start w/ empty schedule (all 0s)
			foreach ($_POST["schedule"] as $s) { // s = a single time slot
				$hour = floor($s / 8);
				$day = $s % 8;
				$schedule[$hour] |= 1 << $day;
			}
			$schedule_data = "";
			foreach ($schedule as $hour) {
				$schedule_data .= chr($hour); // Convert from ints to string
			}
			$schedule_data = $db->real_escape_string($schedule_data);
			
			// Calculate boolean values for role
			$frontend = $_POST["role"] != 2 ? 1 : 0;
			$backend = $_POST["role"] >= 2 ? 1 : 0;
			
			$db->query("
				INSERT INTO students (name, frontend, backend, languages, schedule)
				VALUES ('$name', $frontend, $backend, '$lang_data', '$schedule_data')
			");
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
		function show_survey() {
			$("#pre-survey").hide();
			$("#survey").show();
		}
		function show_pre_survey() {
			$("#pre-survey").show();
			$("#survey").hide();
		}
		$(document).ready(function() {
			$('#next').click(show_survey);
			$('#back').click(show_pre_survey);
			<?php if (!empty($errors) && !isset($errors["name"]) && !isset($errors["password"])) echo "show_survey();" ?>
		});
	</script>
	<title>Student Team Survey [CIS 422]</title>
</head>
<body>
	<div id="main">
		<h1>Student Team Survey</h1>
		<hr>
		<form method="post">
			<table class="formtable" id="pre-survey">
				<tr>
					<td class="label"><label for="name">Full Name:</label></td>
					<td><input type="text" name="name" id="name" maxlength=64 value="<?php autofill("name"); ?>"></td>
					<td class="error"><?php print_err($errors, "name"); ?></td>
				</tr>
				<tr>
					<td class="label"><label for="uoid">9-Digit UO ID:</label></td>
					<td><input disabled type="text" name="uoid" id="uoid" maxlength=9 value="123456789"></td>
					<td class="error"><?php print_err($errors, "uoid"); ?></td>
				</tr>
				<tr>
					<td class="label"><label for="course">Course:</label></td>
					<td><select name="course" id="course">
						<option>CIS 422 (Young)</option>
					</select></td>
					<td class="error"><?php print_err($errors, "course"); ?></td>
				</tr>
				<tr>
					<td class="label"><label for="password">Course Password:</label></td>
					<td><input type="password" name="password" id="password" maxlength=64 value="<?php autofill("password"); ?>"></td>
					<td class="error"><?php print_err($errors, "password"); ?></td>
				</tr>
				<tr>
					<td class="next" colspan=3><button type="button" id="next" class="greenbutt">Next &#8594;</button></td>
				</tr>
			</table>
			<table class="formtable" id="survey" style="display: none;">
				<tr>
					<td class="label-above" colspan=2><label for="languages">Team Role:</label></td>
					<td class="error"><?php print_err($errors, "role"); ?></td>
				</tr>
				<tr>
					<td class="tabbed" colspan=3><table class="input-table" id="role-table">
						<tr>
							<td colspan=2>Select which role you feel suits you the best (or both):</td>
						</tr>
						<tr>
							<td><input type="radio" name="role" id="role1" value=1></td>
							<td><label for="role1">Front-end development (user interface)</label></td>
						</tr>
						<tr>
							<td><input type="radio" name="role" id="role2" value=2></td>
							<td><label for="role2">Back-end development (program logic)</label></td>
						</tr>
						<tr>
							<td><input type="radio" name="role" id="role3" value=3></td>
							<td><label for="role3">Both of the above</label></td>
						</tr>
					</table></td>
				</tr>
				<tr>
					<td class="label-above" colspan=2><label for="languages">Programming Languages:</label></td>
					<td class="error"><?php print_err($errors, "languages"); ?></td>
				</tr>
				<tr>
					<!--<td><input type="range" name="languages" id="languages" min=-2 max=2 step=1></td>-->
					<td class="tabbed" colspan=3><table class="input-table" id="language-table">
						<tr>
							<td></td>
							<td><div class="tooltip">
								Don't Know
								<span class="tooltiptext">I do not use these languages</span>
							</div></td>
							<td><div class="tooltip">
								Know a Little
								<span class="tooltiptext">I am an ameteur programmer in these languages</span>
							</div></td>
							<td><div class="tooltip">
								Know a Lot
								<span class="tooltiptext">I am relatively proficient in these languages</span>
							</div></td>
						</tr>
						<tr>
							<td>Python</td>
							<td><input type="radio" name="lang-py" value=0 checked></td>
							<td><input type="radio" name="lang-py" value=1></td>
							<td><input type="radio" name="lang-py" value=2></td>
						</tr>
						<tr>
							<td>Java</td>
							<td><input type="radio" name="lang-java" value=0 checked></td>
							<td><input type="radio" name="lang-java" value=1></td>
							<td><input type="radio" name="lang-java" value=2></td>
						</tr>
						<tr>
							<td>C/C++</td>
							<td><input type="radio" name="lang-c" value=0 checked></td>
							<td><input type="radio" name="lang-c" value=1></td>
							<td><input type="radio" name="lang-c" value=2></td>
						</tr>
						<tr>
							<td>PHP</td>
							<td><input type="radio" name="lang-php" value=0 checked></td>
							<td><input type="radio" name="lang-php" value=1></td>
							<td><input type="radio" name="lang-php" value=2></td>
						</tr>
						<tr>
							<td>SQL</td>
							<td><input type="radio" name="lang-sql" value=0 checked></td>
							<td><input type="radio" name="lang-sql" value=1></td>
							<td><input type="radio" name="lang-sql" value=2></td>
						</tr>
						<tr>
							<td>HTML/CSS/JS</td>
							<td><input type="radio" name="lang-html" value=0 checked></td>
							<td><input type="radio" name="lang-html" value=1></td>
							<td><input type="radio" name="lang-html" value=2></td>
						</tr>
						<tr>
							<td>Bash/Sh</td>
							<td><input type="radio" name="lang-sh" value=0 checked></td>
							<td><input type="radio" name="lang-sh" value=1></td>
							<td><input type="radio" name="lang-sh" value=2></td>
						</tr>
					</table></td>
				</tr>
				<tr>
					<td class="label-above" colspan=2><label for="schedule">Possible Meeting Times:</label></td>
					<td class="error"><?php print_err($errors, "schedule"); ?></td>
				</tr>
				<tr>
					<td class="tabbed" colspan=3><table class="input-table" id="schedule-table">
						<tr>
							<td></td>
							<td><div class="tooltip">
								M
								<span class="tooltiptext small">Monday</span>
							</div></td>
							<td><div class="tooltip">
								T
								<span class="tooltiptext small">Tuesday</span>
							</div></td>
							<td><div class="tooltip">
								W
								<span class="tooltiptext small">Wednesday</span>
							</div></td>
							<td><div class="tooltip">
								Th
								<span class="tooltiptext small">Thursday</span>
							</div></td>
							<td><div class="tooltip">
								F
								<span class="tooltiptext small">Friday</span>
							</div></td>
							<td><div class="tooltip">
								S
								<span class="tooltiptext small">Saturday</span>
							</div></td>
							<td><div class="tooltip">
								Su
								<span class="tooltiptext small">Sunday</span>
							</div></td>
						</tr>
						<?php
							
							// Generate table of checkboxes for schedule
							$hrs = array(
								"6am - 7am",
								"7am - 8am",
								"8am - 9am",
								"9am - 10am",
								"10am - 11am",
								"11am - 12pm",
								"12pm - 1pm",
								"1pm - 2pm",
								"2pm - 3pm",
								"3pm - 4pm",
								"4pm - 5pm",
								"5pm - 6pm",
								"6pm - 7pm",
								"7pm - 8pm",
								"8pm - 9pm",
								"9pm - 10pm",
							);
							for ($i = 0; $i < 16; $i++) {
								echo "<tr><td>" . $hrs[$i] . "</td>";
								for ($j = 0; $j < 7; $j++) {
									$val = ($i * 8) + $j;
									echo "<td><input type='checkbox' name='schedule[]' value=$val></td>";
								}
								echo "</tr>";
							}
							
						?>
						<!--
						<tr>
							<td>6am - 7am</td>
							<td><input type="checkbox" name="schedule" value=0></td>
							<td><input type="checkbox" name="schedule" value=1></td>
							<td><input type="checkbox" name="schedule" value=2></td>
							<td><input type="checkbox" name="schedule" value=3></td>
							<td><input type="checkbox" name="schedule" value=4></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>7am - 8am</td>
							<td><input type="checkbox" name="schedule" value=5></td>
							<td><input type="checkbox" name="schedule" value=6></td>
							<td><input type="checkbox" name="schedule" value=7></td>
							<td><input type="checkbox" name="schedule" value=8></td>
							<td><input type="checkbox" name="schedule" value=9></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>8am - 9am</td>
							<td><input type="checkbox" name="schedule" value=10></td>
							<td><input type="checkbox" name="schedule" value=11></td>
							<td><input type="checkbox" name="schedule" value=12></td>
							<td><input type="checkbox" name="schedule" value=13></td>
							<td><input type="checkbox" name="schedule" value=14></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>9am - 10am</td>
							<td><input type="checkbox" name="schedule" value=15></td>
							<td><input type="checkbox" name="schedule" value=16></td>
							<td><input type="checkbox" name="schedule" value=17></td>
							<td><input type="checkbox" name="schedule" value=18></td>
							<td><input type="checkbox" name="schedule" value=19></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>10am - 11am</td>
							<td><input type="checkbox" name="schedule" value=20></td>
							<td><input type="checkbox" name="schedule" value=21></td>
							<td><input type="checkbox" name="schedule" value=22></td>
							<td><input type="checkbox" name="schedule" value=23></td>
							<td><input type="checkbox" name="schedule" value=24></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>11am - 12pm</td>
							<td><input type="checkbox" name="schedule" value=25></td>
							<td><input type="checkbox" name="schedule" value=26></td>
							<td><input type="checkbox" name="schedule" value=27></td>
							<td><input type="checkbox" name="schedule" value=28></td>
							<td><input type="checkbox" name="schedule" value=29></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>12pm - 1pm</td>
							<td><input type="checkbox" name="schedule" value=30></td>
							<td><input type="checkbox" name="schedule" value=31></td>
							<td><input type="checkbox" name="schedule" value=32></td>
							<td><input type="checkbox" name="schedule" value=33></td>
							<td><input type="checkbox" name="schedule" value=34></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>1pm - 2pm</td>
							<td><input type="checkbox" name="schedule" value=35></td>
							<td><input type="checkbox" name="schedule" value=36></td>
							<td><input type="checkbox" name="schedule" value=37></td>
							<td><input type="checkbox" name="schedule" value=38></td>
							<td><input type="checkbox" name="schedule" value=39></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>2pm - 3pm</td>
							<td><input type="checkbox" name="schedule" value=40></td>
							<td><input type="checkbox" name="schedule" value=41></td>
							<td><input type="checkbox" name="schedule" value=42></td>
							<td><input type="checkbox" name="schedule" value=43></td>
							<td><input type="checkbox" name="schedule" value=44></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>3pm - 4pm</td>
							<td><input type="checkbox" name="schedule" value=45></td>
							<td><input type="checkbox" name="schedule" value=46></td>
							<td><input type="checkbox" name="schedule" value=47></td>
							<td><input type="checkbox" name="schedule" value=48></td>
							<td><input type="checkbox" name="schedule" value=49></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>4pm - 5pm</td>
							<td><input type="checkbox" name="schedule" value=50></td>
							<td><input type="checkbox" name="schedule" value=51></td>
							<td><input type="checkbox" name="schedule" value=52></td>
							<td><input type="checkbox" name="schedule" value=53></td>
							<td><input type="checkbox" name="schedule" value=54></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>5pm - 6pm</td>
							<td><input type="checkbox" name="schedule" value=55></td>
							<td><input type="checkbox" name="schedule" value=56></td>
							<td><input type="checkbox" name="schedule" value=57></td>
							<td><input type="checkbox" name="schedule" value=58></td>
							<td><input type="checkbox" name="schedule" value=59></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>6pm - 7pm</td>
							<td><input type="checkbox" name="schedule" value=60></td>
							<td><input type="checkbox" name="schedule" value=61></td>
							<td><input type="checkbox" name="schedule" value=62></td>
							<td><input type="checkbox" name="schedule" value=63></td>
							<td><input type="checkbox" name="schedule" value=64></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>7pm - 8pm</td>
							<td><input type="checkbox" name="schedule" value=65></td>
							<td><input type="checkbox" name="schedule" value=66></td>
							<td><input type="checkbox" name="schedule" value=67></td>
							<td><input type="checkbox" name="schedule" value=68></td>
							<td><input type="checkbox" name="schedule" value=69></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>8pm - 9pm</td>
							<td><input type="checkbox" name="schedule" value=70></td>
							<td><input type="checkbox" name="schedule" value=71></td>
							<td><input type="checkbox" name="schedule" value=72></td>
							<td><input type="checkbox" name="schedule" value=73></td>
							<td><input type="checkbox" name="schedule" value=74></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						<tr>
							<td>9pm - 10pm</td>
							<td><input type="checkbox" name="schedule" value=75></td>
							<td><input type="checkbox" name="schedule" value=76></td>
							<td><input type="checkbox" name="schedule" value=77></td>
							<td><input type="checkbox" name="schedule" value=78></td>
							<td><input type="checkbox" name="schedule" value=79></td>
							<td><input type="checkbox" name="schedule" value=99></td>
							<td><input type="checkbox" name="schedule" value=99></td>
						</tr>
						-->
					</table></td>
				</tr>
				<tr>
					<td colspan=3>
						<div class="prev prev-next"><button type="button" id="back" class="graybutt">&#8592; Back</button></div>
						<div class="next prev-next"><button type="submit" class="greenbutt">Submit Survey</button></div>
					</td>
				</tr>
			</table>
		</form>
	</main>
</body>
<html>