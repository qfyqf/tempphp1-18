<?php

namespace app\teach\model;

use think\Model;
use think\Db;

class Course extends Model
{
    protected $table = 'vr_course';

    // 获取单个课程信息
    public function getone($id)
    {
        return DB::table($this->table)->where('id', $id)->find();
    }

    // 分页获取课程列表
    public function getall($map, $cnt)
    {

        return DB::table($this->table)->alias('c')
            ->field("c.id, c.sid, c.name, c.credit, c.year, c.term, c.week, c.class_cycle, c.class_turn, c.address, t.name AS tname")
            ->where($map)
            ->join('vr_teacher t', 't.id=c.teacherid', 'LEFT')
            ->order('c.id asc')->paginate($cnt);

    }

    // 分页获取老师任课列表
    public function mycourse($map, $cnt)
    {
        return DB::table($this->table)->alias('c')
            ->field("c.id, c.sid, c.name, c.credit, c.year, c.term, c.week, c.class_cycle, c.class_turn, c.address, t.name AS tname")
            ->where($map)
            ->join('vr_teacher t', 't.id=c.teacherid', 'LEFT')
            ->order('c.id asc')->paginate($cnt);
    }

    // 获取老师任课列表
    public function mycourselist($teacherid)
    {
        $map['c.teacherid'] = $teacherid;
        $map['c.status'] = 1;
        return DB::table($this->table)->alias('c')
            ->field("c.id, c.sid, c.name, c.credit, c.year, c.term, c.week, c.class_cycle, c.class_turn, c.address, t.name AS tname")
            ->where($map)
            ->join('vr_teacher t', 't.id=c.teacherid', 'LEFT')
            ->order('c.id desc')->limit(100)->select();
    }

    // 分页获取尚未任课的课程列表
    public function unchoose($map, $cnt)
    {
        return DB::table($this->table)->where($map)->order('id desc')->paginate($cnt);
    }

    // 获取选择该课程的学生人数
    public function get_choose_cnt($cid)
    {
        $map['course_id'] = $cid;
        return DB::name('student_option')->where($map)->count();
    }

    // 获取选择该课程的学生列表
    public function get_choose_list($cid)
    {
        return DB::name('student_option')->alias('a')
            ->field("a.id, a.student_id, s.name, s.sid, s.email, s.sex, s.phone, s.address")
            ->where('course_id', $cid)
            ->join('student s', 's.id = a.student_id', 'LEFT')
            ->order('a.id desc')->limit(100)->select();
    }

    // 获取选择该老师任课的学生id集合
    public function get_stulist_by_teacher($tid)
    {
        $stuids = [];
        $tlist = $this->mycourselist($tid);
        foreach ($tlist as $v) {
            $slist = $this->get_choose_list($v['id']);
            foreach ($slist as $v) {
                if (!in_array($v['student_id'], $stuids)) {
                    $stuids[] = $v['student_id'];
                }
            }
        }
        return $stuids;
    }

    // 添加课程
    public function addone($data)
    {
        return DB::table($this->table)->insert($data);
    }

    // 修改课程
    public function updone($id, $data)
    {
        return DB::table($this->table)->where('id', $id)->update($data);
    }

    // 课程表
    public function course_table($teacherid, $op)
    {
        $map = ($op == 'next') ? $this->get_next_term() : $this->get_term();
        $map['teacherid'] = $teacherid;
        $width = 125; // 基础宽
        $height = 70; // 基础高

        $field = 'id, sid, name,credit, class_cycle, week, class_turn, address, teacherid';
        $courses = DB::name('course')->field($field)->where($map)->select();

        foreach ($courses as $ke => $va) {
            $class_start = substr($va['class_turn'], 0, 1);
            $class_end = substr($va['class_turn'], -1, 1);
            $courses[$ke]['css'] = 'width:' . $width . 'px; height:' . (($class_end - $class_start + 1) * $height) . 'px; left:' . ($va['week'] * $width) . 'px; top:' . ($class_start * $height) . 'px; padding-top:' . ((($class_end - $class_start + 1) * $height - 75) / 2) . 'px;';
        }

        return $courses;
    }

    // 获取当前学期
    public function get_term()
    {
        $month = date('m');
        if ($month >= 9) {
            $data['year'] = date('Y');
            $data['term'] = 1;
        } else {
            $data['year'] = date('Y') - 1;
            $data['term'] = 2;
        }
        return $data;
    }

    // 获取下一学期（待选课学期）
    public function get_next_term()
    {
        $month = date('m');
        $data['year'] = date('Y');
        if ($month >= 9)
            $data['term'] = 2;
        else
            $data['term'] = 1;
        return $data;
    }

    /**
     * 获取指定老师的课程
     * @param int $teacherID
     * @param array $limitStatus
     * @return array
     */
    public static function getListByTeacherID(int $teacherID, array $limitStatus = []): array
    {
        $where = [
            ' teacherid ' => $teacherID,
        ];
        if (!empty($limitStatus)) {
            $where['status'] = ['IN', $limitStatus];
        }
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 获取
     * @param array $courseIds
     * @return array
     */
    public static function getListByCourseIds(array $courseIds): array
    {
        $where = [];
        $where['id'] = ['in', $courseIds];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 获取课程信息
     * @param int $ID
     * @return array
     */
    public static function getInfo(int $ID): array
    {
        $where = [
            'id' => $ID,
        ];
        $result = self::where($where)->find();
        return !empty($result) ? $result->toArray() : [];
    }

}