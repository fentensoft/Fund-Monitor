<?php
	include_once "ez_sql_core.php";
    include_once "ez_sql_mysql.php";
    class FundConfig {
    	var $FUNDA = array("Name"=>"分级基金", "URL"=>"http://www.jisilu.cn/data/sfnew/funda_list/", "Key"=>"funda_base_est_dis_rt", "Threshold"=>"cfg_fundathre", "VolumeKey"=>"funda_volume", "Toggle"=>"cfg_fundatoggle");
    	var $QDII = array("Name"=>"QDII基金", "URL"=>"http://www.jisilu.cn/data/qdii/qdii_list/", "Key"=>"discount_rt", "Threshold"=>"cfg_qdiithre", "VolumeKey"=>"volume", "Toggle"=>"cfg_qdiitoggle");
    	var $db, $kv;
    	var $smsUser = "123456789"; 
    	var $smsPass = "123456789";
    	function FundConfig() {
    		$this->db = new ezSQL_mysql(SAE_MYSQL_USER,SAE_MYSQL_PASS,SAE_MYSQL_DB,SAE_MYSQL_HOST_M . ':' . SAE_MYSQL_PORT);
    		$this->kv = new SaeKV();
    	}
    	function balance() {
    		$content = explode(",", $this->curl_get("http://www.smsbao.com/query?u={$this->smsUser}&p={$this->smsPass}"));
    		return $content[1];
    	}
    	function curl_get($url) {
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        $dom = curl_exec($ch);
	        if(curl_errno($ch)) $dom = curl_error($ch);
	        curl_close($ch);
	        return $dom;
	    }
	    function curl_post($url, $data) {
	    	$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$dom = curl_exec($ch);
			if(curl_errno($ch)) $dom = curl_error($ch);
			curl_close($ch);
			return $dom;
	    }
	    function deleteCfg($key) {
	    	return $this->kv->delete($key);
	    }
	    function getAllFunds() {
	    	$content = $this->curl_get("http://www.jisilu.cn/data/sfnew/fundm_list/");
	    	if ($content) {
	    		$data = json_decode($content, true);
	    		$result = array();
	    		foreach($data["rows"] as $fund)
	    			$result[] = array("id"=>$fund["cell"]["base_fund_id"], "name"=>$fund["cell"]["base_fund_nm"]);
	    		return $result;
	    	}
	    }
	    function getFundAsset($code, $n, $ratio) {
	    	$data = $this->curl_post("http://www.jisilu.cn/data/lof/detail_fund_stocks/" . $code, "is_search=1&rp=50&page=1&fund_id=" . $code);
	    	if ($data) {
	    		$data = json_decode($data, true);
	    		if (count($data["rows"]) == 0)
	    			return;
	    		else {
	    			$result = array();
	    			if ($n > 10)
	    				$n = 10;
	    			for ($i = 0; $i < $n; $i++) {
	    				if (floatval($data["rows"][$i]["cell"]["ratio"]) < floatval($ratio))
	    					break;
	    				$result[] = array("id"=>$data["rows"][$i]["cell"]["asset_id"], "name"=>$data["rows"][$i]["cell"]["asset_nm"]);
	    			}
	    			return $result;
	    		}
	    	}
	    }
	    function getStockHalt() {
	    	$content = $this->curl_post("http://www.cninfo.com.cn/cninfo-new/announcement/query", "searchkey=复牌;&plate=szcy;shmb;szmb;szzx;sz;&seDate=" . date("Y-m-d", time() - 259200) . 
	    								" ~ " . date("Y-m-d", time()) . "&tabName=summary&column=sse&columnTitle=历史公告查询&pageSize=30&pageNum=1");
    		$data = json_decode($content, true);
    		if (!$data["announcements"]) {
    			print("P1 failed<br/>");
    			return false;
    		}
    		$tmp = $data["announcements"];
    		$result = array();
    		$pages = ceil($data["totalAnnouncement"]/30);
    		print("Total:{$data["totalAnnouncement"]},Pages:$pages<br/>");
    		print("P1 ok<br/>");
    		for ($i = 2; $i <= $pages; $i++) {
    			$content = $this->curl_post("http://www.cninfo.com.cn/cninfo-new/announcement/query", "searchkey=复牌;&plate=szcy;shmb;szmb;szzx;sz;&seDate=" . date("Y-m-d", time() - 259200) . 
    								" ~ " . date("Y-m-d", time()) . "&tabName=summary&column=sse&columnTitle=历史公告查询&pageSize=30&pageNum=$i");
				print("P$i ");
				$data = json_decode($content, true);
				if (!$data["announcements"]) {
	    			print("failed<br/>");
	    			return false;
	    		}
	    		print("ok<br/>");
	    		$tmp = array_merge($tmp, $data["announcements"]);
    		}
    		foreach ($tmp as $res) {
    			if (!strstr($res["announcementTitle"], "延期") && !strstr($res["announcementTitle"], "暂不"))
    				$result[$res["secCode"]] = true;
    		}
    		return $result;
	    }
	    function isHalt($code) {
	    	$stock = explode(",", $this->curl_get("http://hq.sinajs.cn/list=" . (($code[0]=="6") ? "sh" : "sz") . $code));
    		return ($stock[1] == "0.00");
	    }
	    function readAllCfg() {
	    	return $this->kv->pkrget("cfg_", 10);
	    }
	    function readCfg($key) {
	    	return $this->kv->get($key);
	    }
	    function readPhones() {
	    	$phones = $this->db->get_results("SELECT setvalue FROM tbl_fund WHERE setname='phone'");
	    	$result = array();
	    	foreach ($phones as $phone) {
	    		$result[] = $phone->setvalue;
	    	}
	    	return $result;
	    }
	    function scanFund($dtType) {
	    	$content = $this->curl_get($dtType["URL"]);
		    $threh = floatval($this->readCfg($dtType["Threshold"] . "high"));
		    $threl = floatval($this->readCfg($dtType["Threshold"] . "low"));
		    $volthre = floatval($this->readCfg("cfg_volthre"));
		    if($content) {
		    	$arr = json_decode($content, true);
		    	$lst = array();
		    	foreach ($arr["rows"] as $row) {
		    		$rt = floatval($row["cell"][$dtType["Key"]]);
		    		if (($rt > $threh || $rt < $threl) && (floatval($row["cell"][$dtType["VolumeKey"]]) >= $volthre)) {
		    			$lst[] = $row["id"];
		    		}
		    	}
		    	$restr = join(",", $lst);
		    	$issend = ($this->readCfg($dtType["Toggle"]) == "yes");
		    	if ($restr=="") $restr="NULL";
		    	if (!empty($lst) && isset($_GET["send"]) && $issend) {
		    		$result = $this->sendSMS(join(",", $this->readPhones()), "【华东师范大学】温馨提示，当前监视的{$dtType['Name']}中有折溢价超过设定阈值的，请登录集思录网站查看。");
		    		return $result . ";" . $restr;
		    	} else return $restr;
    		}
	    }
	    function sendSMS($phones,$str) {
	    	return $this->curl_get("http://api.smsbao.com/sms?u={$this->smsUser}&p={$this->smsPass}&m=$phones&c=" . urlencode($str));
	    }
	    function setCfg($arr) {
	    	foreach ($arr as $key => $val) {
	    		$this->writeCfg($key, $val);
	    	}
		}
		function writeCfg($key, $val) {
			return $this->kv->set($key, $val);
		}
    }  
?>