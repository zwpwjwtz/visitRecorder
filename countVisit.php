<?php
/**
 *IP访问统计，防刷机可缓存
 *使用时会记录把访问信息记录在上一级目录的countVisit.txt文件中
 *若需修改缓存数，更改VISIT_MAX_CACHE后面的数字即可
 *WTZ Software独家编写，WTZ Corporation 2006-2013版权所有。
**/

define('VISIT_MAX_CACHE',20);
define('VISIT_LOG_PATH','../');

function countVisit()
{ 
	session_start();
	if(!isset($_SESSION['visited']))
	{
		$_SESSION['visited']=true;
		$addr = ($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		if ($addr=='unknown' || $addr=='' || !isCached($addr))
		{
			$f=fopen(VISIT_LOG_PATH.'visitRecord.txt','a');
  			if ($f)
  			{
				flock($f,LOCK_EX);
  				fwrite($f,$addr.','.date('Y-m-d H:i:s')."\n");
   				flock($f,LOCK_UN);
			}	
   			fclose($f);
		}
	}
}

function isCached($ip)
{
	$cache_file=VISIT_LOG_PATH.'visitCache';
	if (!file_exists($cache_file)) file_put_contents(VISIT_LOG_PATH.$cache_file,NULL);
	$cachedIP=file_get_contents($cache_file);
	if (strstr($cachedIP,$ip))//匹配到完整的IP记录
	{
		$foundIP=substr($cachedIP,strpos($cachedIP,$ip),strlen($ip)+20);
		if (date('Y-m-d')==substr($foundIP,strpos($foundIP,'@')+1,10) && date('H')==substr($foundIP,strpos($foundIP,':')-2,2))	return true;//缓存当天1小时内多次访问的记录
	  	else 
	  	{
			$cachedIP=str_replace($foundIP,$ip.'@'.date('Y-m-d H:i:s').'|',$cachedIP);
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
			$cachedIPArray[]=$ip.'@'.date('Y-m-d H:i:s');
		}
		else
		{
			array_splice($cachedIPArray,VISIT_MAX_CACHE-1);
			//$cachedIPArray[]=$ip.'@'.date('Y-m-d H:i:s');
			array_splice($cachedIPArray,0,0,$ip.'@'.date('Y-m-d H:i:s'));
		}
		$cachedIP=implode('|',$cachedIPArray);
		file_put_contents($cache_file,$cachedIP);
		return false;
	}
}

function maintainance(){
	header('Content-Type: text/html; charset=UTF-8');
	echo('维护中，请稍后访问。 Maintainance mode now, please come here later.');
	exit(0);
}
?>
