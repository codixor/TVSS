<?php
ini_set('display_errors',1);
session_start();
if (!isset($_SESSION['admin_user_id']) || !$_SESSION['admin_user_id']){
	exit();
}

set_time_limit(0);
ob_start();
extract($_POST);
extract($_GET);

require_once("../../vars.php");
require_once("../../includes/show.class.php");
require_once("../../includes/sidereel.class.php");

if (@$sidereel_url){
	$sidereel = new Sidereel();
	$showstatus = $sidereel->getShowStatus($sidereel_url);
	print($showstatus);
} else {
	print("0");
}

?>