<?php

namespace app\index\controller;

use app\index\model\Help;
use app\index\model\User;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use \IlabJwt;

class Index extends Controller
{
    protected $help;
    protected $user;
    public $role;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->help = new Help();
        $this->user = new User();
        $role = Session::get('role');
        if(!isset($role) || is_null($role)) {
            Session::set('role', 0);
        }

        $this->role = Session::get('role');
    }

    /**
     * 首页
     * @return \think\response\View
     */
    public function index() {
        // $this->redirect('http://wjgt.our360vr.com/home');
        $this->redirect('/');
    }


    /**
     * 登录页面 1.0
     * @return \think\response\View
     */
    public function index1() {
        return view('index1');
    }

    /**
     * 登录页面 2.0
     * @return \think\response\View
     */
    public function index2() {
        return view('index2');
    }

    /*
     * 网站内容页
     */
    public function page() {
        $id = intval( Request::instance()->param('id') );
        $id = max(1,$id);
        $item = DB::name('page')->where(['id'=>$id, 'status'=>1])->find();
        if (empty($item)) {
            $this->error('data error');
        }

        $this->assign('item', $item);
        return $this->fetch('page');

    }


    /**
     * 登录处理
     * role： 0 学生，1 老师，2 管理员
     */
    public function login()
    {
        //获取表单参数
        $request = Request::instance()->param();
        $password = $request['password'];


        if (is_null($password) || empty($password)) {
            $this->error('登录失败，用户名或密码错误');
        }

        $map = ['account'=>$request['account'], 'status'=>1];

        $admin = DB::name('admin')->where($map)->find();
        if (!empty($admin)) {
            $role = 2;
            $user = $admin;
            $redirect = 'admin/index/index';
        }else{
            $teacher = DB::name('teacher')->where($map)->find();
            if (!empty($teacher)) {
                $role = 1;
                $user = $teacher;
                $redirect = 'teach/index/index';
            }else{
                $student = DB::name('student')->where($map)->find();
                if (!empty($student)) {
                    $role = 0;
                    $user = $student;
                    $redirect = 'student/navigation/index';
                }else{
                    $this->error('登录失败，用户名或密码错误');
                }
            }
        }

        if (password_verify($password,$user['password'])) {
            Session::set('role', $role);
            Session::set('name', $user['name']);
            Session::set('id', $user['id']);
            $this->success('登录成功', $redirect);
        }else{
            $this->error('登录失败，用户名或密码错误');
        }
    }


    /**
     * 帮助页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function help()
    {
        $type = intval(Request::instance()->param('type'));
        $result = $this->help->getTitle($type);
        $this->assign('title', $result['title']);
        $this->assign('type',  $type);
        if (isset($result['subTitle'])) {
            $this->assign('subTitle', $result['subTitle']);
        }
        return $this->fetch('help');
    }

    /**
     * 帮助内容
     * @param $id int 当前页ID
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function content($id)
    {
        $type = intval(Request::instance()->param('type'));
        $result = $this->help->getTitle($type);
        $this->assign('type',  $type);
        if (isset($result['subTitle'])) {
            $this->assign('subTitle', $result['subTitle']);
        }
        $this->assign('title', $result['title']);

        $content = $this->help->getContent($id, $type);

        $this->assign('position', $content);
        return $this->fetch('content');
    }

    /**
     * @param $id int 当前页ID
     * @param $operate int 操作,1，上一页；2，下一页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function skip($id, $operate)
    {
        $type = intval(Request::instance()->param('type'));
        $position = $this->help->skip($id, $type, $operate);
        $result = $this->help->getTitle($type);
        $this->assign('type',  $type);
        if (isset($result['subTitle'])) {
            $this->assign('subTitle', $result['subTitle']);
        }
        $this->assign('title', $result['title']);
        if ($position == -1) {
            $this->error('已到最后一页');
        } elseif ($position == 0) {
            return $this->fetch('help');
        } elseif ($position == -2) {
            $this->error('出错');
        } else {
            $this->assign('position', $position);
            return $this->fetch('content');
        }
    }

    /**
     * 实验平台获取用户数据
     * http://116.62.42.12:801/TeachingPlatform/public/index/index/getuserinfo
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo()
    {
        $data = $this->user->getUserInfo(Session::get('id'), $this->role);
        $data['type'] = $this->role;
        if ($data['type'] == 1) {
            $data['classID'] = null;
        }
        echo json_encode($data);
        exit;
    }

    /**
     * 实验平台获取用户数据
     * http://116.62.42.12:801/TeachingPlatform/public/index/index/getImage?file=[]
     */
    public function getImage()
    {

        $file = Request::instance()->param('file');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");

        header('Access-Control-Allow-Methods:HEAD,GET,POST,OPTIONS,PATCH,PUT,DELETE');
        header('Access-Control-Allow-Headers:Origin,X-Requested-With,Authorization,Content-Type,Accept,Z-Key');
        var_Dump($file);
        exit;
    }


    // 实验空间账号注册生成账号
    public function test(){
        $nickname = DB::name('names')->orderRaw('rand()')->limit(1)->find(); 
        $nickname = trim($nickname['name'],"&ensp; ");
        if (strlen($nickname)<=2) {
            $nickname = $nickname.str_pad(rand(0,99), 2, '0', STR_PAD_LEFT);
            $password = $nickname.str_pad(rand(0,99999999), 8, '0', STR_PAD_LEFT);
        }elseif (strlen($nickname)<=4) {
            $nickname = $nickname.str_pad(rand(0,99), 2, '0', STR_PAD_LEFT);
            $password = $nickname.str_pad(rand(0,999999), 6, '0', STR_PAD_LEFT);
        }elseif (strlen($nickname)<=6) {
            $password = $nickname.str_pad(rand(0,9999), 4, '0', STR_PAD_LEFT);
        }else{
            $password = $nickname.str_pad(rand(0,99), 2, '0', STR_PAD_LEFT);
        }

        $nickname = ucfirst($nickname);

        $this->assign('nickname',  $nickname);
        $this->assign('password',  $password);
        return view('test');
    }

    // 读取文件
    public function readfile() {
        $file = "./teach/ename.txt";

        $myfile = fopen($file,'r');

        while(!feof($myfile)) {
            $name = trim(fgets($myfile));
            if ($name!='') {
                if (DB::name('names')->insert(['name'=>$name])) {
                    echo $name.' 保存成功！<br>';
                }
            }
        }
        fclose($myfile);
    }

    // 保存实验空间注册信息
    public function reginfo() {
        $param = Request::instance()->param();

        $data = array(
            'phone' => $param['phone'],
            'nickname' => $param['nickname'],
            'password' => $param['password'],
            'createtime' => date('Y-m-d H:i:s')
        );

        if (DB::name('account_ilab')->insert($data)) {
            echo $param['phone'].':注册成功！';
        }else{
            echo $param['phone'].':注册失败！';
        }
    }


    // 虚拟实验 或者 设置内容和图片接口
    public function get_lab_option(){
        $id = intval(input('get.id'));
        if ($id<=0) 
            exit('error');

        $exp = DB::name('experiment')->field('id, name, option_imgs, option_strs')->where('id',$id)->find();
        if (empty($exp))
            exit('error');

        // 1 文本， 2 图片
        $type = input('get.type');
        if ($type==2){
            echo $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME'])).'/uploads/'.$exp['option_imgs'];
        }else{
            echo $exp['option_strs'];
        }
    }
}
