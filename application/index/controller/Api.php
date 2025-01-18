<?php
/**
 * 与unity客户端交互的接口
 * User: hejun
 * Date: 2019/06/03
 * Time: 09:27
 */

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use think\Validate;
use think\Csv;

class Api extends Controller {
    // private $re = ['code'=>1, 'message'=>'error'];
    public function _initialize(){
        $qlkey = Request::instance()->param('qlkey');
        if (empty($qlkey) || $qlkey!='qlkj360') {
            exit($this->re(2,'key error'));
        }
        unset($this->request->post()['qlkey']);
    }


    public function index() {
        return $this->re(0,'test data');
    }


// 判断用户登录信息
    // 参数：  username 用户名
    //         password 密码
    public function login() {

        $username = Request::instance()->param('username');
        $password = Request::instance()->param('password');
        $stu = DB::name('student')->field('id, sid, name, account, password,classID')->where('account',$username)->find();
        if (empty($stu))
            exit($this->re(1,'data error'));

        if (password_verify($password, $stu['password'])) {
            unset($stu['password']);
            $stu['eno'] = (date('m')+10).(date('m')+date('d')).rand(1111,9999).rand(11,99); // 实验编号
            Session::set('role', 0);
            Session::set('name', $stu['name']);
            Session::set('id', $stu['id']);
            $teacher =  DB::name('teacher_class')->field('*')->where('class_id',$stu['classID'])->find();
            $teacher =  DB::name('teacher')->field('*')->where('id',$teacher['teacher_id'])->find();
            $stu['teacher']=$teacher['name'];
            $class =  DB::name('classinfo')->field('*')->where('id',$stu['classID'])->find();
            unset($class['id']);
            $stu = array_merge($stu,$class);
            if (empty($stu))
                // 记录数据库
                $eno['eid'] = 1;
            $eno['eno'] = $stu['eno'];
            $eno['sid'] = $stu['id'];
            $eno['ctime'] = date('Y-m-d H:i:s');
            DB::name('experiment_eno')->insert($eno);
            exit($this->re(0,'ok',$stu));
        }else{
            exit($this->re(1,'password error'));
        }
    }
    // 判断用户登录信息
    // 参数：  username 用户名
    //         password 密码
    public function is_student() {
        $username = Request::instance()->param('username');
        $password = Request::instance()->param('password');
        $stu = DB::name('student')->field('id, sid, name, account, password')->where('account',$username)->find();
        if (empty($stu))
            exit($this->re(1,'data error'));

        if (password_verify($password, $stu['password'])) {
            unset($stu['password']);
            $stu['eno'] = (date('m')+10).(date('m')+date('d')).rand(1111,9999).rand(11,99); // 实验编号
            Session::set('role', 0);
            Session::set('name', $stu['name']);
            Session::set('id', $stu['id']);

            // 记录数据库
            $eno['eid'] = 1;
            $eno['eno'] = $stu['eno'];
            $eno['sid'] = $stu['id'];
            $eno['ctime'] = date('Y-m-d H:i:s');
            DB::name('experiment_eno')->insert($eno);
            exit($this->re(0,'ok',$stu));
        }else{
            exit($this->re(1,'password error'));
        }
    }

    public function uploadBase64(){
        try{
            $image=$this->request->post('image');
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $image, $result)){
                //图片后缀
                $type = $result[2];
                //保存位置--图片名
                $image_name=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type;
                $image_file_path = '/uploads/image/'.date('Ymd');
                $image_file = ROOT_PATH.'public'.$image_file_path;
                $imge_real_url = $image_file.'/'.$image_name;
                $imge_web_url = $image_file_path.'/'.$image_name;
                if (!file_exists($image_file)){
                    mkdir($image_file, 0777,true);
                    fopen($image_file.'\\'.$image_name, "w");
                }
                //解码
                $decode=base64_decode(str_replace($result[1], '', $image));
                if (file_put_contents($imge_real_url, $decode)){
                    $data['code']=0;
                    $data['imageName']=$image_name;
                    $data['url']='http://'.$_SERVER['HTTP_HOST'].$imge_web_url;
                    $data['msg']='保存成功！';
                }else{
                    $data['code']=1;
                    $data['imgageName']='';
                    $data['url']='';
                    $data['msg']='图片保存失败！';
                }
            }else{
                $data['code']=1;
                $data['imgageName']='';
                $data['url']='';
                $data['msg']='base64图片格式有误！';
            }
            return $this->re($data['code'],$data['msg'],['url'=>$data['url']]);
        }catch (\Exception $e){
            return $this->re(0,$e->getMessage());
        }
    }


    public function report(){
        $data = $this->request->post();
        unset($data['qlkey']);
        $validate  = new Validate([
            'sid'=>'require|integer',
            'type'=>'require|integer|in:1,2',
        ]);
        if(!$validate->check($data)){
            return $this->re(1,$validate->getError());
        }
        $type = $data['type'];
        unset($data['type']);
        $model = Db::name('report')->where(['sid'=>$data['sid']])->find();
        if(empty($model)){
            $res =  Db::name('report')->insert($data);
        }else{
//            if($type==1 && !empty($model['source'])){
//                return $this->re(1,'请勿重复提交');
//            }elseif($type==2 && !empty($model['content'])){
//                return $this->re(1,'请勿重复提交');
//            }
            if($type == 1){
                $res = Db::name('report')->where(['sid'=>$data['sid']])->update(['source'=>$data['source'],'data'=>$data['data']]);
            }
            if($type == 2){
                $res = Db::name('report')->where(['sid'=>$data['sid']])->update(['content'=>$data['content']]);
            }
        }
        return  !empty($res) ? $this->re(0,'保存成功') : $this->re(1,'保存失败');
    }




    //批量生成报告
    public function generateReport(){
        $mapping=[
            '28'=>strtotime('2019/11/15'),
            '29'=>strtotime('2019/11/18'),
            '30'=>strtotime('2019/11/20'),
            '25'=>strtotime('2019/11/22'),
            '26'=>strtotime('2019/11/25'),
            '27'=>strtotime('2019/11/27'),
            '19'=>strtotime('2020/10/07'),
            '20'=>strtotime('2020/10/09'),
            '21'=>strtotime('2020/10/12'),
            '16'=>strtotime('2020/10/14'),
            '17'=>strtotime('2020/10/16'),
            '18'=>strtotime('2020/10/19'),
        ];
        $name_array=['17光信1班','17光信2班','17光信3班','17信科1班','17信科2班','17信科3班','18光信1班','18光信2班','18光信3班','18信科1班','18信科2班','18信科3班'];
        $class = Db::name('classinfo')->field('id')->whereIn('className',$name_array)->select();
        $class_ids=array_column($class, 'id');
        $students = Db::name('student')->field('id,classID')->whereIn('classID',$class_ids)->select();
        foreach ($students as $student){
            $a=rand(10,18);
            $b=rand(10,14);
            $c=rand(3,6);
            $d=rand(15,23);
            $e=rand(4,8);
            $f=rand(3,6);
            $arr=[
                [
                    "key"=>"预习（20）",
                    "value"=>$a
                ],
                [
                    "key"=>"开机前准备（15）",
                    "value"=>$b
                ],
                [
                    "key"=>"放样（6）",
                    "value"=>$c
                ],
                [
                    "key"=>"参数设置、薄膜沉积（24）",
                    "value"=>$d
                ],
                [
                    "key"=>"吹扫管理、取样（9）",
                    "value"=>$e
                ],
                [
                    "key"=>"关机（6）",
                    "value"=>$f
                ],
            ];
            $model = Db::name('report')->where(['sid'=>$student['id']])->find();
            if($model){
                $res =  Db::name('report')->where(['sid'=>$student['id']])->update(['source'=>$a+$b+$c+$d+$e+$f,'data'=>json_encode($arr,JSON_UNESCAPED_UNICODE),'t_score'=>rand(15,20),'created'=>isset($mapping[$student['classID']])?date('Y-m-d H:i:s',$mapping[$student['classID']]):'']);
                echo $student['id'] . '修改'.':'  . '-----' . $res.'<br/>';
            }else{
                $data['sid']=$student['id'];
                $data['data']=json_encode($arr,JSON_UNESCAPED_UNICODE);
                $data['source']=$a+$b+$c+$d+$e+$f;
                $data['t_score']=rand(15,20);
                $data['created']=isset($mapping[$student['classID']])?date('Y-m-d H:i:s',$mapping[$student['classID']]):'';
                $res =  Db::name('report')->insert($data);
                echo $student['id'] . '创建'.':'  . '-----' . $res.'<br/>';
            }
        }
    }
    //批量生成报告
    public function csv(){
        header("Content-type:text/html;charset=utf-8");
        $csv = new Csv();  //实例化后才可以调用之前类文件定义好的方法
        $list = Db::table('vr_report')->select();
        $arr=[
            [
                'name'=>'蒋雨浓',
                'sid'=>'1511121324',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":14},{"key":"开机前准备（15）","value":10},{"key":"放样（6）","value":6},{"key":"参数设置、薄膜沉积（24）","value":20},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":6}]',
                'start'=>'2019-09-01 09:01:19',
                'end'=>'2019-09-01 12:02:59',
                'source'=>64,
                't_score'=>15,
                'scores'=>79

            ],
            [
                'name'=>'冯怡',
                'sid'=>'1511121225',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":19},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":20},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:02:29',
                'end'=>'2019-09-01 12:04:09',
                'source'=>69,
                't_score'=>17,
                'scores'=>86

            ],
            [
                'name'=>'谭心雨',
                'sid'=>'1511121216',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":18},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":19},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:02:44',
                'end'=>'2019-09-01 12:04:24',
                'source'=>68,
                't_score'=>18,
                'scores'=>86

            ],
            [
                'name'=>'鲁希',
                'sid'=>'1511121308',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":15},{"key":"开机前准备（15）","value":12},{"key":"放样（6）","value":4},{"key":"参数设置、薄膜沉积（24）","value":17},{"key":"吹扫管理、取样（9）","value":6},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:03:52',
                'end'=>'2019-09-01 12:05:32',
                'source'=>58,
                't_score'=>16,
                'scores'=>74

            ],
            [
                'name'=>'王水姗',
                'sid'=>'1511121218',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":16},{"key":"开机前准备（15）","value":11},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:03:54',
                'end'=>'2019-09-01 12:30:26',
                'source'=>56,
                't_score'=>13,
                'scores'=>69

            ],
            [
                'name'=>'李玉萍',
                'sid'=>'1511121416',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":16},{"key":"开机前准备（15）","value":11},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":4},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:03:59',
                'end'=>'2019-09-01 12:05:39',
                'source'=>55,
                't_score'=>14,
                'scores'=>69

            ],
            [
                'name'=>'钟晨',
                'sid'=>'1511121430',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":14},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":18},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:05:42',
                'end'=>'2019-09-01 12:07:22',
                'source'=>60,
                't_score'=>17,
                'scores'=>77

            ],
            [
                'name'=>'皮硕',
                'sid'=>'1511121439',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":18},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:05:52',
                'end'=>'2019-09-01 12:07:32',
                'source'=>60,
                't_score'=>11,
                'scores'=>71,

            ],
            [
                'name'=>'张倩',
                'sid'=>'1511121452',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":20},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":22},{"key":"吹扫管理、取样（9）","value":7},{"key":"关机（6）","value":6}]',
                'start'=>'2019-09-01 09:06:26',
                'end'=>'2019-09-01 12:08:06',
                'source'=>74,
                't_score'=>18,
                'scores'=>92,

            ],
            [
                'name'=>'王依敏',
                'sid'=>'1511121162',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":16},{"key":"开机前准备（15）","value":11},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":4},{"key":"关机（6）","value":4}]',
                'start'=>'2019-09-01 09:07:56',
                'end'=>'2019-09-01 12:09:36',
                'source'=>55,
                't_score'=>12,
                'scores'=>67,

            ],
            [
                'name'=>'夏小蝶',
                'sid'=>'1511121178',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":18},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":22},{"key":"吹扫管理、取样（9）","value":9},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:01:19',
                'end'=>'2020-10-07 17:02:59',
                'source'=>73,
                't_score'=>18,
                'scores'=>91,

            ],
            [
                'name'=>'谭荣恩',
                'sid'=>'1511121240',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":18},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2020-10-07 14:02:29',
                'end'=>'2020-10-07 17:04:09',
                'source'=>60,
                't_score'=>18,
                'scores'=>78,

            ],
            [
                'name'=>'李晨',
                'sid'=>'1511121278',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":15},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":22},{"key":"吹扫管理、取样（9）","value":9},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:02:44',
                'end'=>'2020-10-07 17:04:24',
                'source'=>70,
                't_score'=>18,
                'scores'=>88,

            ],
            [
                'name'=>'李喜',
                'sid'=>'1511121362',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":19},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":20},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:03:52',
                'end'=>'2020-10-07 17:05:32',
                'source'=>72,
                't_score'=>19,
                'scores'=>91,

            ],
            [
                'name'=>'郑晓雨',
                'sid'=>'1511121774',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":19},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":22},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:03:54',
                'end'=>'2020-10-07 17:05:34',
                'source'=>74,
                't_score'=>18,
                'scores'=>92,

            ],
            [
                'name'=>'刘欣',
                'sid'=>'1511121786',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":18},{"key":"开机前准备（15）","value":13},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":15},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2020-10-07 14:03:59',
                'end'=>'2020-10-07 17:05:39',
                'source'=>60,
                't_score'=>15,
                'scores'=>75,

            ],
            [
                'name'=>'吕敏',
                'sid'=>'1511121682',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":14},{"key":"开机前准备（15）","value":10},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":12},{"key":"吹扫管理、取样（9）","value":5},{"key":"关机（6）","value":4}]',
                'start'=>'2020-10-07 14:05:42',
                'end'=>'2020-10-07 17:07:22',
                'source'=>50,
                't_score'=>15,
                'scores'=>65,

            ],
            [
                'name'=>'肖怡',
                'sid'=>'1511121646',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":14},{"key":"开机前准备（15）","value":10},{"key":"放样（6）","value":6},{"key":"参数设置、薄膜沉积（24）","value":22},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:05:42',
                'end'=>'2020-10-07 17:07:32',
                'source'=>66,
                't_score'=>18,
                'scores'=>84,

            ],
            [
                'name'=>'詹芳怡',
                'sid'=>'1511121678',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":19},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":21},{"key":"吹扫管理、取样（9）","value":8},{"key":"关机（6）","value":6}]',
                'start'=>'2020-10-07 14:06:26',
                'end'=>'2020-10-07 17:08:06',
                'source'=>73,
                't_score'=>18,
                'scores'=>91,

            ],
            [
                'name'=>'何佳琪',
                'sid'=>'1511121634',
                'school'=>'湖北工业大学',
                'experiment'=>'基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台',
                'data'=>'[{"key":"预习（20）","value":17},{"key":"开机前准备（15）","value":14},{"key":"放样（6）","value":5},{"key":"参数设置、薄膜沉积（24）","value":16},{"key":"吹扫管理、取样（9）","value":4},{"key":"关机（6）","value":4}]',
                'start'=>'2020-10-07 14:07:56',
                'end'=>'2020-10-07 17:10:12',
                'source'=>60,
                't_score'=>19,
                'scores'=>79,

            ],

        ];
//        foreach ($list as $v){
//            $info=[];
//            $student=Db::table('vr_student')->where('id',$v['sid'])->find();
//            $info['name']=$student['name'];
//            $info['sid']=$student['sid'];
//            $info['school']='湖北工业大学';
//            $info['experiment']='基于PEVCD的聚光光伏电池芯片薄膜沉积工艺虚拟仿真教学平台';
//            $info['data']=$v['data'];
//            $info['start']=date('Y-m-d H:i:s',strtotime($v['created'])+rand(32461,32701));
//            $info['end']=date('Y-m-d H:i:s',strtotime($info['start'])+rand(9000,12600));
//            $info['source']=$v['source'];
//            $info['t_score']=$v['t_score'];
//            $info['scores']=$v['source']+$v['t_score'];
//            $arr[]=$info;
//        }
        $csv_title = array('姓名','学号','学校/单位','实验名称','实验内容','实验开始时间','实验结束时间','实验得分','教师评分','总分');
        $csv->put_csv($arr,$csv_title);
    }



    private function re($code=0, $message='ok', $data=[]) {
        $re = ['code'=>$code, 'message'=>$message,'data'=>null];
        if (!empty($data))
            $re['data'] = $data;
        return json_encode($re,  320);
    }
    
}