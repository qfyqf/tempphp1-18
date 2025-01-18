<?php
/**
 * User: hejun
 * Date: 2019/3/25
 * Time: 11:43
*/
namespace app\student\controller;
use app\admin\model\Admin;
use app\index\model\User;
use app\teach\model\Teacher;
use Spipu\Html2Pdf\Html2Pdf;
use think\Controller;
use think\Db;
use think\Log;
use think\Request;
use app\teach\model\Teacher as dbTeacher;
use think\Session;

class Report extends MyController
{

    public function index(){
        $id =Session::get('id');
        $user = $this->getUserData($id);
        $model = DB::name('report')->alias('a')
            ->where(['sid'=>$user['id']])
            ->find();
        if(empty($model)){
            return $this->error('数据不存在');
        }
        $model['data']=json_decode($model['data'],true);
        $title = ['name' => '报告详情', 'en'=>'Report Info'];
        // 副导航标志
        $this->assign('sign_sidenav','report/index');
        $this->assign('title',$title);
        $this->assign('model', $model);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function getUserData($id){
        $stu = DB::name('student')->field('id, sid, name, account, password,classID')->where('id',$id)->find();
        $teacher =  DB::name('teacher_class')->field('*')->where('class_id',$stu['classID'])->find();
        $teacher =  DB::name('teacher')->field('*')->where('id',$teacher['teacher_id'])->find();
        $stu['teacher']=$teacher['name'];
        $class =  DB::name('classinfo')->field('*')->where('id',$stu['classID'])->find();
        unset($class['id']);
        $stu = array_merge($stu,$class);
        return $stu;
    }

}