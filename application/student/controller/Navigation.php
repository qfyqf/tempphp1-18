<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/21
 * Time: 17:59
 */

namespace app\student\controller;


class Navigation extends MyController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 导航页面
     * @return \think\response\View
     */
    public function index()
    {
        return view('index');
    }

}

