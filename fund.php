<?php
	if (!isset($_GET["send"])) {
		print "Nothing here!";
		exit;
	}
	include_once "config.php";
    $cfg = new FundConfig();
    print "FUNDA:";
    print $cfg->scanFund($cfg->FUNDA);
    print "\nQDII:";
    print $cfg->scanFund($cfg->QDII);
?>