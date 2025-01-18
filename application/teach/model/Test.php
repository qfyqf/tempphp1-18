<?php

namespace app\teach\model;

use think\Model;
use think\Db;

class Test extends Model
{
    protected $table = 'vr_test';
    protected $paper_field = 'a.id, a.name, a.duration, a.start_time, a.course_id, a.total_credit, c.name AS cname, t.name AS tname, a.praxis_box, a.end_time, a.create_time, a.status';


    /* 试卷 */

    // 分页获取试卷列表
    public function get_paper_list($where, $cnt)
    {
        return DB::name('test_paper')->alias('a')
            ->field($this->paper_field)
            ->join('vr_course c', 'c.id = a.course_id', 'left')
            ->join('vr_teacher t', 't.id = a.teacher_id', 'left')
            ->where($where)->paginate($cnt);
    }

    // 获取单个试卷
    public function get_paper($id)
    {
        return DB::name('test_paper')->alias('a')
            ->field($this->paper_field)
            ->join('vr_course c', 'c.id = a.course_id', 'left')
            ->join('vr_teacher t', 't.id = a.teacher_id', 'left')
            ->where('a.id', $id)->find();
    }

    /* 试题 */

    // 获取单个试题
    public function get_praxis($id)
    {
        return DB::name('praxis')->alias('a')
            ->field('a.id, a.knowledge_id, a.course_id, a.experiment_id, a.type, a.credit, a.difficult, a.title, a.content, a.options, a.answer, c.name AS cname, e.name AS ename')
            ->join('course c', 'c.id = a.course_id', 'LEFT')
            ->join('experiment e', 'e.id = a.experiment_id', 'LEFT')
            ->where(['a.id' => $id])
            ->find();
    }

    // 分页获取习题列表
    public function get_praxis_list($map)
    {
        return DB::name('praxis')->alias('a')
            ->field('a.id, a.knowledge_id, a.course_id, a.experiment_id, a.type, a.credit, a.difficult, a.title, c.name AS cname, e.name AS ename ')
            ->field('a.id, a.knowledge_id, a.course_id, a.experiment_id, a.type, a.credit, a.difficult, a.title, c.name AS cname')
            ->join('course c', 'c.id = a.course_id', 'LEFT')
            ->join('experiment e', 'e.id = a.experiment_id', 'LEFT')
            ->where($map)
            ->order('id desc')
            ->paginate(10, false, ['query' => request()->param()]);
    }

    // 修改习题
    public function upd_praxis($id, $data)
    {
        return DB::name('praxis')->where('id', $id)->update($data);
    }



    /* 知识点 */

    // 获取单个知识点
    public function get_knowledge($id)
    {
        return DB::name('knowledge')->where('id', $id)->find();
    }


    // 分页获取知识点列表
    public function get_knowledge_list($teacher_id)
    {
        return DB::name('knowledge')->alias('k')
            ->field('k.id, k.name, k.course_id, c.name AS course_name')
            ->join('vr_course c', 'c.id = k.course_id', 'LEFT')
            ->where('k.status', 1)
            ->where('k.teacher_id', $teacher_id)
            ->order('k.id desc')
            ->paginate(10);
    }

    // 获取课程下面的知识点列表
    public function get_knowledgelist_bycourse($course_id, $teacher_id)
    {

        $map['a.status'] = 1;
        $map['a.course_id'] = $course_id;
        $map['a.teacher_id'] = $teacher_id;
        return DB::name('knowledge')->alias('a')
            ->field('a.id, a.name, c.name AS cname')
            ->join('vr_course c', 'c.id = a.course_id', 'left')
            ->where($map)
            ->order('a.id desc')
            ->limit(100)
            ->select();
    }

    // 添加知识点
    public function add_knowledge($data)
    {
        return DB::name('knowledge')->insert($data);
    }

    // 修改知识点
    public function upd_knowledge($id, $data)
    {
        return DB::name('knowledge')->where('id', $id)->update($data);
    }



    /* 考试 */

    // 分页获取考试列表
    public function get_test_list($where, $cnt = 10)
    {
        $field = 'a.id, a.student_id, a.paper_id, a.testtime, a.grade, a.answer, s.name AS sname, p.name AS pname, p.start_time, p.end_time, c.name AS cname';
        $ob = DB::name('test')->alias('a')
            ->field($field)
            ->join('vr_student s', 's.id = a.student_id', 'left') // 学生
            ->join('vr_test_paper p', 'p.id = a.paper_id', 'left') // 试卷
            ->join('vr_course c', 'c.id = p.course_id', 'left')// 课程
            ->where($where);
        if (!$cnt)
            return $ob->select();
        else
            return $ob->paginate($cnt);
    }

    // 获取单个考试
    public function get_test($id)
    {

        $field = 'a.id, a.student_id, a.paper_id, a.testtime, a.grade, a.answer, a.status, s.name AS sname';
        return DB::name('test')->alias('a')->field($field)->join('vr_student s', 's.id = a.student_id', 'left')->where('a.id', $id)->find();
    }

    /**
     * 获取学生的关联试卷信息
     * @param int $studnetID
     * @return array
     */
    public static function getListByStudentID(int $studnetID): array
    {
        $where = [
            'student_id' => $studnetID,
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 获取试卷的考试记录
     * @param int $paperID
     * @param int $testStatus
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTestListByPaperID(int $paperID, int $testStatus = 0): array
    {
        $where = [
            'paper_id' => $paperID,
        ];
        if ($testStatus > 0) {
            $where['status'] = $testStatus;
        }
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 删除指定试卷的考试记录
     * @param int $paperID
     */
    public static function deleteTestByPaperID(int $paperID):void
    {
        $where=[
            'paper_id'=>$paperID,
        ];
        self::where($where)->delete();
    }


}