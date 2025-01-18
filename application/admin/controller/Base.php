<?php
/**
 * User: hejun
 * Date: 2019/5/25
 * Time: 11:43
*/
namespace app\admin\controller;
use think\Controller;
use think\Session;
use think\Request;
use think\Db;
use app\teach\model\Teacher;

class Base extends Controller
{

    protected $info = []; // 角色信息

	public function __construct(){
        parent::__construct();

        // 登录状态信息
        $ses = session::get();
        if (isset($ses['role']) && $ses['role']==2 && isset($ses['id'])) {
            $info = DB::name('admin')->where('id',$ses['id'])->find();
            $this->info = $info;
            $this->assign('info',$info);
        }else{
            $this->redirect('/');
        }

        // 侧边栏导航列表
        $c = Request::instance()->controller();
        $this->assign('sidelist',config('sidelist'));

        // 主导航标志
        $this->assign('sign_nav',$c);


	}

}