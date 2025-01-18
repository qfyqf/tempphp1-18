<?php

namespace app\teach\model;

use think\Model;
use think\Db;

class Experiment extends Model
{
    protected $table = 'vr_experiment';

    // 添加一个实验
    public function addone($data)
    {
        return DB::table($this->table)->insert($data);
    }

    // 修改一个实验
    public function updone($id, $data)
    {
        return DB::table($this->table)->where('id', $id)->update($data);
    }

    // 获取单个实验
    public function getone($id)
    {
        return DB::table($this->table)->alias('e')
            ->field('e.id, e.sid, e.course_id, e.name, e.type,  e.demand,  e.word, e.teacher_id, e.experiment, e.option_strs, e.option_imgs, e.score_rule, t.name AS teacher_name, e.createtime, c.name AS course_name, c.year, c.term')
            ->join('vr_course c', 'c.id = e.course_id', 'LEFT')
            ->join('vr_teacher t', 't.id = e.teacher_id', 'LEFT')
            ->where('e.id', $id)->find();
    }

    // 分页获取我的实验列表
    public function getall($where, $cnt = 10)
    {
        return DB::table($this->table)->alias('e')
            ->field('e.id, e.sid, e.course_id, e.name, e.type, e.teacher_id, t.name AS teacher_name, e.createtime, c.name AS course_name')
            ->join('vr_course c', 'c.id = e.course_id', 'LEFT')
            ->join('vr_teacher t', 't.id = e.teacher_id', 'LEFT')
            ->where($where)
            ->order('e.id desc')->paginate($cnt);
    }


    // 分页获取安排实验列表
    public function get_arrage_list($where, $cnt = 10)
    {
        return DB::name('experiment_arrange')->alias('a')
            ->field('a.id, a.sid, a.teacher_id, a.experiment_id, a.is_must, a.start_time, a.end_time, a.address, a.create_time, e.name AS ename, c.name AS cname, t.name AS tname')
            ->join('vr_experiment e', 'e.id = a.experiment_id')
            ->join('vr_course c', 'c.id = e.course_id')
            ->join('vr_teacher t', 't.id = a.teacher_id')
            ->where($where)->order('a.start_time desc')
            ->paginate($cnt);
    }

    // 获取单个实验布置
    public function get_arrage($id)
    {
        return DB::name('experiment_arrange')->alias('a')
            ->field('a.id, a.sid, a.teacher_id, a.experiment_id, a.is_must, a.start_time, a.end_time, a.remark, a.address, a.create_time, e.type, e.name AS ename, c.name AS cname, t.name AS tname')
            ->join('vr_experiment e', 'e.id = a.experiment_id')
            ->join('vr_course c', 'c.id = e.course_id')
            ->join('vr_teacher t', 't.id = a.teacher_id')
            ->where('a.id', $id)->find();
    }

    // 修改一个实验布置
    public function upd_arrage($id, $data)
    {
        return DB::name('experiment_arrange')->where('id', $id)->update($data);
    }


    // 分页获取实验结果列表
    public function experiment_result_list($id, $cnt = 10)
    {
        $ob = DB::name('experiment_result')->alias('a')
            ->field('a.id, a.student_id, a.arrange_id, a.grade, a.duration, a.create_time, a.done_time, a.status, s.name AS sname')
            ->join('vr_student s', 's.id = a.student_id', 'left')
            ->where('a.arrange_id', $id);

        if (!$cnt) {
            return $ob->select();
        } else {
            return $ob->paginate($cnt);
        }

    }

    /*
        实验布置方法 arrage()
        参数:
            $expe_id    实验id
            $data       实验布置数据array
     */
    public function arrage($expe_id, $data)
    {
        $experiment = $this->getone($expe_id);
        $stus = DB::name('student_option')->where('course_id', $experiment['course_id'])->column('student_id');
        $arrange_id = DB::name('experiment_arrange')->insertGetId($data);
        foreach ($stus as $v) {
            $result['student_id'] = $v;
            $result['arrange_id'] = $arrange_id;
            $result['create_time'] = $data['create_time'];
            DB::name('experiment_result')->insert($result);
        }
    }


    /**
     * 批量获取实验信息
     * @param array $experimentIds
     * @return array
     */
    public static function getListByIds(array $experimentIds): array
    {
        $where = [];
        $where['id'] = ['in', $experimentIds];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

}