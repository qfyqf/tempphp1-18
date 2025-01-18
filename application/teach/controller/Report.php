<?php
/**
 * User: hejun
 * Date: 2019/3/25
 * Time: 11:43
*/
namespace app\teach\controller;
use app\admin\model\Admin;
use app\teach\model\Teacher;
use think\Controller;
use think\Db;
use think\Log;
use think\Request;
use app\teach\model\Teacher as dbTeacher;
use think\Validate;


class Report extends Base
{

    public function index()
    {
        $title = ['name' => '实验成果信息', 'en'=>'My Info'];
        // 副导航标志
        $this->assign('sign_sidenav','user/index');
        $this->assign('title',$title);
        $map=[];
        if ($ss = Request::instance()->param('ss')) {
            $map['s.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $field = 'a.*,s.name,s.sid';
        $list = DB::name('report')->alias('a')
            ->field($field)
            ->where($map)
            ->join('student s', 's.id=a.sid', 'LEFT')
            ->order('a.id desc')
            ->paginate(10);
        $header = !isset($list[0]) ? [] : json_decode($list[0]['data'],true);
        $this->assign('header', $header);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function detail()
    {
        $id = $this->request->get('id');
        $model = DB::name('report')->alias('a')
            ->where(['id' => $id])
            ->order('id desc')
            ->find();
        $user = $this->getUserData($model['sid']);
        if (empty($model)) {
            return $this->error('数据不存在');
        }
        $model['data'] = json_decode($model['data'], true);
        $title = ['name' => '报告详情', 'en' => 'Report Info'];
        // 副导航标志
        $this->assign('sign_sidenav', 'report/index');
        $this->assign('title', $title);
        $this->assign('model', $model);
        $this->assign('user', $user);
        return $this->fetch();
    }


    public function edit(){
        $data = $this->request->param();
        $validate = new Validate([
            'id'=>'require',
            't_score'=>'require|integer|<=:100|>=:0',
        ],[
            't_score.require'=>'评分不能为空',
            't_score.integer'=>'评分必须是整数',
            't_score.>='=>'评分必须>=0',
            't_score.<='=>'评分必须<1=00',
        ]);
        if(!$validate->check($data)){
            return $this->error($validate->getError());
        }
        Db::name('report')->where(['id'=>$data['id']])->update($data);
        return $this->success('保存成功');
    }
    public function del(){
        $id = $this->request->get('id');
        $res = Db::name('report')->where(['id'=>$id])->delete();
        return empty($res) ? $this->error('删除失败') : $this->success('删除成功');
    }

    public function getUserData($id)
    {
        $stu = DB::name('student')->field('id, sid, name, account, password,classID')->where('id', $id)->find();
        $teacher = DB::name('teacher_class')->field('*')->where('class_id', $stu['classID'])->find();
        $teacher = DB::name('teacher')->field('*')->where('id', $teacher['teacher_id'])->find();
        $stu['teacher'] = $teacher['name'];
        $class = DB::name('classinfo')->field('*')->where('id', $stu['classID'])->find();
        $stu = array_merge($stu, $class);
        return $stu;
    }
}