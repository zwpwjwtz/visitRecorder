<?php
//This is a simple example of using the script

//'include' or 'require' the php file, then you can freely use these functions.
include 'countVisit.php';

//By default, you may use Visit_countVisit() to analyze current visitor and record the ip if needed.
Visit_countVisit();

//If you only want to know whether an ip is cached or not, use Visit_isCached() which return an BOOL.
//$ip='127.0.0.1';
//if (Visit_isCached($ip)) echo $ip.' has visited.';
//else echo $ip.' has not visited yet.';

//To split a log file into parts manually, use Visit_correctFileSize([Filename]).
//Splitted files will be restored in VISIT_LOG_PATH.

//Also, you can simply call Visit_maintenance() to interrupt the execution of php, which simply display a message and exit.
//This is sometimes useful in debugging.
//Visit_maintenance();

?>