<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 17:09
 */

namespace app\student\model;

use think\Db;

class Experiment extends MyModel
{
    protected $name = 'experiment';
    protected $is_must = ['选做', '必做'];
    protected $status = ['未开始','实验中','实验结束'];


    /**
     * 获取实验信息
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getInfo()
    {
        $result = Db::name($this->name)
            ->alias('e')
            ->join('experiment_arrange a', 'e.id = a.experiment_id')
            ->join('experiment_result r', 'a.id = r.arrange_id')
            ->join('teacher t', 't.id = e.teacher_id')
            ->join('course c', 'c.id = e.course_id')
            ->where('r.student_id',$this->studentId)
            ->field('a.id,e.name,t.name as tname,c.name as cname,start_time,end_time,
            type,is_must,r.status,grade,r.done_time')
            ->paginate(6)
            ->each(function ($item) {
                if ($item['start_time'] > date("Y-m-d H:i:s")) {
                    $item['status'] = 0;
                } else {
                    if ($item['end_time'] < date("Y-m-d H:i:s")) {
                        $item['status'] = 2;
                    } else {
                        $item['status'] = 1;
                    }
                }
                $item['is_must'] = $this->is_must[$item['is_must']];
                $item['status'] = $this->status[$item['status']];
                if (is_null($item['grade'])) {
                    $item['grade'] = '未批改';
                }
                return $item;
            });

        return $result;
    }

    /**
     * 获取实验内容
     * @param $id int 实验ID
     * @return array|false|string 实验内容
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getContent($id)
    {
        $info = Db::name($this->name)
            ->alias('e')
            ->join('experiment_arrange a', 'e.id = a.experiment_id')
            ->join('experiment_result r', 'a.id = r.arrange_id')
            ->join('course c', 'c.id = e.course_id')
            ->where('r.student_id',$this->studentId)
            ->where('a.id', $id)
            ->field('a.id,e.name,c.name as cname,type,demand,e.word,duration,start_time, r.id as result_id, 
            r.word as rword, r.word_file, a.start_time,a.end_time,e.experiment')
            ->find();

        return $this->result($info);
    }

    /**
     * 获取实验结果
     * @param $id integer 实验ID
     * @return array 实验结果
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getResult($id)
    {
        $info = Db::name($this->name)
            ->alias('e')
            ->join('experiment_arrange a', 'e.id = a.experiment_id')
            ->join('experiment_result r', 'a.id = r.arrange_id')
            ->join('course c', 'c.id = e.course_id')
            ->where('r.student_id',$this->studentId)
            ->where('a.id', $id)
            ->field('a.id,e.name,c.name as cname,type,demand,e.word,duration,start_time,
            r.word as rword,a.start_time,a.end_time,r.grade,r.result,r.id as result_id,r.word_file')
            ->find();

        $info['result'] = json_decode($info['result'], true);

        return $this->result($info);
    }

    /**
     * 保存实验信息
     * @param $info array 实验报告
     * @return int|string 更新结果
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saveInfo($info)
    {
        $duration = Db::name('experiment_result')
            ->where('student_id', $this->studentId)
            ->where('arrange_id', $info['id'])
            ->value('duration');

        $duration += $info['time'];

        $result = Db::name('experiment_result')
            ->where('student_id',$this->studentId)
            ->where('arrange_id',$info['id'])
            ->update(
                [
                    'word' => $info['editor'],
                    'duration' => $duration,
                    'done_time' => date('Y-m-d H:i:s'),
                    'word_file' => $info['word_file']
                ]
            );

        return $result;
    }

    /**
     * 保存实验数据
     * @param $id integer 实验ID
     * @param $param array 虚拟实验数据
     * @return int|string 更新结果
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saveResult($id, $param)
    {
        $end_time = Db::name('experiment_arrange')
            ->where('id', $id)
            ->value('end_time');
        if ($end_time < date('Y-m-d H:i:s')) {
            return -1;
        } else {
            $result = Db::name('experiment_result')
                ->where('student_id',$this->studentId)
                ->where('arrange_id',$id)
                ->update(['result' => $param['data']]);
            return $result;
        }
    }


    /**
     * 通过实验名搜索实验
     * @param $exp
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($exp)
    {
        $result = Db::name($this->name)
            ->alias('e')
            ->join('experiment_arrange a', 'e.id = a.experiment_id')
            ->join('experiment_result r', 'a.id = r.arrange_id')
            ->join('teacher t', 't.id = e.teacher_id')
            ->join('course c', 'c.id = e.course_id')
            ->where('r.student_id', $this->studentId)
            ->where('e.name', 'LIKE', "%$exp%")
            ->field('a.id,e.name,t.name as tname,c.name as cname,start_time,end_time,
            type,is_must,r.status,grade,r.done_time')
            ->paginate(6)
            ->each(function ($item) {
                if ($item['start_time'] > date("Y-m-d H:i:s")) {
                    $item['status'] = 0;
                } else {
                    if ($item['end_time'] < date("Y-m-d H:i:s")) {
                        $item['status'] = 2;
                    } else {
                        $item['status'] = 1;
                    }
                }
                $item['is_must'] = $this->is_must[$item['is_must']];
                $item['status'] = $this->status[$item['status']];
                if (is_null($item['grade'])) {
                    $item['grade'] = '未批改';
                }
                return $item;
            });
        return $result;
    }
}
