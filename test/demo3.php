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


$fd = fopen("demo3.php","r");
echo !$fd;
print_r(debug_backtrace());