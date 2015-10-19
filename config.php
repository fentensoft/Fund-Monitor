<?php
	include_once "ez_sql_core.php";
    include_once "ez_sql_mysql.php";
    class FundConfig {
    	var $FUNDA = array("Name"=>"分级基金", "URL"=>"http://www.jisilu.cn/data/sfnew/funda_list/", "Key"=>"funda_base_est_dis_rt", "Threshold"=>"fundathre", "VolumeKey"=>"funda_volume");
    	var $QDII = array("Name"=>"QDII基金", "URL"=>"http://www.jisilu.cn/data/qdii/qdii_list/", "Key"=>"discount_rt", "Threshold"=>"qdiithre", "VolumeKey"=>"volume");
    	var $db;
    	var $smsUser = "123456789"; 
    	var $smsPass = "123456789";
    	function FundConfig() {
    		$this->db = new ezSQL_mysql(SAE_MYSQL_USER,SAE_MYSQL_PASS,SAE_MYSQL_DB,SAE_MYSQL_HOST_M . ':' . SAE_MYSQL_PORT);
    	}
    	function curl_get($url) {
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        $dom = curl_exec($ch);
	        curl_close($ch);
	        return $dom;
	    }
	    function readCfg($key, $convert = true) {
	    	$settings = $this->db->get_results("SELECT setvalue FROM tbl_fund WHERE setname='$key'");
	    	$result = array();
	    	foreach ($settings as $setting) {
	    		$result[] = $setting->setvalue;
	    	}
	    	if ($convert && count($result) == 1) $result = $result[0];
	    	return $result;
	    }
	    function scanFund($dtType) {
	    	$content = $this->curl_get($dtType["URL"]);
		    $threh = floatval($this->readCfg($dtType["Threshold"] . "high"));
		    $threl = floatval($this->readCfg($dtType["Threshold"] . "low"));
		    $volthre = floatval($this->readCfg("volthre"));
		    if($content) {
		    	$arr = json_decode($content, true);
		    	$iexist = false;
		    	foreach ($arr["rows"] as $row) {
		    		$rt = floatval($row["cell"][$dtType["Key"]]);
		    		if (($rt > $threh || $rt < $threl) && (floatval($row["cell"][$dtType["VolumeKey"]]) >= $volthre)) {
		    			$iexist = true;
		    			break;
		    		}
		    	}
		    	if ($iexist) return $this->sendSMS(join(",", $this->readCfg("phone", false)), "【华东师范大学】温馨提示，当前监视的{$dtType['Name']}中有折溢价超过设定阈值的，请登录集思录网站查看。");
		    	else return "Nothing";
    		}
	    }
	    function sendSMS($phones,$str) {
	    	return $this->curl_get("http://api.smsbao.com/sms?u={$this->smsUser}&p={$this->smsPass}&m=$phones&c=" . urlencode($str));
	    }
    }  
?>