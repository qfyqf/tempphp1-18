<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/19
 * Time: 16:28
 */

namespace app\student\model;

use think\Db;

class Course extends MyModel
{
    protected $name = 'course';

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    /**
     * 获取学年
     * @return array
     */
    public function getSchoolYear()
    {
        $year = date('Y');
        $month = date('n');
        if (intval($month) < 9) {
            $term = 1;
        } else {
            $term = 2;
        }

        $map = [];
        $map['year'] = $year;
        $map['term'] = $term;

        return $map;
    }

    /**
     * 获取当前学年
     * @return array
     */
    public function getNowSchoolYear()
    {
        $year = date('Y', strtotime('-1 year'));
        $month = date('n');

        if (intval($month) < 9 || intval($month) > 1) {
            $term = 2;
        } else {
            $year++;
            $term = 1;
        }

        $map = [];
        $map['year'] = $year;
        $map['term'] = $term;

        return $map;
    }


    /**
     * 获取课程信息
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPage(array $teacherIds)
    {
        $map['a.year'] = ['IN', [date('Y') - 1, date('Y')]];
        $map['a.teacherid'] = ['IN', $teacherIds];
        $result = Db::name($this->name)
            ->alias('a')
            ->join('teacher b', 'b.id = a.teacherid')
            ->where($map)
            ->field('a.id, a.sid,a.name, b.name as tname, year, term, content,credit, 
            class_cycle, week,class_turn,a.address,unit')
            ->paginate(10);
        return $result;

    }

    /**
     * 选择课程
     * @param $student int 学生ID
     * @param $options array 所选课程代码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function optionCourse($student, $courseIds)
    {
        //TODO 选择课程处理流程
        //判断所选课程与已选课程是否重复，通过任课ID判定
        //上课时间是否冲突，先比较所选课程时间是否冲突，再比较所选课程与已选课程时间是否冲突
        //插入选课数据
        //TODO 判断时间是否冲突
        $msg = "";
        $error = 0;

        Db::startTrans();

        //获取所选课程信息
        $classTime = Db::name($this->name)
            ->where('id', 'IN', $courseIds)
            ->field('id,class_cycle,week,class_turn')//class_cycle上课周期,class_turn节次,week星期
            ->select();

        if (empty($classTime)) {
            $msg = "获取选课信息有误！！！";
            $error = 1;
            return $this->message($msg, $error);
        }
        /**
         * Array
         * (
         * [0] => Array
         * (
         * [id] => 3
         * [class_cycle] => 1-20
         * [week] => 1
         * [class_turn] => 89
         * )
         *
         * )
         */
        //格式化上课时间和周期
        $num = 0;
        foreach ($classTime as $key => &$value) {
            $num++;
            $value['class_cycle'] = $this->format($value['class_cycle'], '-');
            $value['class_turn'] = $this->format($value['class_turn'], ',');
        }

        //比较所选课程之间时间是否冲突
        $optionCourse = [];

        while ($end = end($classTime)) { //拿到数组最后一个元素
            if ($num > 1) {
                while ($prev = prev($classTime)) { //拿到前一个元素
                    $inter_cycle = array_intersect($end['class_cycle'], $prev['class_cycle']);
                    $inter_turn = array_intersect($end['class_turn'], $prev['class_turn']);
                    //如果星期相等，周期存在交集，节次存在交集则说明存在冲突
                    if ($end['week'] == $prev['week'] && !empty($inter_cycle) && !empty($inter_turn)) {
                        $msg = '所选课程时间存在冲突，请检查后再选课！！！';
                        $error = 1;
                        return $this->message($msg, $error);
                    }
                }
            }
            //比较完成后弹出最后一个元素
            $optionCourse[] = array_pop($classTime);
        }


        //比较所选课程与已选课程之间时间是否冲突
        //得到已选课程的时间
        $selected = []; //已选
        $optionId = []; //所选

        if ($error == 0) {
            $selectedTime = $this->getInfo($student)['data'];
            if (!is_array($selectedTime)) {
                $msg = "获取已选课程信息有误！！！";
                $error = 1;
                return $this->message($msg, $error);
            }

            if (isset($selectedTime) && !empty($selectedTime)) {
                foreach ($selectedTime as $key => &$info) {
                    $info['class_cycle'] = $this->format($info['class_cycle'], '-');
                    $info['class_turn'] = $this->format($info['class_turn'], ',');
                }

                foreach ($selectedTime as $key => $value) {
                    foreach ($optionCourse as $item => $time) {
                        $inter_cycle = array_intersect($value['class_cycle'], $time['class_cycle']);
                        $inter_turn = array_intersect($value['class_turn'], $time['class_turn']);
                        //如果星期相等，并且上课周期和上课节次都有交集，则冲突
                        if ($value['week'] == $time['week'] && !empty($inter_cycle) && !empty($inter_turn)) {
                            $msg = '所选课程时间和已选课程时间冲突！！！';
                            $error = 1;
                            return $this->message($msg, $error);
                        }
                        $optionId[] = $time['id'];

                    }
                }
                //exit;
            }
        }

        if ($error == 0) {
            //TODO 判断课程是否重复
            if (isset($selectedTime) && !empty($selectedTime)) {
                foreach ($selectedTime as $value) {
                    $selected[] = $value['id'];
                }

                if (!empty(array_intersect($selected, $optionId))) {
                    $msg = '课程重复！！！';
                    $error = 1;
                    return $this->message($msg, $error);
                }
            }
        }

        if ($error == 0) {
            foreach ($optionCourse as $value) {
                $data[] = ['student_id' => $student, 'course_id' => $value['id']];
            }
        }

        if (!empty($data)) {
            $number = Db::name('student_option')->insertAll($data);
            if ($number) {
                $msg = '选课成功！';
                $error = 0;
                Db::commit();
            } else {
                $msg = '选课失败！';
                $error = 1;
                Db::rollback();
            }
        }

        return $this->message($msg, $error);
    }

    /**
     * 获取选课信息
     * @param $options array 选课ID
     * @return false|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOptionInfo($courseIds)
    {
        $courseInfo = Db::name($this->name)
            ->where('id', 'IN', $courseIds)
            ->select();
        return $courseInfo;
    }

    /**
     * 获取已选课程信息
     * @param $student int 学生ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOptionCourseInfo($student)
    {
        $map = $this->getNowSchoolYear();
        $course_info = Db::name($this->name)
            ->alias('a')
            ->join('student_option b', 'a.id = b.course_id')
            ->join('teacher t', 'a.teacherid = t.id')
            ->where('b.student_id', 'EQ', $student)
            ->where($map)
            ->field('a.id,a.sid,a.name,a.credit,a.class_cycle,a.week,a.class_turn,a.address,a.unit,t.name as tname,a.status')
            ->paginate(7);
        return $this->result($course_info);
    }


    /**
     * 获取数据
     * @param $student
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($student)
    {
        $course_info = Db::name($this->name)
            ->alias('a')
            ->join('student_option b', 'a.id = b.course_id')
            ->join('teacher t', 'a.teacherid = t.id')
            ->where('b.student_id', 'EQ', $student)
            ->field('a.id,a.sid,a.name,a.credit,a.class_cycle,a.week,a.class_turn,a.address,a.unit,t.name as tname,a.status')
            ->select();

        return $this->result($course_info);
    }

    /**
     * 获取课程信息
     * @param $student
     * @param $nowTerm
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCourseTime($student, $nowTerm)
    {
        if ($nowTerm == 1) {
            $map = $this->getNowSchoolYear();
        } else {
            $map = $this->getSchoolYear();
        }
        $course_info = Db::name($this->name)
            ->alias('a')
            ->join('student_option b', 'a.id = b.course_id')
            ->join('teacher t', 't.id = a.teacherid')
            ->where('student_id', 'EQ', $student)
            ->where($map)
            ->field('a.name,class_cycle,week,class_turn,t.name as tname,a.address')
            ->select();

        if (isset($course_info) && !empty($course_info)) {
            foreach ($course_info as &$item) {
                $item['class_start'] = substr($item['class_turn'], 0, 1);
                $item['class_end'] = substr($item['class_turn'], -1, 1);
            }

            $schedule = [];
            for ($i = 0; $i < 12; $i++) {
                for ($j = 0; $j < 8; $j++) {
                    $schedule[$i][$j] = "&nbsp;";
                }
            }

            $schedule[0] = ["hidden", "hidden", "hidden", "hidden", "hidden", "hidden", "hidden", "hidden"];
            for ($j = 1; $j < 12; $j++) {
                $schedule[$j][0] = $j;
            }

            foreach ($course_info as $value) {
                $value['turn'] = $value['class_end'] - $value['class_start'] + 1;
                for ($i = $value['class_start'] + 1; $i <= $value['class_end']; $i++) {
                    $schedule[$i][$value['week']] = 'hidden';
                }
                $schedule[$value['class_start']][$value['week']] = $value;
            }
            return $this->message('获取课程表数据成功！', 0, $schedule);
        } else {
            return $this->message('获取课程表数据失败！', 1);
        }
    }

    /**
     * 通过课程名搜索课程
     * @param $course
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($course)
    {
        $map = $this->getSchoolYear();
        $info = Db::name($this->name)
            ->alias('a')
            ->join('teacher b', 'b.id = a.teacherid')
            ->where($map)
            ->where('a.name', 'LIKE', "%$course%")
            ->field('a.id, a.sid,a.name, b.name as tname, year, term, content,credit, 
            class_cycle, week,class_turn,a.address,unit')
            ->paginate(10);
        return $info;
    }

    /**
     * 格式化时间
     * @param $string string 字符串
     * @param $format string 标志
     * @return array
     */
    public function format($string, $format)
    {
        $result = explode($format, $string);
        $array = [];
        for ($i = intval($result[0]); $i <= $result[1]; $i++) {
            $array[] = $i;
        }
        return $array;
    }
}

