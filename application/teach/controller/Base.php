<?php
/**
 * User: hejun
 * Date: 2019/3/18
 * Time: 11:43
*/
namespace app\teach\controller;
use app\teach\model\ClassInfo;
use app\teach\model\TeacherClass;
use think\Controller;
use think\Session;
use think\Request;
use think\DB;
use app\teach\model\Teacher;

class Base extends Controller
{

    protected $info = []; // 角色信息

	public function __construct(){
        parent::__construct();

        // 登录状态信息
        $ses = session::get();
        if (isset($ses['role']) && $ses['role']==1 && isset($ses['id'])) {
            //$teacher = Model('teacher');
            $info = Teacher::getInfo($ses['id']);
            $info['className']='';
            $info['classIds']=[];
            //获取教师的班级信息
            $teacherClassList=TeacherClass::getListByTeacherID($ses['id']);
            if(!empty($teacherClassList)){
                $classIds=array_unique(array_column($teacherClassList,'class_id'));
                if(!empty($classIds)){
                    $info['classIds']=$classIds;
                    $classList=ClassInfo::getListByIds($classIds);
                    if(!empty($classList)){
                        foreach ($classList as $class){
                            $info['className'].=$class['className'].',';
                        }
                    }
                    unset($teacherClassList,$classList);
                }
            }

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