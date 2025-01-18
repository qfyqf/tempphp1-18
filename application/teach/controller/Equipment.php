<?php
/**
 * User: hejun
 * Date: 2019/3/27
 * Time: 12:00
*/
namespace app\teach\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\teach\model\Equipment as dbEquipment;

class Equipment extends Base
{
    protected $dbEquipment;

    public function _initialize() {
        $this->dbEquipment = new dbEquipment();
    }

    // 设备借出列表
    public function index() {
        $title = ['name' => '设备借出', 'en'=>'Equipment Lend'];
        $where = ['status'=>2];   
        $list = $this->dbEquipment->getall($where);
        // 副导航标志
        $this->assign('sign_sidenav','equipment/index');
        $this->assign('list',$list);
        $this->assign('title',$title);
        return $this->fetch();
    }

    // 借出设备
    public function lend() {
        Db::transaction(function(){
            $params = Request::instance()->param();
            if (!$params['equipment_id'])
                $this->error('请选择借出设备');

            if (strtotime($params['returntime']) <= time())
                $this->error('请填写正确的归还时间');
            $add = $params;
            $add['role'] = 1;
            $add['role_id'] = $this->info['id'];
            $add['lendingtime'] = date('Y-m-d H:i:s');
            $add['status'] = 1;

            $this->dbEquipment->lendout($add);
        });
        $this->success('借出成功','equipment/mylending');

    }

    // 我的借出记录
    public function mylending() {
        $list = $this->dbEquipment->mylending(10,$this->info['id']);

        // 副导航标志
        $this->assign('sign_sidenav','equipment/mylending');
        $this->assign('list',$list);
        return $this->fetch();
    }


    // 学生的借出记录
    public function stulending() {
        $list = $this->dbEquipment->stulending(10,$this->info['id']);

        // 副导航标志
        $this->assign('sign_sidenav','equipment/mylending');
        $this->assign('list',$list);
        return $this->fetch();
    }


    // 
    public function reback() {
        $id = intval(Request::instance()->param('id'));

        $item = $this->dbEquipment->getone($id);

        if (empty($item))
            $this->error('DATA ERROR');

        if ($this->dbEquipment->reback($id, $this->info['id'])) {
            $this->success('归还成功', 'equipment/mylending');
        }else{
            $this->error('归还失败');
        }
    }


    /* 
     *  获取设备列表 
     *  参数 sk:  搜索关键词
     *  返回值：设备列表JSON
     */
    public function getList(){
        $where = ['status'=>2];
        $sk = Request::instance()->param('sk');
        if ($sk)    
            $where['name'] = ['like','%'.$sk.'%'];

        $re = $this->dbEquipment->getall($where);
        if (empty($re)) {
            return false;
        }else{
            return json_encode($re, JSON_UNESCAPED_UNICODE);
        }
    }

}