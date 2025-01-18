<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/25
 * Time: 11:52
 */

namespace app\student\model;


use think\Db;
use think\Exception;

class Equipment extends MyModel
{

    protected $name = 'equipment';

    /**
     * 获取借出设备信息
     * @return false|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo()
    {
        $info  = Db::name($this->name)
            ->where('status',2)
            ->select();
        return $info;
    }

    /**
     * 借出操作
     * @param $role integer 角色，0，学生；1，老师
     * @param $param array 设备信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lend($role,$param)
    {
        $equip = $param['equipment_id'];
        $data = [];
        $equip_id = [];
        foreach ($equip as $key => $value) {
            if (!empty($value)) {
                $equip_id[] = $value;
                $status = Db::name($this->name)->where('id', $value)->value('status');
                if ($status == 1) {
                   return 3;
                }
                $data[$key]['equipment_id'] = $value;
                $data[$key]['role'] = $role;
                $data[$key]['returntime'] = $param['returntime'];
                $data[$key]['return_time'] = $param['returntime'];
                $data[$key]['reason'] = $param['reason'];
                $data[$key]['lendingtime'] = date('Y-m-d H:i:s');
                if($data[$key]['lendingtime'] >= $data[$key]['returntime']) {
                    return 4;
                }
                $data[$key]['status'] = 1;
                $data[$key]['role_id'] = $this->studentId;
            }
        }

        Db::startTrans();
        try {
            if (!empty($data) && !empty($equip_id)) {
                $result = Db::name('lending')->insertAll($data);
                Db::name($this->name)->where('id','IN',$equip_id)->update(['status'=>1]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }

        if (isset($result) && !empty($result)) {
           return 1;
        } else {
            return 0;
        }
    }

    /**
     * 获取借出设备资料
     * @param $role integer 角色，0，学生；1，老师
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getLendInfo($role)
    {
        $info = Db::name('lending')
            ->alias('l')
            ->join('equipment e','e.id = l.equipment_id')
            ->where('role', $role)
            ->where('role_id', $this->studentId)
            ->order('l.lendingtime')
            ->field('e.name,l.lendingtime,l.return_time,l.status,e.id as eid,l.id')
            ->paginate(10);
        return $info;
    }

    /**
     * 设备归还
     * @param $id integer 设备ID
     * @return bool
     */
    public function revert($id)
    {
        Db::startTrans();
        try {
            $equip = Db::name('lending')
                ->where('id', $id)
                ->value('equipment_id');
            Db::name('lending')->where('id', $id)->update(['status' => 2 ,'return_time' => date('Y-m-d H:i:s')]);
            Db::name($this->name)->where('id', $equip)->update(['status' => 2]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 搜索
     * @param $name string 设备名称
     * @param $role integer 角色，0，学生；1，老师
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($name, $role)
    {
        $info = Db::name('lending')
            ->alias('l')
            ->join('equipment e','e.id = l.equipment_id')
            ->where('role', $role)
            ->where('role_id', $this->studentId)
            ->where('e.name', 'LIKE', "%$name%")
            ->order('l.lendingtime')
            ->field('e.name,l.lendingtime,l.return_time,l.status,e.id as eid,l.id')
            ->paginate(10);
        return $info;
    }
}
