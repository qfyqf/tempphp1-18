<?php

// 注册 ilab 实验空间 账号相关接口

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;

class Regilab extends Controller
{


    public function index()
    {
        $nickname = DB::name('names')->orderRaw('rand()')->limit(1)->find(); 
        $nickname = trim($nickname['name'],"&ensp; ");
        if (strlen($nickname)<=2) {
            $nickname = $nickname.str_pad(rand(0,99999999), 8, '0', STR_PAD_LEFT);
            $password = $nickname.str_pad(rand(0,99999999), 8, '0', STR_PAD_LEFT);
        }elseif (strlen($nickname)<=4) {
            $nickname = $nickname.str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);
            $password = $nickname.str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);
        }elseif (strlen($nickname)<=6) {
            $nickname = $nickname.str_pad(rand(0,9999), 4, '0', STR_PAD_LEFT);
            $password = $nickname.str_pad(rand(0,9999), 4, '0', STR_PAD_LEFT);
        }else{
            $password = $nickname.str_pad(rand(0,99), 2, '0', STR_PAD_LEFT);
        }

        $nickname = ucfirst($nickname);
        echo $nickname.'|'.$password;
        exit;
    }


    public function reginfo() {
        $param = Request::instance()->param();

        $data = array(
            'phone' => $param['phone'],
            'nickname' => $param['nickname'],
            'password' => $param['password'],
            'createtime' => date('Y-m-d H:i:s')
        );

        if (DB::name('account_ilab')->insert($data)) {
            echo 1;
        }else{
            echo 0;
        }

        exit;
    }





}
