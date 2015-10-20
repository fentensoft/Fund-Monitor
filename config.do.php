<?php
	if (!isset($_GET["act"])) {
		print "Nothing here!";
		exit;
	}
	include_once "config.php";
	$cfg = new FundConfig();
	function updateCfg() {
		global $cfg;
		$cfg->setCfg($_POST);
	}
	switch ($_GET["act"]) {
		case "getcfg": print json_encode($cfg->readAllCfg());break;
		case "setcfg": updateCfg();break;
		case "balance": print $cfg->balance();
	}
?>