<?php
	
	// Inaccessible file
	if(basename(__FILE__) == basename($_SERVER['PHP_SELF'])){header("HTTP/1.1 403 Forbidden");exit();}
	
	// Generic functions used across entire site
	include("util.inc.php");
	
	//Disable magic quotes
	if (get_magic_quotes_gpc()) {
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		unset($process);
	}
	
	// Connect to database
	$db_creds = file_get_contents(dirname(__FILE__) . "/../../project_1_db");
	$db_creds = explode(",", $db_creds);
	print_r($db_creds);
	$db = mysqli_connect("localhost", $db_creds[0], $db_creds[1], $db_creds[2]);
	
?>