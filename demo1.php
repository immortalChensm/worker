<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2018/1/10
 * Time: 21:53
 */

require_once 'vendor/autoload.php';

$worker = new \Workerman\Worker("http://127.0.0.1:1234");
$worker->count=4;

$worker->onMessage=function ($connection,$data){
    print_r($_POST);
    $connection->send("hello,world");
};

\Workerman\Worker::runAll();