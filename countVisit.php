<?php
/**
 * IP访问统计，防刷机可缓存
 * 访问信息记录到 VISIT_LOG_PATH 目录下 VISIT_LOG_CURRENT_NAME 所对应的文件中
 * 可以通过指定 VISIT_LOG_ALLOW_SPLIT 来开启/关闭日志自动拆分功能
 * 若需修改访问记录的缓存数，更改VISIT_MAX_CACHE后面的数字即可
 * 版权所有 zwpwjwtz(at)163.com 2006-2015。
**/

define('VISIT_LOG_PATH','../');	//访问记录的存储路径
define('VISIT_LOG_CURRENT_NAME','visitRecord.txt');     //当前所使用的日志文件名

define('VISIT_LOG_ALLOW_SPLIT',true);   //是否允许自动拆分日志文件
define('VISIT_LOG_MAX_SIZE',100000);  //单个日志文件的最大尺寸
define('VISIT_LOG_MAX_NUMBER',1000);    //日志编号的最大值
define('VISIT_LOG_NAME_FORMAT','visitRecord_%s.txt');    //分割后日志文件的命名格式(用于sprintf)

define('VISIT_MAX_CACHE',50);	//最大缓存IP数
define('VISIT_CACHE_TIME',3600);	//在多长的时间内认为来自同一IP的访问是相同的


function getLogFileName($logNumber)
{
    $logNumber=sprintf('%0'.(int)log10(VISIT_LOG_MAX_NUMBER).'d',$logNumber % VISIT_LOG_MAX_NUMBER);
    return VISIT_LOG_PATH.sprintf(VISIT_LOG_NAME_FORMAT,$logNumber);
}

function getNextLogNumber()
{
    //查找首次连续出现的文件序号的最大值
    //若存在序号不连续的文件，可能导致判断错误！
    
    //文件编号从1开始
    $number=1;
    
    //查找最小编号
    while(!file_exists(getLogFileName($number)) && $number<VISIT_LOG_MAX_NUMBER)
    {
        $number++;
    }
    if ($number==VISIT_LOG_MAX_NUMBER) return 1;
    
    //查找最大编号
    //当编号达到最大值时，默认写入0号文件，否则写入下一编号所对应的文件
    while(file_exists(getLogFileName($number)) && $number!=VISIT_LOG_MAX_NUMBER)
    {
        $number++;
    }
    
    return $number % VISIT_LOG_MAX_NUMBER;
}

function correctFileSize($filename)
{
    $temp='';
    if (filesize($filename) > VISIT_LOG_MAX_SIZE)
    {
        $fp=fopen($filename,'rb');
        for($i=0;$i<filesize($filename);$i+=VISIT_LOG_MAX_SIZE)
        {
            fseek($fp,$i);
            $temp=fread($fp,VISIT_LOG_MAX_SIZE);
            
            //避免将一个记录分割开来
            $p=strrpos($temp,"\n");
            $i-=strlen($temp)-$p-1;
            $temp=substr($temp,0,$p);
            
            //写入新日志文件
            if($i+VISIT_LOG_MAX_SIZE<filesize($filename))
            {
                $extraFile=getLogFileName(getNextLogNumber());
                file_put_contents($extraFile,$temp,LOCK_EX);
            }
            else
            {
                break;
            }
        }
        fclose($fp);
        file_put_contents($filename,$temp,LOCK_EX);
    }
}


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
                        //准备写入的记录
                        $addr=$addr.','.date('Y-m-d H:i:s')."\n";
                        
                        //写入日志文件
                        $log_file=VISIT_LOG_PATH.VISIT_LOG_CURRENT_NAME;
                        file_put_contents($log_file,$addr,FILE_APPEND|LOCK_EX);
                        
                        //检查日志文件尺寸
                        if (VISIT_LOG_ALLOW_SPLIT) correctFileSize($log_file);
		}
	}
}        

function isCached($ip)
{
	$cache_file=VISIT_LOG_PATH.'visitCache';        
	$cachedIP=file_get_contents($cache_file);
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
		file_put_contents($cache_file,$cachedIP,LOCK_EX);
		return false;
	}
}

function maintenance(){
	header('Content-Type: text/html; charset=UTF-8');
	echo('维护中，请稍后访问。 Maintenance mode now, please come here later.');
	exit(0);
}
?>
