<?php
/**
 * User: hejun
 * Date: 2019/3/18
 * Time: 11:43
 */

namespace app\teach\controller;

use app\student\model\Student;
use app\teach\model\ClassInfo;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\teach\model\Teacher;
use app\teach\model\Course as dbCourse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Course extends Base
{
    protected $dbCourse;

    public function _initialize()
    {
        $this->dbCourse = new dbCourse();
    }

    // 课程列表
    public function index()
    {
        $map['c.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['c.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $list = $this->dbCourse->getall($map, 10);
        $this->assign('list', $list);

        // 副导航标志
        $this->assign('sign_sidenav', 'course/index');
        return $this->fetch();
    }

    // 课程列表 导出
    public function index_excel()
    {
        $table_name = '课程列表';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $map['c.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['c.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $data = $this->dbCourse->getall($map, 10)->items();

        $title = ['课程编号', '课程名称', '学分', '学年学期', '上课周次', '上课时间', '上课地点', '任课教师'];
        $icon = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($icon as $value) {
            $sheet->getColumnDimension($value)->setWidth(40);
        }

        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'name' => 'Arial',
                'bold' => true,
                'italic' => false,
                'strikethrough' => false,
                'color' => [
                    'argb' => '7DA7FF'
                ]
            ],
        ];

        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
            $sheet->getStyle($icon[$i] . '1')->applyFromArray($styleArray);
        }

        $j = 2;
        $style = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ];
        foreach ($data as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['sid']);
            $sheet->getStyle($icon[0] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[1] . strval($j), $value['name']);
            $sheet->getStyle($icon[1] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[2] . strval($j), $value['credit']);
            $sheet->getStyle($icon[2] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[3] . strval($j), $value['year'] . '-' . ($value['year'] + 1) . '学年 第' . $value['term'] . '学期');
            $sheet->getStyle($icon[3] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[4] . strval($j), '第' . $value['class_cycle'] . '周');
            $sheet->getStyle($icon[4] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[5] . strval($j), '星期' . str_replace(['1', '2', '3', '4', '5', '6', '7'], ['一', '二', '三', '四', '五', '六', '日'], $value['week']) . $value['class_turn'] . '节');
            $sheet->getStyle($icon[5] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[6] . strval($j), $value['address']);
            $sheet->getStyle($icon[6] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[7] . strval($j), $value['tname']);
            $sheet->getStyle($icon[7] . strval($j))->applyFromArray($style);
            $j++;
        }
        unset($j);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $table_name . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // 我任课的课程
    public function mycourse()
    {
        $op = Request::instance()->param('op');
        $op = empty($op) ? 'crt' : $op;

        // $map = $this->dbCourse->get_term();
        $map['c.teacherid'] = $this->info['id'];
        $map['c.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['c.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }

        $list = $this->dbCourse->mycourse($map, 5);
        $this->assign('list', $list);

        // 副导航标志
        $this->assign('sign_sidenav', 'course/mycourse');
        return $this->fetch();
    }

    // 我任课的课程 导出
    public function mycourse_excel()
    {
        $table_name = '我担任的课程';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $map = $this->dbCourse->get_term();
        $map['c.teacherid'] = $this->info['id'];
        $map['c.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['c.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }

        $data = $this->dbCourse->mycourse($map, 10)->items();

        $title = ['课程编号', '课程名称', '学分', '上课周次', '上课时间', '上课地点', '任课教师'];
        $icon = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
        }

        $j = 2;
        foreach ($data as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['sid']);
            $sheet->setCellValue($icon[1] . strval($j), $value['name']);
            $sheet->setCellValue($icon[2] . strval($j), $value['credit']);
            $sheet->setCellValue($icon[3] . strval($j), '第' . $value['class_cycle'] . '周');
            $sheet->setCellValue($icon[4] . strval($j), '星期' . str_replace(['1', '2', '3', '4', '5', '6', '7'], ['一', '二', '三', '四', '五', '六', '日'], $value['week']) . $value['class_turn'] . '节');
            $sheet->setCellValue($icon[5] . strval($j), $value['address']);
            $sheet->setCellValue($icon[6] . strval($j), $value['tname']);
            $j++;
        }
        unset($j);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $table_name . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // 选择该课程的学生列表
    public function stu_option_list()
    {
        $id = intval(Request::instance()->param('id'));
        $course = $this->dbCourse->getone($id);
        if (empty($course))
            $this->error('参数错误');

        $list = $this->dbCourse->get_choose_list($id);

        if (empty($list))
            $this->error('SORRY 暂时无人选择该门课程');

        $this->assign('title', ['name' => '选课学生', 'en' => 'Option Student']);
        $this->assign('course', $course);
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 课程选择，尚未任课的课程列表
    public function unchoose()
    {
        $map['teacherid'] = 0;
        $map['status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $list = $this->dbCourse->unchoose($map, 10);
        $this->assign('list', $list);

        // 副导航标志
        $this->assign('sign_sidenav', 'course/unchoose');
        return $this->fetch();
    }

    // 老师任课
    public function choose()
    {
        $id = Request::instance()->param('id');
        $item = $this->dbCourse->getone($id);
        if (empty($item)) {
            $this->error('课程不存在');
        } elseif ($item['teacherid'] > 0) {
            $this->error('已有老师任课');
        } else {
            if ($this->dbCourse->updone($id, ['teacherid' => $this->info['id']])) {
                $this->success('任课成功', 'course/mycourse');
            } else {
                $this->error('任课失败');
            }
        }
    }

    // 课程表
    public function course_table()
    {
        $title = ['name' => '教师课程表', 'en' => 'Teacher’s Schedule'];

        $op = Request::instance()->param('op');
        $op = empty($op) ? 'crt' : $op;

        $courses = $this->dbCourse->course_table($this->info['id'], $op);
        $term = $this->dbCourse->get_term();
        $next_term = $this->dbCourse->get_next_term();

        if ($op == 'next')
            $this->assign('term_name', $next_term['year'] . "-" . ($next_term['year'] + 1) . "学年 第" . $next_term['term'] . "学期");
        else
            $this->assign('term_name', $term['year'] . "-" . ($term['year'] + 1) . "学年 第" . $term['term'] . "学期");

        // 副导航标志
        $this->assign('sign_sidenav', 'course/course_table');
        $this->assign('courses', $courses);
        $this->assign('title', $title);
        $this->assign('op', $op);
        return $this->fetch();
    }


    public function ajaxadd()
    {
        $params = Request::instance()->param();
        if ($this->dbCourse->addone($params)) {
            echo '添加成功';
        } else {
            echo '添加失败';
        }
    }

    public function ajaxupd()
    {
        $params = Request::instance()->param();
        if ($this->dbCourse->updone($params['id'], $params)) {
            echo '修改成功';
        } else {
            echo '修改失败';
        }
    }

    /* 
     *  获取课程列表 
     *  参数 tid: teacher_id, 教师id
     *  参数 sk:  搜索关键词
     *  返回值：课程列表JSON
     */
    public function getCourseList()
    {
        $tid = Request::instance()->param('tid');
        $sk = Request::instance()->param('sk');
        $where = ' `status` = 1 ';
        if ($tid) $where .= ' AND `teacherid` = ' . $tid;
        if ($sk) $where .= ' AND `name` LIKE "%' . $sk . '%" ';
        $re = DB::name('course')->where($where)->select();

        if (empty($re)) {
            return false;
        } else {
            return json_encode($re, JSON_UNESCAPED_UNICODE);
        }
    }

    /* 添加课程表单 */
    public function add()
    {
        // 近期学年列表
        $year = '';
        for ($i = -2; $i <= 4; $i++) {
            $year .= '<option value="' . (date('Y') + $i) . '">' . (date('Y') + $i) . '-' . (date('Y') + $i + 1) . '学年</option>';
        }

        // 星期选择列表
        $week = '';
        for ($i = 1; $i <= 7; $i++) {
            $dx = str_replace([1, 2, 3, 4, 5, 6, 7], ['一', '二', '三', '四', '五', '六', '天'], $i);
            $week .= '<option value="' . $i . '">星期' . $dx . '</option>';
        }

        // 老师列表
        $teachers = '';
        $teacher = new Teacher();
        $arr = $teacher->getall();

        foreach ($arr as $v) {
            $teachers .= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }

        include("../application/teach/view/course/add.html");
    }
    /* 添加课程表单 END */

    /* 修改课程表单 */
    public function update()
    {
        $id = Request::instance()->param('id');
        $item = $this->dbCourse->getone($id);
        if (!$item) {
            echo '课程不存在';
            exit();
        }

        $item['teachername'] = $item['teacherid'] ? DB::name("teacher")->where('id', $item['teacherid'])->value('name') : '无';

        // 星期选择列表
        $week = '';
        for ($i = 1; $i <= 7; $i++) {
            $dx = str_replace([1, 2, 3, 4, 5, 6, 7], ['一', '二', '三', '四', '五', '六', '天'], $i);

            $week .= $item['week'] == $i ? '<option value="' . $i . '" selected>星期' . $dx . '</option>' : '<option value="' . $i . '">星期' . $dx . '</option>';
        }

        include("../application/teach/view/course/update.html");
    }

    /* 修改课程表单 END */

}