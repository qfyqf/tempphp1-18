<?php
/**
 * User: hejun
 * Date: 2019/3/20
 */

namespace app\teach\controller;

use app\student\model\Student;
use app\teach\model\ClassInfo;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\teach\model\Course;
use app\teach\model\Experiment AS dbExperiment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class Experiment extends Base
{

    protected $dbCourse;
    protected $dbExperiment;

    public function _initialize()
    {
        $this->dbCourse = new Course();
        $this->dbExperiment = new dbExperiment();
    }

    // 我的实验库
    public function index()
    {
        $map['e.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['e.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        if ($cid = Request::instance()->param('cid')) {
            $map['e.course_id'] = $cid;
        }
        $list = $this->dbExperiment->getall($map, 10);
        $this->assign('list', $list);

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/index');
        return $this->fetch();
    }

    // 添加实验库
    public function add()
    {
        $title = ['name' => '添加实验', 'en' => 'New Experiment'];
        $course_list = $this->dbCourse->mycourselist($this->info['id']);

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            if (!$params['name'] || !$params['demand'] || !$params['course_id'])
                $this->error('请将内容填完整');

            $add = [
                'sid' => 'E' . date('ymdH') . str_pad(rand(0, 9999), 4, 0, STR_PAD_LEFT),
                'course_id' => $params['course_id'],
                'name' => $params['name'],
                'word' => $params['word'],
                'demand' => $params['demand'],
                'type' => $params['type'],
                'experiment' => $params['experiment'],
                'teacher_id' => $this->info['id'],
                'createtime' => date('Y-m-d H:i:s')
            ];

            if ($this->dbExperiment->addone($add))
                $this->success('添加成功', 'experiment/index');
            else
                $this->error('添加失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/add');
        $this->assign('course_list', $course_list);
        $this->assign('title', $title);
        return $this->fetch();
    }


    // 修改实验
    public function upd()
    {
        $title = ['name' => '修改实验', 'en' => 'Update Experiment'];
        $id = intval(Request::instance()->param('id'));
        if ($id < 1)
            $this->error('OPTION ERROR');

        $item = $this->dbExperiment->getone($id);

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            if (!$params['name'] || !$params['demand'])
                $this->error('请将内容填完整');

            $add = [
                'name' => $params['name'],
                'word' => $params['word'],
                'demand' => $params['demand'],
                'type' => $params['type'],
            ];

            if ($this->dbExperiment->updone($id, $add))
                $this->success('修改成功', 'experiment/index');
            else
                $this->error('修改失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/add');
        $this->assign('item', $item);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 设置实验参数（供客户端接口调用）
    public function setoption()
    {
        $title = ['name' => '设置参数', 'en' => 'Set Experiment'];
        $id = intval(Request::instance()->param('id'));
        if ($id < 1)
            $this->error('OPTION ERROR');

        $item = $this->dbExperiment->getone($id);

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();

            $file = request()->file('resource');

            // 上传到框架应用根目录/public/uploads/ 目录下
            if ($file) {
                $info = $file->validate(['size' => 15678000, 'ext' => 'jpg,png,gif,jpeg,doc,docx,xls,xlsx,pdf'])->move(ROOT_PATH . 'public' . DS . 'uploads');
                if ($info) {

                    $params['option_imgs'] = $info->getSaveName();
                    $params['option_imgs'] = str_replace("\\", "/", $params['option_imgs']);
                    unset($params['resource']);

                    if (DB::name('experiment')->where('id', $params['id'])->update($params)) {
                        $this->success('设置成功', 'experiment/index');
                    } else {
                        $this->error('设置失败');
                    }

                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

        }


        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/setoption');
        $this->assign('item', $item);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 布置新实验
    public function arrange()
    {
        $step = Request::instance()->param('step');
        $step = empty($step) ? 'c_course' : $step;

        switch ($step) {

            // 选择课程
            case 'c_course':
                $map = $this->dbCourse->get_term();
                $map['c.teacherid'] = $this->info['id'];
                $map['c.status'] = 1;
                if ($ss = Request::instance()->param('ss')) {
                    $map['c.name'] = ['LIKE', '%' . $ss . '%'];
                    $this->assign('ss', $ss);
                }
                $list = $this->dbCourse->mycourse($map, 10);
                $this->assign('list', $list);
                break;

            // 选择实验
            case 'c_expe':
                $course_id = Request::instance()->param('course_id');
                if ($course_id < 1)
                    $this->error('OPTION ERROR');

                $map['e.status'] = 1;
                $map['e.course_id'] = intval($course_id);
                $list = $this->dbExperiment->getall($map, 10);
                $this->assign('list', $list);
                break;

            // 布置实验
            case 'c_arrange':
                $expe_id = Request::instance()->param('expe_id');
                if ($expe_id < 1)
                    $this->error('OPTION ERROR');

                $item = $this->dbExperiment->getone($expe_id);
                if (empty($item))
                    $this->error('OPTION ERROR');
                $this->assign('item', $item);
                break;

            // 完成布置
            case 'c_done':
                Db::transaction(function () {
                    $params = Request::instance()->param();
                    $expe_id = intval($params['expe_id']);
                    if ($expe_id < 1)
                        $this->error('OPTION ERROR');

                    $experiment = $this->dbExperiment->getone($expe_id);

                    // 添加实验布置
                    $add['sid'] = $experiment['sid'] . 'D' . date('ymd') . str_pad(rand(0, 99), 2, 0, STR_PAD_LEFT);
                    $add['teacher_id'] = $this->info['id'];
                    $add['experiment_id'] = $expe_id;
                    $add['is_must'] = $params['is_must'];
                    $add['start_time'] = $params['start_time'];
                    $add['end_time'] = $params['end_time'];
                    $add['remark'] = $params['remark'];
                    $add['address'] = $params['address'];
                    $add['create_time'] = date('Y-m-d H:i:s');

                    $this->dbExperiment->arrage($expe_id, $add);
                });
                $this->success('实验布置成功', 'experiment/arranged');

                break;
            default:
                $this->error('OPTION ERROR');
                break;

        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/arrange');
        $this->assign('step', $step);
        return $this->fetch();
    }


    // 已安排实验
    public function arranged()
    {
        $where = [
            'a.teacher_id' => $this->info['id'],
            'a.status' => 1
        ];
        if ($ss = Request::instance()->param('ss')) {
            $where['e.name'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }

        $list = $this->dbExperiment->get_arrage_list($where, 10);

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/arranged');
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 修改实验安排
    public function arrange_upd()
    {
        $title = ['name' => '修改实验安排', 'en' => 'Experiment Result'];

        $id = intval(Request::instance()->param('id'));

        $item = $this->dbExperiment->get_arrage($id);

        if (empty($item))
            $this->error('DATA ERROR');

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            $add = [
                'is_must' => $params['is_must'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'address' => $params['address'],
                'remark' => $params['remark']
            ];

            if ($this->dbExperiment->upd_arrage($id, $add))
                $this->success('修改成功', 'experiment/arranged');
            else
                $this->error('修改失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/arranged');
        $this->assign('item', $item);
        $this->assign('title', $title);
        return $this->fetch();

    }

    // 查看实验数据（非布置）
    public function dat()
    {
        $title = ['name' => '实验数据', 'en' => 'Experiment Data'];
        //初始化
        $studentIds = $studentNames = $studentClass = $classNames = $experiments = [];

        //根据老师负责的班级获取学生数据
        $studentList = Student::getListByClassIds($this->info['classIds']);
        if (!empty($studentList)) {
            $studentIds = array_unique(array_column($studentList, 'id'));
            $studentNames = array_column($studentList, 'name', 'id');
            $studentClass = array_column($studentList, 'classID', 'id');
        }

        //获取班级信息
        $classList = ClassInfo::getListByIds($this->info['classIds']);
        if (!empty($classList)) {
            $classNames = array_column($classList, 'className', 'id');
        }


        $map = [
            'status' => 1,
        ];
        $map['sid'] = ['in', $studentIds];

        //优化列表
        $list = DB::name('experiment_eno')
            // ->field('count(a.id) as cnt, a.student_id, a.e_id, a.eno, a.create_time, e.name as ename, s.name as sname')
            //->field('a.*, e.name as ename, s.name as sname, s.sid, s.classID,ci.className')
            //->join('vr_experiment e', 'e.id = a.eid')
            //->join('vr_student s', 's.id = a.sid')
            //->join('vr_classinfo ci', 'ci.id = s.classID')
            //->where('a.status=1 AND s.classID = ' . $this->info['classID'])
            ->where($map)
            ->group('eno')->order('ctime desc')
            ->paginate(15)
            ->each(function ($item) {
                $item['cnt'] = $this->dat_get_cnt($item['eno']);
                return $item;
            });

        if (!empty($list->items())) {
            $experimentIds = array_unique(array_column($list->items(), 'eid'));
            if (!empty($experimentIds)) {
                $experiments = dbExperiment::getListByIds($experimentIds);
                if (!empty($experiments)) $experiments = array_column($experiments, 'name', 'id');
            }
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/dat');
        $this->assign('list', $list);
        $this->assign('student_names', $studentNames);
        $this->assign('student_class', $studentClass);
        $this->assign('class_names', $classNames);
        $this->assign('experiments', $experiments);
        //$this->assign('className', $this->info['className']);
        $this->assign('title', $title);
        return $this->fetch();
    }

    public function dat_get_cnt($eno)
    {
        $item = DB::name('experiment_data')->alias('a')
            ->field(' a.student_id, a.e_id, a.eno, a.create_time, a.result')->where('eno', $eno)->find();
        $list = DB::name('experiment_data')->where('eno', $eno)->select();

        $data = []; // 综合数据
        $sz_question = []; // 实战模式题号集合
        $kh_question = []; // 考核模式题号集合
        foreach ($list as $k => $v) {
            $result = json_decode($v['result'], TRUE);
            if ($result['testType'] == '实战模式') {
                if (!in_array($result['questionNum'], $sz_question)) {
                    $data[$result['testType']][] = [
                        'id' => $v['id'],
                        'grade' => $result['singleGrade'],
                        'duration' => $v['duration'],
                        'create_time' => $v['create_time'],
                        'info' => $result
                    ];
                    $sz_question[] = $result['questionNum'];
                }
            } elseif ($result['testType'] == '知识点考核模式') {
                if (!in_array($result['questionNum'], $kh_question)) {
                    $data[$result['testType']][] = [
                        'id' => $v['id'],
                        'grade' => $result['singleGrade'],
                        'duration' => $v['duration'],
                        'create_time' => $v['create_time'],
                        'info' => $result
                    ];
                    $kh_question[] = $result['questionNum'];
                }
            }
        }

        return count($sz_question) + count($kh_question);
    }

    // 查看实验数据详情（非布置）
    public function dat_item()
    {
        $title = ['name' => '实验数据详情', 'en' => 'Experiment Data'];
        $eno = intval(Request::instance()->param('eno'));
        $item_eno = DB::name('experiment_eno')->where('eno', $eno)->find();

        $scorerule = DB::name('experiment')->where('id', 1)->value('score_rule');

        $item = DB::name('experiment_data')->alias('a')
            ->field('count(a.id) as cnt, a.student_id, a.e_id, a.eno, a.create_time, e.name as ename, s.name as sname')
            ->join('vr_experiment e', 'e.id = a.e_id')
            ->join('vr_student s', 's.id = a.student_id')->where('eno', $eno)->find();
        $list = DB::name('experiment_data')->where('eno', $eno)->select();

        if (empty($list))
            $this->error('找不到实验记录');

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();

            $add = [
                'score1' => $params['score1'],
                'score2' => $params['score2'],
                'score3' => $params['score3'],
                'score4' => $params['score4'],
                'score_rule' => $params['score_rule']
            ];

            $scorerule = explode(':', $params['score_rule']);

            $add['score'] = round($add['score1'] * $scorerule[0] * 0.01) + round($add['score2'] * $scorerule[1] * 0.01) + round($add['score3'] * $scorerule[2] * 0.01) + round($add['score4'] * $scorerule[3] * 0.01);

            if (DB::name('experiment_eno')->where('eno', $eno)->update($add))
                $this->success('提交成功', 'experiment/dat_item?eno=' . $eno);
            else
                $this->error('提交失败');
        }

        $data = []; // 综合数据
        $sz_question = []; // 实战模式题号集合
        $kh_question = []; // 考核模式题号集合
        foreach ($list as $k => $v) {
            $result = json_decode($v['result'], TRUE);
            if ($result['testType'] == '实战模式') {
                if (!in_array($result['questionNum'], $sz_question)) {
                    $data[$result['testType']][] = [
                        'id' => $v['id'],
                        'grade' => $result['singleGrade'],
                        'duration' => $v['duration'],
                        'create_time' => $v['create_time'],
                        'info' => $result
                    ];
                    $sz_question[] = $result['questionNum'];
                }
            } elseif ($result['testType'] == '知识点考核模式') {
                if (!in_array($result['questionNum'], $kh_question)) {
                    $data[$result['testType']][] = [
                        'id' => $v['id'],
                        'grade' => $result['singleGrade'],
                        'duration' => $v['duration'],
                        'create_time' => $v['create_time'],
                        'info' => $result
                    ];
                    $kh_question[] = $result['questionNum'];
                }
            } elseif ($result['testType'] == '训练模式探索') {
                $data['训练模式探索'] = [
                    'id' => $v['id'],
                    'grade' => $result['singleGrade'],
                    'duration' => $v['duration'],
                    'create_time' => $v['create_time'],
                    'info' => $result
                ];
            }
        }

        unset($list);

        $this->assign('title', $title);
        $this->assign('item_eno', $item_eno);
        $this->assign('scorerule', $scorerule);
        $this->assign('item', $item);
        $this->assign('data', $data);
        return $this->fetch();

    }


    // 得分规则权重设置
    public function scorerule()
    {
        $title = ['name' => '权重设置', 'en' => 'Score Rule'];
        $id = intval(Request::instance()->param('id'));
        if ($id < 1) $id = 1;

        $item = $this->dbExperiment->getone($id);

        $rule = explode(':', $item['score_rule']);

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            if (!$params['rule'])
                $this->error('请将内容填完整');

            $upd['score_rule'] = implode(':', $params['rule']);

            if ($this->dbExperiment->updone($id, $upd))
                $this->success('修改成功', 'experiment/scorerule');
            else
                $this->error('修改失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/scorerule');
        $this->assign('item', $item);
        $this->assign('rule', $rule);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 实验成绩列表（非布置）
    public function gradelist()
    {
        $title = ['name' => '实验成绩', 'en' => 'Experiment Grade'];
        //$map['a.status'] = 1;
        $map['s.classID'] = ['in',$this->info['classIds']];
        $list = DB::name('student')
            ->alias('s')
            ->field("s.id, s.sid, s.name, c.className")
            ->join('vr_classinfo c', 'c.id = s.classID')
            //->where('s.classID = ' . $this->info['classID'])
            ->where($map)
            ->paginate(20);
        $data = $list->items();//学生信息

        foreach ($data as $k => $v) {
            // 训练模式探索 得分
            $tmp = DB::name('experiment_data')
                ->field('student_id, isRight, singleGrade')
                ->where('status=1 AND testType="训练模式探索"  AND student_id = ' . $v['id'])
                ->order('create_time DESC')
                ->limit(1)
                ->find();
            $data[$k]['grade']['训练模式探索'] = empty($tmp) ? 0 : ($tmp['isRight'] ? 0 : $tmp['singleGrade']);

            // 实战模式 得分
            $questionNum_list = DB::name('experiment_data')
                ->distinct(true)
                ->where('status=1 AND testType="实战模式"  AND student_id = ' . $v['id'])
                ->column('questionNum');

            if (empty($questionNum_list)) {
                $data[$k]['grade']['实战模式'] = 0;
            } else {
                $score = 0;
                foreach ($questionNum_list as $va) {
                    $tmp = DB::name('experiment_data')->field('student_id, isRight, singleGrade')->where('status=1 AND testType="实战模式" AND questionNum = ' . $va . '  AND student_id = ' . $v['id'])->order('create_time DESC')->limit(1)->find();
                    if (!empty($tmp) && $tmp['isRight'] == 0) {
                        $score += $tmp['singleGrade'];
                    }
                }
                $data[$k]['grade']['实战模式'] = round($score / 4.55);
            }

            // 知识点考核模式 得分
            $questionNum_list = DB::name('experiment_data')->distinct(true)->where('status=1 AND testType="知识点考核模式"  AND student_id = ' . $v['id'])->column('questionNum');

            if (empty($questionNum_list)) {
                $data[$k]['grade']['知识点考核模式'] = 0;
            } else {
                $score = 0;
                foreach ($questionNum_list as $va) {
                    $tmp = DB::name('experiment_data')->field('student_id, isRight, singleGrade')->where('status=1 AND testType="知识点考核模式" AND questionNum = ' . $va . '  AND student_id = ' . $v['id'])->order('create_time DESC')->limit(1)->find();
                    if (!empty($tmp) && $tmp['isRight'] == 0) {
                        $score += $tmp['singleGrade'];
                    }
                }
                $data[$k]['grade']['知识点考核模式'] = $score;
            }

            // 实验报告 得分
            $data[$k]['grade']['实验报告'] = intval(DB::name('experiment_eno')->where('status=1 AND sid = ' . $v['id'])->order('score4 DESC')->limit(1)->value('score4'));

            // 综合得分
            $tmp = DB::name('experiment')->where('id = 1')->value('score_rule');
            $scorerule = explode(':', $tmp);

            $data[$k]['grade']['score'] = round($data[$k]['grade']['训练模式探索'] * $scorerule[0] * 0.01) + round($data[$k]['grade']['实战模式'] * $scorerule[1] * 0.01) + round($data[$k]['grade']['知识点考核模式'] * $scorerule[2] * 0.01) + round($data[$k]['grade']['实验报告'] * $scorerule[3] * 0.01);

        }

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/gradelist');
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('title', $title);
        return $this->fetch();
    }

    public function download_excel()
    {
        $map = [];
        //获取要导出的学生
        $studentIds = trim(Request::instance()->param('student_ids'));
        if (!empty($studentIds)) {
            $map['s.id'] = ['in', explode(',', $studentIds)];
        }
        $map['s.classID'] = ['in',$this->info['classIds']];

        $list = DB::name('student')
            ->alias('s')
            ->field("s.id, s.sid, s.name, c.className")
            ->join('vr_classinfo c', 'c.id = s.classID')
            //->where('s.classID = ' . $this->info['classID'])
            ->where($map)
            ->paginate(20);
        $data = $list->items();//学生信息

        foreach ($data as $k => $v) {
            // 训练模式探索 得分
            $tmp = DB::name('experiment_data')
                ->field('student_id, isRight, singleGrade')
                ->where('status=1 AND testType="训练模式探索"  AND student_id = ' . $v['id'])
                ->order('create_time DESC')
                ->limit(1)
                ->find();
            $data[$k]['grade']['训练模式探索'] = empty($tmp) ? 0 : ($tmp['isRight'] ? 0 : $tmp['singleGrade']);

            // 实战模式 得分
            $questionNum_list = DB::name('experiment_data')
                ->distinct(true)
                ->where('status=1 AND testType="实战模式"  AND student_id = ' . $v['id'])
                ->column('questionNum');

            if (empty($questionNum_list)) {
                $data[$k]['grade']['实战模式'] = 0;
            } else {
                $score = 0;
                foreach ($questionNum_list as $va) {
                    $tmp = DB::name('experiment_data')->field('student_id, isRight, singleGrade')->where('status=1 AND testType="实战模式" AND questionNum = ' . $va . '  AND student_id = ' . $v['id'])->order('create_time DESC')->limit(1)->find();
                    if (!empty($tmp) && $tmp['isRight'] == 0) {
                        $score += $tmp['singleGrade'];
                    }
                }
                $data[$k]['grade']['实战模式'] = round($score / 4.55);
            }

            // 知识点考核模式 得分
            $questionNum_list = DB::name('experiment_data')->distinct(true)->where('status=1 AND testType="知识点考核模式"  AND student_id = ' . $v['id'])->column('questionNum');

            if (empty($questionNum_list)) {
                $data[$k]['grade']['知识点考核模式'] = 0;
            } else {
                $score = 0;
                foreach ($questionNum_list as $va) {
                    $tmp = DB::name('experiment_data')->field('student_id, isRight, singleGrade')->where('status=1 AND testType="知识点考核模式" AND questionNum = ' . $va . '  AND student_id = ' . $v['id'])->order('create_time DESC')->limit(1)->find();
                    if (!empty($tmp) && $tmp['isRight'] == 0) {
                        $score += $tmp['singleGrade'];
                    }
                }
                $data[$k]['grade']['知识点考核模式'] = $score;
            }

            // 实验报告 得分
            $data[$k]['grade']['实验报告'] = intval(DB::name('experiment_eno')->where('status=1 AND sid = ' . $v['id'])->order('score4 DESC')->limit(1)->value('score4'));

            // 综合得分
            $tmp = DB::name('experiment')->where('id = 1')->value('score_rule');
            $scorerule = explode(':', $tmp);

            $data[$k]['grade']['score'] = round($data[$k]['grade']['训练模式探索'] * $scorerule[0] * 0.01) + round($data[$k]['grade']['实战模式'] * $scorerule[1] * 0.01) + round($data[$k]['grade']['知识点考核模式'] * $scorerule[2] * 0.01) + round($data[$k]['grade']['实验报告'] * $scorerule[3] * 0.01);
        }


        // 准备excel表头（列）
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = ['学生', '班级', '学号', '知识点探索', '实战模式', '知识点考核', '实验报告', '综合得分'];
        $icon = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];


        // 循环设置表头 对齐方式和字体样式
        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
        }

        // $j 表示行，内容从第二行开始
        $j = 2;


        // 循环设置数据表的内容和样式
        foreach ($data as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['name']);
            $sheet->setCellValue($icon[1] . strval($j), $value['className']);
            $sheet->setCellValue($icon[2] . strval($j), $value['sid']);

            $sheet->setCellValue($icon[3] . strval($j), $value['grade']['训练模式探索']);
            $sheet->setCellValue($icon[4] . strval($j), $value['grade']['实战模式']);
            $sheet->setCellValue($icon[5] . strval($j), $value['grade']['知识点考核模式']);
            $sheet->setCellValue($icon[6] . strval($j), $value['grade']['实验报告']);
            $sheet->setCellValue($icon[7] . strval($j), $value['grade']['score']);
            $j++;
        }
        unset($j);

        // 输出文件，保存下载
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="实验成绩表.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

    }


    public function gradelist_excel()
    {
        $map = [];
        $map['a.status'] = 1;
        $list = DB::name('experiment_eno')->alias('a')
            ->field('a.*, e.name as ename, s.name as sname, s.sid, s.classID, c.className')
            ->join('vr_experiment e', 'e.id = a.eid')
            ->join('vr_student s', 's.id = a.sid')
            ->join('vr_classinfo c', 'c.id = s.classID')
            ->where('a.status=1 AND a.score_rule is not null AND s.classID = ' . $this->info['classID'])
            ->paginate(20);

        // 准备excel表头（列）
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = ['学生', '班级', '学号', '知识点探索', '实战模式', '知识点考核', '实验报告', '综合得分'];
        $icon = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];


        // 循环设置表头 对齐方式和字体样式
        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
        }

        // $j 表示行，内容从第二行开始
        $j = 2;


        // 循环设置数据表的内容和样式
        foreach ($list as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['sname']);
            $sheet->setCellValue($icon[1] . strval($j), $value['className']);

            $sheet->setCellValue($icon[2] . strval($j), $value['sid']);
            $sheet->setCellValue($icon[3] . strval($j), $value['score1']);
            $sheet->setCellValue($icon[4] . strval($j), $value['score2']);
            $sheet->setCellValue($icon[5] . strval($j), $value['score3']);
            $sheet->setCellValue($icon[6] . strval($j), $value['score4']);
            $sheet->setCellValue($icon[7] . strval($j), $value['score']);
            $j++;
        }
        unset($j);

        // 输出文件，保存下载
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="实验成绩表.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

    }

    // 查看实验安排详情(实验学生列表)
    public function arrange_item()
    {
        $title = ['name' => '实验结果', 'en' => 'Experiment Result'];

        $id = intval(Request::instance()->param('id'));

        $item = $this->dbExperiment->get_arrage($id);
        $list = $this->dbExperiment->experiment_result_list($id, 10);

        // 副导航标志
        $this->assign('sign_sidenav', 'experiment/arranged');
        $this->assign('item', $item);
        $this->assign('list', $list);
        $this->assign('title', $title);
        return $this->fetch();
    }

    // 查看实验安排详情(学生实验成绩导出)
    public function arrange_item_excel()
    {

        // 获取数据
        $id = intval(Request::instance()->param('id'));
        $item = $this->dbExperiment->get_arrage($id);
        $data = $this->dbExperiment->experiment_result_list($id, 0);

        // 准备excel表头（列）
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $title = ['学生', '实验名称', '课程名称', '实验时间', '教师', '完成时间', '成绩'];
        $icon = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        // 设置列宽度
        foreach ($icon as $value) {
            $sheet->getColumnDimension($value)->setWidth(20);
        }
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(40);

        // 定义表头 对齐方式和字体样式
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


        // 循环设置表头 对齐方式和字体样式
        for ($i = 0; $i < count($title); $i++) {
            $sheet->setCellValue($icon[$i] . '1', $title[$i]);
            $sheet->getStyle($icon[$i] . '1')->applyFromArray($styleArray);
        }

        // 单独设置表头 A1 的对齐方式
        $sheet->getStyle('A1')->applyFromArray(['alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
        ]]);

        // $j 表示行，内容从第二行开始
        $j = 2;

        // 定义内容样式
        $style = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ];

        // 循环设置数据表的内容和样式
        foreach ($data as $value) {
            $sheet->setCellValue($icon[0] . strval($j), $value['sname']);
            $sheet->setCellValue($icon[1] . strval($j), $item['ename']);
            $sheet->getStyle($icon[1] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[2] . strval($j), $item['cname']);
            $sheet->getStyle($icon[2] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[3] . strval($j), $item['start_time'] . '至' . $item['end_time']);
            $sheet->getStyle($icon[3] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[4] . strval($j), $item['tname']);
            $sheet->getStyle($icon[4] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[5] . strval($j), $value['done_time']);
            $sheet->getStyle($icon[5] . strval($j))->applyFromArray($style);
            $sheet->setCellValue($icon[6] . strval($j), $value['grade']);
            $sheet->getStyle($icon[6] . strval($j))->applyFromArray($style);
            $j++;
        }
        unset($j);

        // 输出文件，保存下载
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="实验成绩表.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // 查看实验结果
    public function arrange_result()
    {
        $title = ['name' => '实验结果详情', 'en' => 'Experiment Result'];
        $id = intval(Request::instance()->param('id'));

        $item = DB::name('experiment_result')->where('id', $id)->find();

        if (empty($item))
            $this->error('找不到实验记录');

        $arrange = $this->dbExperiment->get_arrage($item['arrange_id']);

        $experiment = $this->dbExperiment->getone($arrange['experiment_id']);

        // $this->assign('sign_sidenav','experiment/arranged');
        $this->assign('item', $item);
        $this->assign('arrange', $arrange);
        $this->assign('experiment', $experiment);
        $this->assign('title', $title);
        return $this->fetch();

    }

    // 实验批改打分
    public function arrange_result_grade()
    {
        $id = intval(Request::instance()->param('id'));
        $grade = intval(Request::instance()->param('grade'));
        if ($grade < 0 || $grade > 100)
            $this->error('分数超出范围');

        $where = ['id' => $id];
        $item = DB::name('experiment_result')->field('id, student_id, arrange_id')->where($where)->find();
        if (empty($item))
            $this->error('找不到实验记录');
        if (DB::name('experiment_result')->where($where)->update(['grade' => $grade])) {
            $this->success('成绩批改成功', 'experiment/arrange_item?id=' . $item['arrange_id']);
        } else {
            $this->error('成绩批改失败');
        }

    }

    /**
     * 下载实验报告附件
     */
    public function download_word_file()
    {
        //下载文件名
        $id = Request::instance()->param('id');
        $file_name = DB::name('experiment_result')->where('id', $id)->value('word_file');
        if (empty($file_name))
            $this->error('DATA ERROR');

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        //下载文件存放目录
        $file_dir = ROOT_PATH . 'public' . DS . 'uploads' . DS . str_replace('/', DS, $file_name);
        //检查文件是否存在
        if (!file_exists($file_dir)) {
            $this->error('资源找不到');
        } else {
            //打开文件
            $file = fopen($file_dir, "r");
            //输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: " . filesize($file_dir));
            Header("Content-Disposition: attachment; filename=实验报告附件." . $ext);
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir));
            fclose($file);
            exit();
        }
    }


}