<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/23
 * Time: 15:20
 */

namespace app\student\model;

use think\Model;
use think\Db;

class Test extends MyModel
{
    /**
     * 获取试卷列表
     * @return false|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTestList()
    {
        //TODO 获取试卷列表
        $testList = Db::name('test_paper')
            ->alias('p')
            ->join('test t', 't.paper_id = p.id')
            ->join('course c', 'p.course_id = c.id')
            ->join('teacher te', 'p.teacher_id = te.id')
            ->where('t.student_id', $this->studentId)
            ->field('p.name,c.name as cname,start_time,end_time,te.name as tname,p.id,t.status')
            ->order('p.start_time desc')
            ->paginate(6);
        return $testList;
    }

    /**
     * 获取试卷信息
     * @param $id int 试卷ID
     * @return array|false|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPaper($id)
    {
        //TODO 获取试卷信息
        $paperInfo = Db::name('test')
            ->alias('t')
            ->join('test_paper p', 't.paper_id = p.id')
            ->join('student s', 's.id = t.student_id')
            ->where('student_id', $this->studentId)
            ->where('p.id', $id)
            ->field('p.name,p.duration,p.start_time,p.end_time,p.praxis_box,s.name as sname,p.id,t.status,p.total_credit')
            ->find();

        $praxis = json_decode($paperInfo['praxis_box']);
        $paperInfo['praxis'] = $praxis;
        return $paperInfo;
    }

    /**
     * 获取试卷题目信息
     * @param $praxis array 题目ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSubject($praxis)
    {
        //TODO 得到习题资料
        $praxisInfo = Db::name('praxis')
            ->where('id', 'IN', $praxis)
            ->field('id,type,content,options,credit,answer')
            ->orderRaw(' rand() ')
            ->select();

        $info = [];
        foreach ($praxisInfo as $value) {
            $value['options'] = json_decode($value['options'], true);
            $info[$value['type']][] = $value;
        }
        return $info;
    }

    /**
     * 保存答题信息
     * @param $param array 答题信息
     * @param $id int 试卷ID
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function saveAnswer($param, $id)
    {
        //TODO 保存答题信息
        $data = [];
        $paper = [];
        array_pop($param);
        foreach ($param as $key => $value) {
            $key = substr($key, 1);
            $paper[] = $key;
            $data[$key] = $value;
        }
        $grade = 0;
        foreach ($data as $k => $v) {
            $praxis = Db::name('praxis')->where('id', $k)->field('id, type, answer,credit')->find();

            if ($praxis['type'] == 2) {
                if (!array_diff(json_decode($praxis['answer']), $v)) {
                    $grade += $praxis['credit'];
                }
            } else {
                if ($praxis['answer'] == $v) {
                    $grade += $praxis['credit'];
                }
            }

        }

        $time = date('Y-m-d H:i:s');
        $data = json_encode($data);
        $update = [
            'testtime' => $time,
            'grade' => $grade,
            'answer' => $data,
            'status' => 2
        ];

        $result = Db::name('test')
            ->where('student_id', $this->studentId)
            ->where('paper_id', $id)
            ->update($update);
        return $result;
    }

    /**
     * 获取结果
     * @param $id int 试卷ID
     * @return array|false|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getResult($id)
    {
        //查看答题信息
        $result = Db::name('test')
            ->alias('t')
            ->join('test_paper p', 't.paper_id = p.id')
            ->join('student s', 's.id = t.student_id')
            ->where('student_id', $this->studentId)
            ->where('paper_id', $id)
            ->field('p.name,s.name as sname,duration,start_time,end_time,p.id,total_credit,praxis_box,t.grade,t.answer')
            ->find();


        if (empty($result['answer'])) {
            $result['answer'] = [];
        } else {
            $result['answer'] = json_decode($result['answer'], true);
            foreach ($result['answer'] as &$value) {
                if (is_array($value)) {
                    $value = implode(",", $value);//将答案数组转化为字符串
                }
            }
        }
        return $result;
    }

    /**
     * 通过实验名搜索实验
     * @param $test
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($test)
    {
        $testList = Db::name('test_paper')
            ->alias('p')
            ->join('test t', 't.paper_id = p.id')
            ->join('course c', 'p.course_id = c.id')
            ->join('teacher te', 'p.teacher_id = te.id')
            ->where('student_id', $this->studentId)
            ->where('p.name', 'LIKE', "%$test%")
            ->field('p.name,c.name as cname,start_time,end_time,te.name as tname,p.id,t.status')
            ->paginate(6);
        return $testList;
    }
}
