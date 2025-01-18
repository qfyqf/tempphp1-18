<?php

namespace app\teach\model;

use think\Model;
use think\Db;
use app\teach\model\Course;

class Equipment extends Model
{
    protected $table = 'vr_equipment';

    // 获取设备列表
    public function getall($where)
    {
        return DB::table($this->table)->where($where)->select();
    }

    // 单个设备信息
    public function getone($id) {
        return DB::table($this->table)->where('id',$id)->find();
    }


    // 设备借出
    public function lendout($add)
    {
        $equipment = DB::name('equipment')->where('id', $add['equipment_id'])->find();
        if (empty($equipment) || $equipment['status']!=2)
            return false;

        DB::name('lending')->insert($add);
        DB::table($this->table)->where('id',$add['equipment_id'])->update(['status'=>1]);
    }

    // 设备归还
    public function reback($id, $role_id) {
        Db::startTrans();
        try {
            $lending = DB::name('lending')->where('id',$id)->find();
            if (empty($lending))
                return false;

            DB::name('equipment')->where('id',$lending['equipment_id'])->update(['status'=>2]);

            $map = ['id' => $id, 'role_id' => $role_id];
            DB::name('lending')->where($map)->update(['status'=>2, 'return_time'=>date('Y-m-d H:i:s')]);

            // 提交事务
            Db::commit();  
            return true;  
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }

    }

    //查看我的借出
    public function mylending($cnt=10, $role_id) {
        $map['role'] = 1;
        $map['role_id'] = $role_id;
        return DB::name('lending')->alias('a')
            ->field('a.id, a.equipment_id, a.role_id, a.lendingtime, a.returntime, a.return_time, a.reason, a.status, e.name')
            ->join('equipment e', 'e.id = a.equipment_id')
            ->where($map)
            ->order('lendingtime desc')->paginate($cnt);
    }

    //查看学生借出
    public function stulending($cnt=10, $teacher_id) {
        // 获取老师任课下面的学生集合
        $dbCourse = new course();
        $stuids = $dbCourse->get_stulist_by_teacher($teacher_id);

        $stuid = "(".implode(',', $stuids).")";

        $map['role'] = 0;
        $map['role_id'] = ['IN', $stuid];
        return DB::name('lending')->alias('a')
            ->field('a.id, a.equipment_id, a.role_id, a.lendingtime, a.returntime, a.return_time, a.reason, a.status, e.name, s.name AS sname')
            ->join('equipment e', 'e.id = a.equipment_id')
            ->join('student s', 's.id = a.role_id')
            ->where($map)
            ->order('lendingtime desc')->paginate($cnt);
    }



    
}