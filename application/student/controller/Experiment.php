<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/16
 * Time: 15:16
 */

namespace app\student\controller;

use think\Request;
use app\student\model\Experiment as newExp;
use think\Session;
use think\DB;

class Experiment extends MyController
{
    protected $experiment;

    public function __construct()
    {
        parent::__construct();
        $this->experiment = new newExp();
    }

    /**
     * 实验列表数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //TODO 从布置表中获取实验数据
        $info = $this->experiment->getInfo();
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();
        $list = $info->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('info', $info);
        return $this->fetch('index');
    }


    public function index2()
    {
        $info = Db::name('experiment')
            ->alias('e')
            ->join('teacher t', 't.id = e.teacher_id')
            //->join('course c', 'c.id = e.course_id')
            ->field('e.id,e.name,t.name as tname, type')
            ->paginate(10);
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();
        $list = $info->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('info', $info);
        return $this->fetch('index2');
    }

    /**
     * 做实验
     * @param $id integer 实验布置ID
     * @param $status integer 状态
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function done($id, $status)
    {
        $content = $this->experiment->getContent($id);
        Session::set('expId', $id);
        if ($content['error'] == 0) {
            $result = $content['data'];
            $this->assign('info', $result);
            $this->assign('status', $status);
            return $this->fetch('experiment/test');
        } else {
            return $content;
        }
    }


    /**
     * 保存实验内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save()
    {
        $param = Request::instance()->param();

        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $param['word_file'] = $info->getSaveName();
        } else {
            return false;
        }

        if (isset($param) && !empty($param)) {
            $result = $this->experiment->saveInfo($param);
            if ($result) {
                return $result;
            } else {
                return '提交失败';
            }
        } else {
            return "失败";
        }
    }

    /**
     * 下载
     */
    public function download_word_file()
    {
        //下载文件名
        $id = Request::instance()->param('id');
        $file_name = DB::name('experiment_result')->where('id',$id)->value('word_file');
        if(empty($file_name))
            $this->error('DATA ERROR');

        $ext = pathinfo($file_name,PATHINFO_EXTENSION);

        //下载文件存放目录
        $file_dir = ROOT_PATH . 'public' . DS . 'uploads' . DS . str_replace('/',DS, $file_name);
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
            Header("Content-Disposition: attachment; filename=实验报告附件." .$ext);
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir));
            fclose($file);
            exit();
        }
    }


    /**
     * 获取实验结果
     * @param $id integer 实验ID
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expResult($id)
    {
        //TODO 获取实验结果
        $content = $this->experiment->getResult($id);
        if ($content['error'] == 0) {
            $result = $content['data'];
            $this->assign('info', $result);
            return $this->fetch('result');
        } else {
            return $content;
        }
    }


    /**
     * 图片上传
     * @return string
     */
    public function upload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $path = $info->getSaveName();
                $path = 'uploads' . DS . $path;
                return $path;
            } else {
                // 上传失败获取错误信息
                return $file->getError();
            }
        } else {
            return false;
        }
    }

    /**
     * 保存虚拟实验数据
     * 116.62.42.12:801/TeachingPlatform/public/student/experiment/saveResult?data=[]
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saveResult()
    {
        $param = Request::instance()->param();
        $id = Session::get('expId');
        $result = $this->experiment->saveResult($id, $param);
        if ($result == -1) {
            $this->error('实验已结束，不可再提交数据');
        } else {
            $this->success('提交成功');
        }
    }


    /**
     * 课程搜索
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $param = Request::instance()->param();
        $info = $this->experiment->getSearchInfo($param['exp']);
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();
        $list = $info->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('info', $info);
        return $this->fetch('index');
    }

    // 查看实验数据（非布置）
    public function dat(){
        $title = ['name' => '实验数据', 'en' => 'Experiment Data'];

        $list = DB::name('experiment_data')->alias('a')
            ->field('count(a.id) as cnt, a.student_id, a.e_id, a.eno, a.create_time, e.name as ename, s.name as sname')
            ->join('vr_experiment e','e.id = a.e_id')
            ->join('vr_student s','s.id = a.student_id')
            ->where('a.status', 1)->where('student_id',$this->studentId)->group('eno')->order('create_time desc')
            ->paginate(15)
            ->each(function($item){
            $item['cnt'] = $this->dat_get_cnt($item['eno']);
            return $item;
        });
        // 副导航标志
        $this->assign('sign_sidenav','experiment/dat');
        $this->assign('list',$list);
        $this->assign('title',$title);
        return $this->fetch();

    }

    public function dat_get_cnt($eno) {
        $item = DB::name('experiment_data')->alias('a')
            ->field(' a.student_id, a.e_id, a.eno, a.create_time, a.result')->where('eno',$eno)->find();
        $list = DB::name('experiment_data')->where('eno',$eno)->select();
        
        $data = []; // 综合数据
        $sz_question = []; // 实战模式题号集合
        $kh_question = []; // 考核模式题号集合
        foreach ($list as $k => $v) {
            $result = json_decode($v['result'],TRUE);
            if ($result['testType']=='实战模式') {
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
            }elseif ($result['testType']=='知识点考核模式') {
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

        return count($sz_question)+count($kh_question);
    }

    // 查看实验数据详情（非布置）
    public function dat_item(){
        $title = ['name' => '实验数据详情', 'en' => 'Experiment Data'];
        $eno = intval(Request::instance()->param('eno'));

        $item_eno = DB::name('experiment_eno')->where('eno',$eno)->find();

        $item = DB::name('experiment_data')->alias('a')
            ->field('count(a.id) as cnt, a.student_id, a.e_id, a.eno, a.create_time, e.name as ename, s.name as sname')
            ->join('vr_experiment e','e.id = a.e_id')
            ->join('vr_student s','s.id = a.student_id')->where('eno',$eno)->where('student_id',$this->studentId)->find();
        $list = DB::name('experiment_data')->where('eno',$eno)->select();

        if (empty($list))
            $this->error('找不到实验记录');

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();

            $add = [
                'word'          => $params['word']
            ];

            if (DB::name('experiment_eno')->where('eno',$eno)->update($add))
                $this->success('提交成功','experiment/dat_item?eno='.$eno);
            else              
                $this->error('提交失败');
        }

        $data = []; // 综合数据
        $sz_question = []; // 实战模式题号集合
        $kh_question = []; // 考核模式题号集合
        foreach ($list as $k => $v) {
            $result = json_decode($v['result'],TRUE);
            if ($result['testType']=='实战模式') {
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
            }elseif ($result['testType']=='知识点考核模式') {
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
            }elseif ($result['testType']=='训练模式探索') {
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


        $this->assign('title',$title);
        $this->assign('item_eno',$item_eno);
        $this->assign('item',$item);
        $this->assign('data',$data);
        return $this->fetch();

    }

}
