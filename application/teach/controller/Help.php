<?php
/**
 * User: hejun
 * Date: 2019/4/18
 * Time: 12:00
*/
namespace app\teach\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;

class Help extends Base
{
    protected $dbEquipment;

    public function _initialize() {
        
    }

    // 学生端文档列表
    public function index() {
        $title = ['name' => '帮助文档', 'en'=>'Help'];
        $where = ['status'=>1,'type'=>0, 'pid'=>0];   
        $list = DB::name('help')->where($where)->order('orderid asc, id asc')->select();

        foreach ($list as $k => $v) {
            $where['pid'] = $v['id'];
            $list[$k]['sublist'] = DB::name('help')->where($where)->order('orderid asc, id asc')->select();
        }

        $this->assign('list',$list);
        $this->assign('title',$title);
        return $this->fetch();
    }

    // 教师端文档列表
    public function teacher() {
        $title = ['name' => '帮助文档', 'en'=>'Help'];
        $where = ['status'=>1,'type'=>1, 'pid'=>0];   
        $list = DB::name('help')->where($where)->order('orderid asc, id asc')->select();

        foreach ($list as $k => $v) {
            $where['pid'] = $v['id'];
            $list[$k]['sublist'] = DB::name('help')->where($where)->order('orderid asc, id asc')->select();
        }

        $this->assign('list',$list);
        $this->assign('title',$title);
        return $this->fetch();
    }

    // 添加
    public function add(){
        $title = ['name' => '添加文档', 'en'=>'New Help'];

        $pids = DB::name('help')
            ->field('id, title')
            ->where(['pid'=>0, 'type'=>0, 'status'=>1])
            ->order('orderid asc')->select();

        $maxid = DB::name('help')
            ->order('id desc')
            ->limit(1)
            ->value('id');

        $maxid = ($maxid+1)*10;

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();
            if (DB::name('help')->insert($params)) {
                if ($params['type']) 
                    $this->success('修改成功','help/teacher');
                else
                    $this->success('修改成功','help/index');
            }else{
                $this->error('添加失败');
            }
        }

        $this->assign('title',$title);
        $this->assign('pids',$pids);
        $this->assign('maxid',$maxid);
        return $this->fetch();
    }

    // 修改
    public function upd(){
        $title = ['name' => '修改文档', 'en'=>'update Help'];
        $id = Request::instance()->param('id');
        $item = DB::name('help')
            ->where(['id'=>$id, 'status'=>1])
            ->find();

        if (empty($item)) 
            $this->error('参数错误');

        $pids = DB::name('help')
            ->field('id, title')
            ->where(['pid'=>0, 'type'=>$item['type'], 'status'=>1])
            ->order('orderid asc')->select();

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();
            if (DB::name('help')->where('id', $id)->update($params)) {
                if ($params['type']) 
                    $this->success('修改成功','help/teacher');
                else
                    $this->success('修改成功','help/index');
                
            }else{
                $this->error('修改失败');
            }
        }

        $this->assign('title',$title);
        $this->assign('pids',$pids);
        $this->assign('item',$item);
        return $this->fetch();
    }

    // 根据类型获取父标题集合
    public function getpids() {
        $type = intval(Request::instance()->param('type'));
        $pids = DB::name('help')
            ->field('id, title')
            ->where(['pid'=>0, 'type'=>$type, 'status'=>1])
            ->order('orderid asc')->select();
        return json_encode($pids, JSON_UNESCAPED_UNICODE);
    }

    

}