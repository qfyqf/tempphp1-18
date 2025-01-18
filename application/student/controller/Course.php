<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 17:13
 */

namespace app\student\controller;

use app\student\model\Student;
use app\teach\model\TeacherClass;
use \think\Request;
use app\student\model\Course as newCourse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Course extends MyController
{
    protected $course;

    /**
     * 初始化相关资源
     * Course constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->course = new newCourse();
    }

    /** 选课主页面
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $studentInfo = Student::getInfo($this->studentId);
        if (empty($studentInfo)) $this->error('学生信息不存在');
        if ($studentInfo['classID'] <= 0) $this->error('请联系管理员，学生没有班级信息。');

        $teacherClassList = TeacherClass::getListByClassID($studentInfo['classID']);
        if (empty($teacherClassList)) $this->error('请联系管理员，学生的班级没有关联老师');
        $teacherIds = array_unique(array_column($teacherClassList, 'teacher_id'));
        $page = $this->course->getPage($teacherIds);//获取课程信息
        //渲染输出
        $pages = $page->render();
        $current = $page->currentPage();
        $total = $page->total();
        $list = $page->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('info', $page);
        $this->assign('page', $pages);
        return $this->fetch('course/index');
    }


    /**
     * 选课操作
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selectCourse()
    {
        //TODO 把学生ID和任课ID插入选课表
        $param = Request::instance()->param();//获取选课信息
        $courseIds=(array)($param['course'] ?? []);
        if(empty($courseIds)) $this->error('请选择课程');

        $result = $this->course->optionCourse($this->studentId, $courseIds);
        if ($result['error'] == 0) {
            //TODO 选课成功，获取课程信息，查看选课信息
            $optionInfo = $this->course->getOptionInfo($courseIds);
            $this->assign('info', $optionInfo);
            return $this->fetch('option');
        } else {
            //TODO 跳转至错误页面
            $this->error($result['msg']);
        }
    }

    /**
     * 得到学生已选全部课程信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOptionCourseInfo()
    {
        //TODO 得到学生已选全部课程信息
        $result = $this->course->getOptionCourseInfo($this->studentId);
        if ($result['error'] == 0) {
            $data = $result['data'];
            $pages = $data->render();
            $current = $data->currentPage();
            $total = $data->total();
            $list = $data->listRows();

            $this->assign('current', $current);
            $this->assign('total', $total);
            $this->assign('list', $list);
            $this->assign('info', $data);
            $this->assign('page', $pages);
            return $this->fetch('course');
        } else {
            //TODO 跳转至错误页面
            $this->error($result['msg']);
        }

    }

    /**
     * 选课信息导出
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function excel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $data = $this->course->getOptionCourseInfo($this->studentId);
        $title = ['课程编号', '课程名称', '学分', '上课时间', '上课地点', '开课单位', '任课教师', '状态'];
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
        foreach ($data['data'] as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['sid']);
            $sheet->setCellValue($icon[1] . strval($j), $value['name']);
            $sheet->getStyle($icon[1] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[2] . strval($j), $value['credit']);
            $sheet->getStyle($icon[2] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[3] . strval($j), '星期' . $value['week']);
            $sheet->getStyle($icon[3] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[4] . strval($j), $value['address']);
            $sheet->getStyle($icon[4] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[5] . strval($j), $value['unit']);
            $sheet->getStyle($icon[5] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[6] . strval($j), $value['tname']);
            $sheet->getStyle($icon[6] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[7] . strval($j), $value['status']);
            $j++;
        }
        unset($j);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="学生课程表.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * 课程表数据
     * @param $nowTerm
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function schedule($nowTerm = 1)
    {
        $info = $this->course->getCourseTime($this->studentId, $nowTerm);
        if ($nowTerm == 1) {
            $term = $this->course->getNowSchoolYear();
            $type = 1;
        } else {
            $term = $this->course->getSchoolYear();
            $type = 2;
        }
        $this->assign('schedule', $info['data']);
        $this->assign('term', $term);
        $this->assign('type', $type);
        return $this->fetch('schedule');
    }


    /**
     * 课程搜索
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $param = Request::instance()->param();
        $page = $this->course->getSearchInfo($param['course']);
        $pages = $page->render();
        $current = $page->currentPage();
        $total = $page->total();
        $list = $page->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('info', $page);
        $this->assign('page', $pages);

        return $this->fetch('course/index');
    }

    /**
     * 打印课表
     * @param $nowTerm
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function printTable($nowTerm = 1)
    {

        $info = $this->course->getCourseTime($this->studentId, $nowTerm);
        $this->assign('schedule', $info['data']);
        return $this->fetch('print');
    }
}
