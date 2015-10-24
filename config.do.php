<?php
	if (!isset($_GET["act"])) {
		print "Nothing here!";
		exit;
	}
	include_once "config.php";
	$cfg = new FundConfig();
	function checkPwd($pwd) {
		global $cfg;
		return $cfg->readCfg("set_password") == $pwd;
	}
	function getFundHalt() {
		global $cfg;
		$data = json_decode($cfg->readCfg("dataFundAssetHalt"), true);
		$result = array();
		foreach ($data as $fund) {
			$row = "";
			foreach ($fund["data"] as $stock) {
				$row .= "<a target='_blank' href='http://quote.eastmoney.com/" . (($stock["id"][0]=="6") ? "sh" : "sz") . "{$stock["id"]}.html'>" . $stock["id"] . "</a>" . $stock["name"] . "&nbsp;&nbsp;&nbsp;";
			}
			$result[] = array("fund"=>"<a target='_blank' href='http://www.jisilu.cn/data/sfnew/detail/{$fund["code"]}'>" . $fund["code"] . "</a>" . $fund["basenm"], "stock"=>$row);
		}
		return json_encode($result);
	}
	function listFundAssetHalt() {
		global $cfg;
		$funds = $cfg->getAllFunds();
		$result = array();
		$stocks = array();
		foreach ($funds as $fund) {
			$assets = $cfg->getFundAsset($fund["id"], 5);
			$has = false;
			$tmp = array();
			if (count($assets) > 0) {
				foreach ($assets as $asset) {
					if (!isset($stocks[$asset["id"]]))
						$stocks[$asset["id"]] = $cfg->isHalt($asset["id"]);
					if ($stocks[$asset["id"]]) {
						$has = true;
						$tmp[] = $asset;
					}
				}
			}
			if (count($tmp) > 0)
				$result[] = array("code"=>$fund["id"], "basenm"=>$fund["name"], "data"=>$tmp);
		}
		return $cfg->writeCfg("dataFundAssetHalt", json_encode($result)) ? "Success" : "Failed";
	}
	function updateCfg() {
		global $cfg;
		if (checkPwd($_GET["pwd"])) {
			$cfg->setCfg($_POST);
			return "修改成功";
		} else {
			return "密码错误";
		}
		
	}
	switch ($_GET["act"]) {
		case "balance": print $cfg->balance();break;
		case "getcfg": print json_encode($cfg->readAllCfg());break;
		case "getfundassethalt": print getFundHalt();break;
		case "listhaltfund": print listFundAssetHalt();break;
		case "setcfg": print updateCfg();break;
	}
?>