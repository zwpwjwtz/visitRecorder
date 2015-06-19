<?php
//This is a simple example of using the script

//'include' or 'require' the php file, then you can freely use these functions.
include 'countVisit.php';

//By default, you may use countVisit() to analyze current visitor and record the ip if needed.
countVisit();

//If you only want to know whether an ip is cached or not, use isCached() which return an BOOL.
//$ip='127.0.0.1';
//if (isCached($ip)) echo $ip.' has visited.';
//else echo $ip.' has not visited yet.';

//Also, you can simply call maintenance() to interrupt the execution of php, which simply display a message and exit.
//This is sometimes useful in debugging.
//maintenance();

?>