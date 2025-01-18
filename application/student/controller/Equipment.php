<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/25
 * Time: 11:52
 */

namespace app\student\controller;

use think\Request;
use app\student\model\Equipment as newEquipment;

class Equipment extends MyController
{
    protected $equipment;

    public function __construct()
    {
        parent::__construct();
        $this->equipment = new newEquipment();
    }

    /**
     * 借出主页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $info = $this->equipment->getInfo();
        $this->assign('info', $info);
        return $this->fetch('index');
    }

    /**
     * 获取借出数据
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function  getLend()
    {
        $page = $this->equipment->getLendInfo($this->role);
        $pages = $page->render();
        $current = $page->currentPage();
        $total = $page->total();
        $list = $page->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('info', $page);
        $this->assign('page', $pages);
        return $this->fetch('equipment');
    }

    /**
     * 借出操作
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lend()
    {
        $param = Request::instance()->param();
        if(isset($param['equipment_id']) && !empty($param['equipment_id']) && !empty($param['equipment_id'][0])) {
            $result = $this->equipment->lend($this->role, $param);
            if($result == 3) {
                $this->error('设备已借出，请勿重复提交');
            }

            if($result == 4) {
                $this->error('归还时间错误，请重新选择');
            }

            if($result == 1) {
                $this->success('借出设备成功','equipment/getLend');
            } else {
                $this->error('出错，请重新选择');
            }
        } else {
            $this->error('没有选择设备');
        }
    }

    /**
     * 归还操作
     * @param $id integer 设备ID
     */
    public function revert($id)
    {
        $result = $this->equipment->revert($id);
        if($result){
            //设置成功后跳转页面的地址，默认的返回页面是$_SERVER['HTTP_REFERER']
            $this->success('归还成功', 'Equipment/getLend');
        } else {
            //错误页面的默认跳转页面是返回前一页，通常不需要设置
            $this->error('归还失败', 'Equipment/getLend');
        }
    }

    /**
     * 设备搜索
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $param = Request::instance()->param();
        $page = $this->equipment->getSearchInfo($param['equip'], $this->role);
        $pages = $page->render();
        $current = $page->currentPage();
        $total = $page->total();
        $list = $page->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('info', $page);
        $this->assign('page', $pages);

        return $this->fetch('equipment');
    }
}
