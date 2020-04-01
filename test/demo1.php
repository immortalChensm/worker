<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2019/7/10
 * Time: 22:22
 */

$data = [
    'name'=>'test',
    'size'=>'http://www.baidu.com'
];

$data = http_build_query($data);
$options = array(
    'http' => array(
        'method' => 'POST',
        'header' => 'Content-type:application/x-www-form-urlencoded',
        'content' => $data
        //'timeout' => 60 * 60 // 超时时间（单位:s）
    )
);

$uri = "http://localhost/test.php";
$context = stream_context_create($options);
$result = file_get_contents($uri,false,$context);
echo $result;