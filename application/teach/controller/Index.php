<?php
/**
 * User: hejun
 * Date: 2019/3/18
 * Time: 11:43
*/
namespace app\teach\controller;
use think\Controller;
use think\Request;
use think\Session;
use app\teach\model\Course as dbCourse;

class Index extends Base
{

	public function _initialize() {
        $this->dbCourse = new dbCourse();
    }

    public function index() {
        $map = $this->dbCourse->get_term();
        $map['c.teacherid'] = $this->info['id'];
        $map['c.status'] = 1;
        $re = $this->dbCourse->mycourse($map,50);
        $list = $re->items();
        foreach ($list as $k => $v) {
            $list[$k]['cnt'] = $this->dbCourse->get_choose_cnt($v['id']);
        }
        $this->assign('list',$list);
        $this->assign('course_cnt',$re->total());
        return $this->fetch();
    }

    public function logout() {
        if (Session::has('role'))   Session::delete('role');
        if (Session::has('id'))   Session::delete('id');
        $this->redirect('/');
    }

    public function upload() {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $path =  $info->getSaveName();
                $path = 'uploads' . DS . $path;
                return $path;
            } else {
                // 上传失败获取错误信息
                return $file->getError();
            }
        }
    }
}