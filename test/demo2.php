<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2019/7/10
 * Time: 23:07
 */

$file = "demo.php";

$fobj = fopen($file,"r");
$stat = fstat($fobj);

fclose($fobj);
//print_r($stat);
echo $stat['mode'];//10666  0170000 &0170000
//011
//011
//011

//000
//001
//001 000 011 011 011

//001 111 000 000 000 000
//001 000 000 000 000
//100000