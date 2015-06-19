<?php
/**
 * IP访问统计，防刷机可缓存
 * 使用时会记录把访问信息记录在指定目录的countVisit.txt文件中
 * 若需修改缓存数，更改VISIT_MAX_CACHE后面的数字即可
 * 版权所有 zwpwjwtz(at)163.com 2006-2015。
**/

define('VISIT_LOG_PATH','../');	//访问记录的存储路径
define('MAX_LOG_SIZE',100000);  //单个日志文件的最大尺寸
define('LOG_NAME_FORMAT','visitRecord_%03d.txt');    //日志文件的命名格式(用于sprintf)

define('VISIT_MAX_CACHE',20);	//最大缓存IP数
define('VISIT_CACHE_TIME',3600);	//在多长的时间内认为来自同一IP的访问是相同的


function getLogFileName($logNumber)
{
    return VISIT_LOG_PATH.sprintf(LOG_NAME_FORMAT,$logNumber);
}

function getMaxLogNumber()
{
    //查找首次连续出现的文件序号的最大值
    //若存在序号不连续的文件，可能导致判断错误！
    $number=0;
    $default=getLogFileName($number);
    $probe=$default;
    
    //查找最小编号
    while(!file_exists($probe))
    {
        if ($probe==$default && $number!=0) break;
        $number++;
        $probe=getLogFileName($number);
    }
    
    //查找最大编号
    $last=$number;
    while(file_exists($probe))
    {
        if ($probe==$default && $number!=0) break;
        $last=$number++;
        $probe=getLogFileName($number);
    }
    
    return $last;
}

function correctFileSize($filename,$number)
{
    if (filesize($filename) > MAX_LOG_SIZE)
    {
        $n=(int)(filesize($filename) / MAX_LOG_SIZE);
        $fp=fopen($filename,'rb');
        $firstPart='';
        for($i=0;$i<filesize($filename);$i+=MAX_LOG_SIZE)
        {
            fseek($fp,$i);
            $temp=fread($fp,MAX_LOG_SIZE);
            
            //避免将一个记录分割开来
            $p=strrpos($temp,"\n");
            $i-=strlen($temp)-$p-1;
            $temp=substr($temp,0,$p);
            
            if ($firstPart=='')
                $firstPart=$temp;
            else
                file_put_contents(getLogFileName($number),$temp);
            
            $number++;
        }
        fclose($fp);
        file_put_contents($filename,$firstPart);
    }
}


function countVisit()
{ 
	session_start();
	if(!isset($_SESSION['visited']))
	{
		$_SESSION['visited']=true;
		$addr = ($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		if ($addr=='unknown' || $addr=='' || !isCached($addr))
		{
                        //准备写入的记录
                        $addr=$addr.','.date('Y-m-d H:i:s')."\n";
                        
                        //检查日志文件尺寸
                        $log_number=getMaxLogNumber();
                        $log_file=getLogFileName($log_number);
                        if (!file_exists($log_file))
                        {
                            file_put_contents($log_file,NULL);
                        }
                        else
                        {
                            correctFileSize($log_file,$log_number,LOG_NAME_FORMAT);
                            $log_number=getMaxLogNumber();
                            $log_file=getLogFileName($log_number);
                        }                        
                        if (filesize($log_file)+strlen($addr) > MAX_LOG_SIZE)
                        {
                            $log_number++;
                            $log_file=getLogFileName($log_number);
                        }
                        
                        //写入日志文件
			$f=fopen($log_file,'a');
  			if ($f)
  			{
				flock($f,LOCK_EX);
  				fwrite($f,$addr);
   				flock($f,LOCK_UN);
			}	
   			fclose($f);
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
