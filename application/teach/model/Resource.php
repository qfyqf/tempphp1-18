<?php

namespace app\teach\model;

use think\Model;
use think\Db;
class Resource extends Model
{
    // 分页列表
    public function get_list($where, $cnt=10) {
        return DB::name('resource')->alias('a')
            ->field('a.id, a.sid, a.name, a.type, a.desc, a.resource, a.auth, a.status, a.teacher_id, a.course_id, a.add_time, c.name AS cname, t.name As tname')
            ->join('vr_course c','c.id = a.course_id','left')
            ->join('vr_teacher t','t.id = a.teacher_id','left')
            ->where($where)->paginate($cnt);
    }

    // 获取单个
    public function get_one($id) {
        return DB::name('resource')->alias('a')
            ->field('a.id, a.sid, a.name, a.type, a.desc, a.resource, a.auth, a.status, a.teacher_id, a.course_id, a.add_time, c.name AS cname, t.name As tname')
            ->join('vr_course c','c.id = a.course_id','left')
            ->join('vr_teacher t','t.id = a.teacher_id','left')
            ->where('a.id', $id)->find();
    }

    // 添加资源
    public function add($params) {
        return DB::name('resource')->insert($params);
    }

}