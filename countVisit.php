<?php
/**
 * IP访问统计，防刷机可缓存
 * 使用时会记录把访问信息记录在指定目录的countVisit.txt文件中
 * 若需修改缓存数，更改VISIT_MAX_CACHE后面的数字即可
 * 版权所有 zwpwjwtz(at)163.com 2006-2015。
**/

define('VISIT_MAX_CACHE',20);	//最大缓存IP数
define('VISIT_LOG_PATH','../');	//访问记录的存储路径
define('VISIT_CACHE_TIME',3600);	//在多长的时间内认为来自同一IP的访问是相同的

function countVisit()
{ 
	session_start();
	if(!isset($_SESSION['visited']))
	{
		$_SESSION['visited']=true;
                if (isset($_SERVER['HTTP_VIA']) && !empty($_SERVER['HTTP_VIA']))
                    $addr = $_SERVER['HTTP_X_FORWARDED_FOR'];
                else
                    $addr = $_SERVER['REMOTE_ADDR'];
		if ($addr=='unknown' || $addr=='' || !isCached($addr))
		{
			$f=fopen(VISIT_LOG_PATH.'visitRecord.txt','a');
  			if ($f)
  			{
				flock($f,LOCK_EX);
  				fwrite($f,$addr.','.gmdate('Y-m-d H:i:s')."\n");
   				flock($f,LOCK_UN);
			}	
   			fclose($f);
		}
	}
}

function isCached($ip)
{
	$cache_file=VISIT_LOG_PATH.'visitCache';
	if (file_exists($cache_file))
            $cachedIP=file_get_contents($cache_file);
        else
            $cachedIP='';
	if (strstr($cachedIP,$ip))//匹配到完整的IP记录
	{
		$p1=strpos($cachedIP,$ip);
		$p2=strpos($cachedIP,"|",$p1);
		if (!$p2) $p2=strlen($cachedIP)+1;
		$foundRecord=substr($cachedIP,$p1,$p2-$p1);
		if ((time()-(int)substr($foundRecord,strpos($foundRecord,'@')+1))<VISIT_CACHE_TIME)
			return true;//若距上一次访问时间小于VISIT_CACHE_TIME，则不更新缓存
		else 
		{
			$cachedIP=str_replace($foundRecord,$ip.'@'.time(),$cachedIP);
			file_put_contents($cache_file,$cachedIP,LOCK_EX);//否则更新
			return false;
	  	}
	}	  
	else //没有则添加
	{
	 	if ($cachedIP)	$cachedIPArray=explode('|',$cachedIP);
		else $cachedIPArray=array();
		if (count($cachedIPArray)< VISIT_MAX_CACHE)
		{
			$cachedIPArray[]=$ip.'@'.time();
		}
		else
		{
			array_pop($cachedIPArray);
			$cachedIPArray=array_merge((array)($ip.'@'.time()),$cachedIPArray);
		}
		$cachedIP=implode('|',$cachedIPArray);
		file_put_contents($cache_file,$cachedIP);
		return false;
	}
}

function maintenance(){
	header('Content-Type: text/html; charset=UTF-8');
	echo('维护中，请稍后访问。 Maintenance mode now, please come here later.');
	exit(0);
}
?>
