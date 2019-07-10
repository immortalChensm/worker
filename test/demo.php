<?php
/**
 * Created by PhpStorm.
 * User: 1655664358@qq.com
 * Date: 2019/7/10
 * Time: 22:13
 */

class A{
    public $name;

    public function show($b)
    {
        echo 'show';
        print_r(debug_backtrace());
    }
}

$obj = new A();
//$id = spl_object_hash($obj);
//echo $id;

$obj->show($b);