<?php

namespace App\Http\Controllers;

use App\Libraries\EncryptUtil;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function getHttpIp(){
        if(empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['REMOTE_ADDR'];
        }else{
            $str=$_SERVER['HTTP_X_FORWARDED_FOR'];
            $Regex='#([^,]+)#is';
            preg_match($Regex,$str,$result);
            $ip=$result[1];
        }
        return $ip;
    }
    public function jsonRertun($code,$msg,$data){
        $datas['code']=$code;
        $datas['msg']=$msg;
        $datas['data']=$data;
        return response()->json($datas);
    }
    //冒泡排序
    public function sortarr($arr){
        for($i=0;$i<count($arr)-1;$i++){
            for($j=0;$j<count($arr)-1-$i;$j++){
                if($arr[$j]->id>$arr[$j+1]->id){
                    $k=$arr[$j];
                    $arr[$j]=$arr[$j+1];
                    $arr[$j+1]=$k;
                }
            }
        }
        return $arr;
    }
    //加密.
    public function encrypts($text){
        $name='访客'.str_replace('.','',$_SERVER['REMOTE_ADDR']);
        return EncryptUtil::encrypt($text);
    }
    //生成无限极分类树
    function make_tree($arr){
        $refer = array();
        $tree = array();
        foreach($arr as $k => $v){
            $refer[$v['id']] = & $arr[$k];  //创建主键的数组引用
        }
        foreach($arr as $k => $v){
            $pid = $v['father_id'];   //获取当前分类的父级id
            if($pid == 0){
                $tree[] = & $arr[$k];   //顶级栏目
            }else{
                if(isset($refer[$pid])){
                    $refer[$pid]['children'][] = & $arr[$k];  //如果存在父级栏目，则添加进父级栏目的子栏目数组中
                }
            }
        }
        return $tree;
    }
}
