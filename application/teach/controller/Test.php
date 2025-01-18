<?php
/**
 * User: hejun
 * Date: 2019/3/22
 * Time: 17:30
 */

namespace app\teach\controller;

use app\teach\model\{
    TestPaper
};
use think\{
    Controller, Request, Session, Db, Log
};
use app\teach\model\Course;
use app\teach\model\Test AS dbTest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Test extends Base
{
    protected $dbCourse;
    protected $dbExperiment;

    public function _initialize()
    {
        $this->dbCourse = new Course();
        $this->dbTest = new dbTest();
    }

    // 试卷管理
    public function index()
    {
        $where = ['a.teacher_id' => $this->info['id']];
        $list = $this->dbTest->get_paper_list($where, 10);

        // 副导航标志
        $this->assign('sign_sidenav', 'test/index');
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 查看试卷
    public function showpaper()
    {
        $id = intval(Request::instance()->param('id'));
        $item = $this->dbTest->get_paper($id);

        $title = ['name' => $item['name'], 'en' => 'Paper Info'];

        $item['praxis_box'] = json_decode($item['praxis_box'], TRUE);
        if (empty($item['praxis_box']))
            $this->error('PRAXIS COUNT ERROR');

        // 取出试题
        $praxis_box = [];
        foreach ($item['praxis_box'] as $v) {
            $tmp = $this->dbTest->get_praxis($v);
            $tmp['options'] = json_decode($tmp['options'], TRUE);

            if ($tmp['type'] == 1) {
                $praxis_box['danxuan'][] = $tmp;
            } elseif ($tmp['type'] == 2) {
                $tmp['answer'] = json_decode($tmp['answer'], TRUE);
                $praxis_box['duoxuan'][] = $tmp;
            } elseif ($tmp['type'] == 3) {
                $praxis_box['panduan'][] = $tmp;
            }
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/index');
        $this->assign('item', $item);
        $this->assign('praxis_box', $praxis_box);
        $this->assign('title', $title);
        return $this->fetch();

    }

    //删除试卷
    public function deletePaper()
    {
        Db::startTrans();
        try {
            //获取试卷ID
            $paperID = intval(Request::instance()->param('paperid'));
            if ($paperID < 1) throw new \Exception('试卷ID参数错误', 20000);

            //校验试卷有效性
            $testPaperInfo = TestPaper::getInfo($paperID);
            if (empty($testPaperInfo)) throw new \Exception('试卷信息不存在', 20000);

            //校验是否有学生参加了本门考试
            $getTestList = \app\teach\model\Test::getTestListByPaperID($paperID, 2);
            if (!empty($getTestList)) throw new \Exception('删除失败,该试卷有学生考试记录', 20000);

            //删除试卷
            \app\teach\model\Test::deleteTestByPaperID($paperID);
            TestPaper::deleteTestPaper($paperID);
            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
            if ($e->getCode() == 20000) {
                $this->error($e->getMessage());
            } else {
                Log::error('异常文件：' . $e->getFile());
                Log::error('异常行号：' . $e->getLine());
                Log::error('异常信息：' . $e->getMessage());
                Log::error('异常代码：' . $e->getCode());
                Log::error('异常trace：' . var_export($e->getTrace(), true));
                $this->error('服务异常，请联系管理员');
            }
        }
        $this->success('删除成功', 'test/index');
    }

    // 知识点
    public function knowledge()
    {

        $list = $this->dbTest->get_knowledge_list($this->info['id']);

        $this->assign('list', $list);

        // 副导航标志
        $this->assign('sign_sidenav', 'test/knowledge');
        return $this->fetch();
    }

    // 添加知识点
    public function add_knowledge()
    {
        $title = ['name' => '添加知识点', 'en' => 'New knowledge'];
        $course_list = $this->dbCourse->mycourselist($this->info['id']);

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            $params['teacher_id'] = $this->info['id'];
            if (!$params['name'] || !$params['content'] || !$params['course_id']) $this->error('请将内容填完整');
            if ($this->dbTest->add_knowledge($params)) $this->success('添加成功', 'test/knowledge');
            else                                                                $this->error('添加失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/knowledge');
        $this->assign('course_list', $course_list);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 修改知识点
    public function upd_knowledge()
    {
        $id = intval(Request::instance()->param('id'));
        $item = $this->dbTest->get_knowledge($id);
        if (empty($item)) $this->error('知识点不存在');
        $course_list = $this->dbCourse->mycourselist($this->info['id']);

        if (Request::instance()->isPost()) {
            try {
                $params = Request::instance()->param();
                $name = trim($params['name']);
                $content = trim($params['content']);
                $courseID = intval($params['course_id']);

                if (empty($name)) throw new \Exception('请填写标题', 20000);
                if (empty($content)) throw new \Exception('请填写内容', 20000);
                if ($courseID < 1) throw new \Exception('请选择课程', 20000);

                Db::startTrans();
                //更新知识点
                $this->dbTest->upd_knowledge($id, $params);

                //课程变更，更新该知识点下面所有的试题的课程
                if ($item['course_id'] != $courseID) {
                    DB::table('vr_praxis')->where('knowledge_id', $id)->update(['course_id' => $courseID]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();;
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    $this->error('修改失败');
                }
            }
            $this->success('修改成功', 'test/knowledge');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/knowledge');
        $this->assign('course_list', $course_list);
        $this->assign('item', $item);
        return $this->fetch();
    }

    // 习题库
    public function praxis()
    {
        $map['c.teacherid'] = $this->info['id'];
        // 快照（标题）搜索
        if (input('get.name')) {
            $map['a.title'] = ['LIKE', '%' . input('get.name') . '%'];
        }

        if (input('get.co')) {
            $map['a.course_id'] = input('get.co');
        }

        if (input('get.ty')) {
            $map['a.type'] = input('get.ty');
        }

        if (input('get.di')) {
            $map['a.difficult'] = input('get.di');
        }

        $list = $this->dbTest->get_praxis_list($map);
        $course_list = $this->dbCourse->mycourselist($this->info['id']);

        // 副导航标志
        $this->assign('sign_sidenav', 'test/praxis');
        $this->assign('list', $list);
        $this->assign('course_list', $course_list);
        $this->assign('map', $map);
        return $this->fetch();
    }

    // 添加习题
    public function add_praxis()
    {
        $title = ['name' => '添加习题', 'en' => 'New Praxis'];
        $kid = intval(Request::instance()->param('kid'));//知识点
        $cid = intval(Request::instance()->param('cid'));//知识点所属的课程

        if ($kid < 1) $this->error('知识点ID参数错误');
        if ($cid < 1) $this->error('知识点所属的课程ID参数错误');

        // 所属课程
        $course = DB::name('course')->field('id, name')->where('id', $cid)->find();
        // 所属知识点
        $knowledge = DB::name('knowledge')->field('id, name')->where('id', $kid)->find();

        // 实验列表
        //$experiments = DB::name('experiment')->field('id, name')->where('course_id', $cid)->select();
        $experiments = DB::name('experiment')->field('id, name')->where('id', 1)->select();

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            if (!$params['content'])
                $this->error('请将内容填完整');

            $add['knowledge_id'] = intval($params['kid']);
            $add['course_id'] = intval($params['cid']);
            $add['experiment_id'] = intval($params['experiment_id']);
            $add['type'] = intval($params['type']);
            $add['credit'] = intval($params['credit']);
            $add['difficult'] = intval($params['difficult']);
            $add['title'] = $params['title'];
            $add['content'] = $params['content'];
            $add['options'] = '';


            // 根据题型，存储题目答案
            switch ($add['type']) {
                case 1: // 单选
                    $add['answer'] = $params['danxuan'];
                    $add['options'] = json_encode($params['op-danxuan'], JSON_UNESCAPED_UNICODE);
                    break;
                case 2: // 多选
                    if (!isset($params['duoxuan']))
                        $this->error('请选择正确答案选项');
                    $add['answer'] = json_encode($params['duoxuan']);
                    $add['options'] = json_encode($params['op-duoxuan'], JSON_UNESCAPED_UNICODE);
                    break;
                case 3: // 判断
                    $add['answer'] = $params['panduan'];
                    break;
                default:
                    $add['answer'] = NULL;
                    break;
            }


            if (!isset($add['answer']))
                $this->error('请选择正确答案选项');

            if (DB::name('praxis')->insert($add))
                $this->success('添加成功', 'test/praxis');
            else
                $this->error('添加失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/praxis');
        $this->assign('course', $course);
        $this->assign('knowledge', $knowledge);
        $this->assign('experiments', $experiments);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 修改习题
    public function upd_praxis()
    {
        $id = intval(Request::instance()->param('id'));
        $item = $this->dbTest->get_praxis($id);
        if (empty($item)) $this->error('试题不存在');


        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();

            if (!$params['content'])
                $this->error('请将内容填完整');

            $add['credit'] = intval($params['credit']);
            $add['difficult'] = intval($params['difficult']);
            $add['title'] = $params['title'];
            $add['content'] = $params['content'];

            // 根据题型，存储题目答案
            switch ($item['type']) {
                case 1: // 单选
                    $add['answer'] = $params['danxuan'];
                    $add['options'] = json_encode($params['op-danxuan'], JSON_UNESCAPED_UNICODE);
                    break;
                case 2: // 多选
                    if (!isset($params['duoxuan']))
                        $this->error('请选择正确答案选项');
                    $add['answer'] = json_encode($params['duoxuan']);
                    $add['options'] = json_encode($params['op-duoxuan'], JSON_UNESCAPED_UNICODE);
                    break;
                case 3: // 判断
                    $add['answer'] = $params['panduan'];
                    break;
                default:
                    $add['answer'] = NULL;
                    break;
            }

            if ($this->dbTest->upd_praxis($id, $add))
                $this->success('修改成功', 'test/praxis');
            else
                $this->error('添加失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/praxis');
        $this->assign('item', $item);
        return $this->fetch();
    }

    // 生成试卷
    public function newtest()
    {
        $title = ['name' => '生成试卷', 'en' => 'Make Paper'];
        $step = Request::instance()->param('step');
        $step = empty($step) ? 'stepa' : $step;

        switch ($step) {

            // 选择课程
            case 'stepa':
                // $map = $this->dbCourse->get_term();
                // $map['c.teacherid'] = $this->info['id'];
                $map['c.status'] = 1;
                $list = $this->dbCourse->mycourse($map, 10);
                $this->assign('list', $list);
                break;

            // 生成规律
            case 'stepb':
                $course_id = intval(Request::instance()->param('course_id'));
                if ($course_id < 1)
                    $this->error('OPTION ERROR');
                $course = $this->dbCourse->getone($course_id);

                $list = $this->dbTest->get_knowledgelist_bycourse($course_id, $this->info['id']);
                if (!empty($list)) {
                    foreach ($list as $key => $value) {
                        $list[$key]['cnt'] = DB::name('praxis')->where('knowledge_id', $value['id'])->count();
                    }
                    $list = array_filter($list, function ($value) {
                        return $value['cnt'] > 0;
                    });
                }
                if (empty($list)) $this->error('该课程下面试题库为空，请先添加知识点和试题', 'test/newtest?step=stepa');
                $this->assign('list', $list);
                $this->assign('course', $course);
                break;

            // 生成试卷
            case 'stepc':
                $params = Request::instance()->param();

                Db::transaction(function () {
                    $params = Request::instance()->param();
                    $cid = intval($params['cid']);
                    if ($cid < 1)
                        $this->error('OPTION ERROR');

                    $course = $this->dbCourse->getone($cid);

                    // 添加试卷
                    $add['teacher_id'] = $this->info['id'];
                    $add['course_id'] = $cid;
                    $add['name'] = $params['name'];
                    $add['duration'] = $params['duration'];
                    $add['start_time'] = $params['start_time'];
                    $add['end_time'] = date('Y-m-d H:i:s', strtotime($params['start_time']) + ($params['duration'] * 60));
                    $add['create_time'] = date('Y-m-d H:i:s');

                    $praxis_box = []; // 习题集合
                    $add['total_credit'] = 0; // 总分
                    foreach ($params['cnt'] as $key => $value) {
                        if ($value <= 0)
                            continue;
                        for ($i = 1; $i <= $value; $i++) {
                            do {
                                $one = DB::name('praxis')->field('id, credit')->where('knowledge_id', $key)->orderRaw('rand()')->limit(1)->find();
                            } while (in_array($one['id'], $praxis_box));
                            $praxis_box[] = $one['id'];
                            $add['total_credit'] += $one['credit'];
                        }
                    }

                    if (empty($praxis_box))
                        $this->error('PRAXIS COUNT ERROR');

                    $add['praxis_box'] = json_encode($praxis_box);

                    $testpaperID = DB::name('test_paper')->insertGetId($add);

                    //获取该门课程的粗学生
                    $stus = DB::name('student_option')->where('course_id', $cid)->column('student_id');
                    if (!empty($stus)) {
                        $insertData = [];
                        foreach ($stus as $v) {
                            $insertData[] = [
                                'student_id' => $v,
                                'paper_id' => $testpaperID,
                            ];
                        }
                        DB::name('test')->insert($insertData);
                    }
                });
                $this->success('生成试卷成功', 'test/index');
                break;
            default:
                $this->error('OPTION ERROR');
                break;

        }


        // 副导航标志
        $this->assign('sign_sidenav', 'test/newtest');
        $this->assign('step', $step);
        $this->assign('title', $title);
        return $this->fetch();
    }


    // 考试批阅（学生考试列表）
    public function testlist()
    {
        $title = ['name' => '考试列表', 'en' => 'Test List'];
        $paperid = intval(Request::instance()->param('paperid'));
        $map['a.status'] = ['>=', 1];
        $map['p.teacher_id'] = $this->info['id'];
        if ($paperid > 0)
            $map['a.paper_id'] = $paperid;

        $list = $this->dbTest->get_test_list($map, 10);
        // 副导航标志
        $this->assign('sign_sidenav', 'test/testlist');
        $this->assign('list', $list);
        $this->assign('title', $title);
        $this->assign('paperid', $paperid);
        return $this->fetch();
    }

    // 考试批阅（学生考试列表） 导出
    public function testlist_excel()
    {
        $table_name = '考试成绩表';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $paperid = intval(Request::instance()->param('paperid'));
        $map['a.status'] = ['>=', 1];
        $map['p.teacher_id'] = $this->info['id'];
        if ($paperid > 0)
            $map['a.paper_id'] = $paperid;

        $data = $this->dbTest->get_test_list($map, 10);

        $title = ['课程', '试卷', '学生', '考试开始时间', '成绩'];
        $icon = ['A', 'B', 'C', 'D', 'E'];
        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
        }

        foreach ($icon as $value) {
            $sheet->getColumnDimension($value)->setWidth(20);
        }

        $sheet->getStyle('E1')->applyFromArray(['alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT,
        ]]);

        $j = 2;
        foreach ($data as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['cname']);
            $sheet->setCellValue($icon[1] . strval($j), $value['pname']);
            $sheet->setCellValue($icon[2] . strval($j), $value['sname']);
            $sheet->setCellValue($icon[3] . strval($j), $value['start_time']);
            $sheet->setCellValue($icon[4] . strval($j), $value['grade']);
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


    // 考试批阅
    public function testitem()
    {
        $id = intval(Request::instance()->param('id'));

        $item = $this->dbTest->get_test($id);
        $paper = $this->dbTest->get_paper($item['paper_id']);

        $title = ['name' => $paper['name'], 'en' => 'Paper Info'];

        // 答案
        $answer = json_decode($item['answer'], TRUE);

        $paper['praxis_box'] = json_decode($paper['praxis_box'], TRUE);
        if (empty($paper['praxis_box']))
            $this->error('PRAXIS COUNT ERROR');

        // 取出试题
        $praxis_box = [];
        foreach ($paper['praxis_box'] as $v) {
            $tmp = $this->dbTest->get_praxis($v);
            $tmp['options'] = json_decode($tmp['options'], TRUE);

            if ($tmp['type'] == 1) {
                $praxis_box['danxuan'][] = $tmp;
            } elseif ($tmp['type'] == 2) {
                $tmp['answer'] = json_decode($tmp['answer'], TRUE);
                $praxis_box['duoxuan'][] = $tmp;
            } elseif ($tmp['type'] == 3) {
                $praxis_box['panduan'][] = $tmp;
            }
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'test/testlist');
        $this->assign('item', $item);
        $this->assign('paper', $paper);
        $this->assign('praxis_box', $praxis_box);
        $this->assign('answer', $answer);
        $this->assign('title', $title);
        return $this->fetch();
    }

}