<?php
	include_once "config.php";
    $cfg = new FundConfig();
    print "FUNDA:";
    print_r($cfg->scanFund($cfg->FUNDA));
    print "<br/>QDII:";
    print_r($cfg->scanFund($cfg->QDII));
?>