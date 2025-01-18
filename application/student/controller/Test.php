<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/23
 * Time: 15:19
 */

namespace app\student\controller;

use app\student\model\StudentOption;
use app\teach\model\TestPaper;
use think\Request;
use app\student\model\Test as newTest;

class Test extends MyController
{
    protected $test;

    public function __construct()
    {
        parent::__construct();
        $this->test = new newTest();
    }

    /**
     * 获取试卷列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //20200111临时增加补丁,校验自己报名的课程是否已经有考试记录(test表),没有则补充上
        $this->autoFillStudentCourseTest();
        //////////////////////////
        //TODO 获取试卷列表
        $info = $this->test->getTestList();
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();


        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('list', $info);
        return $this->fetch('index');
    }

    /**
     * 学生已经选课的，在test表中没有记录的需要补充上学生关联试卷的记录，否则看不到试卷
     */
    private function autoFillStudentCourseTest()
    {
        //--1.获取学生的选课信息
        $courses = StudentOption::getStudentOption($this->studentId);
        if (empty($courses)) return;
        $courseIds = array_unique(array_column($courses, 'course_id'));

        //--2.根据课程查找试卷
        $testPapers = TestPaper::getListByCourseIds($courseIds);
        if (empty($testPapers)) return;
        $testPaperIds = array_unique(array_column($testPapers, 'id'));

        //--3.获取已经关联的试卷记录
        $studentTestList = \app\teach\model\Test::getListByStudentID($this->studentId);
        $existsPaperIds = [];
        if (!empty($studentTestList)) {
            $existsPaperIds = array_unique(array_column($studentTestList, 'paper_id'));
        }

        //--4.补充缺失的试卷记录
        $diffPaperIds = array_diff($testPaperIds, $existsPaperIds);
        if (empty($diffPaperIds)) return;

        $insertData = [];
        foreach ($diffPaperIds as $paperID) {
            $insertData[] = [
                'student_id' => $this->studentId,
                'paper_id' => $paperID,
                'status' => 1,
            ];
        }
        \app\teach\model\Test::insertAll($insertData);
    }


    /**
     * 试卷题目信息
     * @param $id int 试卷ID
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function paper($id)
    {
        //TODO 获取单个试卷信息
        $paper = $this->test->getPaper($id);//获取试卷信息
        if ($paper['status'] == 2) {
            $this->redirect('student/test/testResult', ['id' => $id]);
        }
        $subject = $this->test->getSubject($paper['praxis']);//获取题目信息

        ksort($subject);
        $this->assign('paper', $paper);
        $this->assign('subject', $subject);
        return $this->fetch('paper');
    }

    /**
     * 保存答题信息
     * @param $id int 试卷ID
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function saveAnswer($id)
    {
        $param = Request::instance()->param();

        if (!empty($param) && isset($param)) {
            $result = $this->test->saveAnswer($param, $id);
            if ($result) {
                $this->redirect('test/testResult', ['id' => $id], 3, '提交成功');
            } else {
                $this->error('提交失败');
            }
        }
    }

    /**
     * 测试结果
     * @param $id int 试卷ID
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function testResult($id)
    {
        $result = $this->test->getResult($id);
        $praxis = json_decode($result['praxis_box'], true);
        $subject = $this->test->getSubject($praxis);
        ksort($subject);
        $this->assign('paper', $result);
        $this->assign('subject', $subject);
        return $this->fetch('result');
    }

    /**
     * 课程搜索
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $param = Request::instance()->param();
        $info = $this->test->getSearchInfo($param['test']);
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('list', $info);
        return $this->fetch('index');
    }
}

