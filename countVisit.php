<?php
/**
 *IP����ͳ�ƣ���ˢ���ɻ���
 *ʹ��ʱ���¼�ѷ�����Ϣ��¼��ָ��Ŀ¼��countVisit.txt�ļ���
 *�����޸Ļ�����������VISIT_MAX_CACHE��������ּ���
 *��Ȩ���� zwpwjwtz@163.com 2006-2015��
**/

define('VISIT_MAX_CACHE',20);	//��󻺴�IP��
define('VISIT_LOG_PATH','../');	//���ʼ�¼�Ĵ洢·��
define('VISIT_CACHE_TIME',3600);	//�ڶ೤��ʱ������Ϊ����ͬһIP�ķ�������ͬ��

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
	if (!file_exists($cache_file)) file_put_contents($cache_file,NULL);
	$cachedIP=file_get_contents($cache_file);
	if (strstr($cachedIP,$ip))//ƥ�䵽������IP��¼
	{
		$p1=strpos($cachedIP,$ip);
		$p2=strpos($cachedIP,"|",$p1);
		if (!$p2) $p2=strlen($cachedIP)+1;
		$foundRecord=substr($cachedIP,$p1,$p2-$p1);
		if ((time()-(int)substr($foundRecord,strpos($foundRecord,'@')+1))<VISIT_CACHE_TIME)
			return true;//������һ�η���ʱ��С��VISIT_CACHE_TIME���򲻸��»���
		else 
		{
			$cachedIP=str_replace($foundRecord,$ip.'@'.time(),$cachedIP);
			file_put_contents($cache_file,$cachedIP,LOCK_EX);//�������
			return false;
	  	}
	}	  
	else //û�������
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
	echo('ά���У����Ժ���ʡ� Maintainance mode now, please come here later.');
	exit(0);
}
?>
