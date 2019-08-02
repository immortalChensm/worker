<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2019/7/11
 * Time: 14:33
 */

//echo sys_get_temp_dir();
//$b=[];
//$b['test']=[1];
//echo $b['test'][0];

//pcntl_signal(SIGALRM,function ($sig){
//    echo $sig.PHP_EOL;
//    echo time();
//});
//
//pcntl_alarm(2);
//
////while (1){
////    pcntl_alarm(3);
////}


//$fd = fopen("demo3.php","r");
//echo !$fd;
//print_r(debug_backtrace());
//
//$stat = fstat(STDOUT);
//print_r($stat);
//echo $stat['mode']&0170000;
//
//print_r(explode(':', "http://ssss:32532", 2));

$socket = stream_socket_server("tcp://127.0.0.1:80", $errno, $errstr);

$socket = socket_import_stream($socket);
socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
stream_set_blocking($socket, 0);
if (!$socket) {
    echo "$errstr ($errno)<br />\n";
} else {
    while ($conn = stream_socket_accept($socket)) {
        fwrite($conn, 'The local time is ' . date('n/j/Y g:i a') . "\n");
        fclose($conn);
    }
    fclose($socket);
}