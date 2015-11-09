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
		if (count($data) > 0) {
			foreach ($data as $fund) {
				$row = "";
				foreach ($fund["data"] as $stock) {
					$row .= "<a target='_blank' class='stock {$stock["id"]}' href='http://quote.eastmoney.com/" . (($stock["id"][0]=="6") ? "sh" : "sz") . "{$stock["id"]}.html'>" . $stock["id"] . "</a>&nbsp;" . $stock["name"] . "&nbsp;&nbsp;&nbsp;";
				}
				$result[] = array("fund"=>"<a target='_blank' href='http://www.jisilu.cn/data/sfnew/detail/{$fund["code"]}'>" . $fund["code"] . "</a>&nbsp;" . $fund["basenm"], "stock"=>$row);
			}
		}
		return json_encode($result);
	}
	function listFundAssetHalt() {
		global $cfg;
		$funds = $cfg->getAllFunds();
		$result = array();
		$stocks = array();
		foreach ($funds as $fund) {
			$assets = $cfg->getFundAsset($fund["id"], 5, "5%");
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
	function updateDis() {
		global $cfg;
		$data = json_decode($cfg->readCfg("dataFundAssetHalt"), true);
		$list = array();
		$times=0;
		while(!$halt = $cfg->getStockHalt()) {
			if ($i++ > 10)
				return "Fucked";
		}
		if (count($data) > 0) {
			$has = false;
			foreach ($data as $fund) {
				foreach ($fund["data"] as $stock) {
					if (!isset($list[$stock["id"]])) {
						$list[$stock["id"]] = (isset($halt[$stock["id"]]));
						$has = $has or $list[$stock["id"]];
					}
				}
			}
			if ($has && $cfg->readCfg("cfg_resumetoggle") == "yes" && isset($_GET['send']))
				print("SMS:" . $this->sendSMS(join(",", $this->readPhones()), "【华东师范大学】温馨提示，检测到有分级基金重仓股可能复牌，请登录网站查看。") . "<br/>");
		}
		return $cfg->writeCfg("dataIsResume", json_encode($list)) ? "Success" : "Failed";
	}
	switch ($_GET["act"]) {
		case "balance": print $cfg->balance();break;
		case "getcfg": print json_encode($cfg->readAllCfg());break;
		case "getfundassethalt": print getFundHalt();break;
		case "getresume": print $cfg->readCfg("dataIsResume");break;
		case "listhaltfund": print listFundAssetHalt();break;
		case "setcfg": print updateCfg();break;
		case "updatadisclosure": print updateDis();break;
	}
?>