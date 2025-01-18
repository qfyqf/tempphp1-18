<?php
/**
 * User: hejun
 * Date: 2019/4/8
 * Time: 17:30
*/
namespace app\teach\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
use app\teach\model\Course;
use app\teach\model\Resource AS dbResource;


class Resource extends Base
{
    protected $dbCourse;
    protected $dbResource;

    public function _initialize(){
        $this->dbCourse = new Course();
        $this->dbResource = new dbResource();
    }

    // 资源库(知识点学习)
    public function index()
    {
        $where = 'a.auth = 1 AND a.teacher_id = '.$this->info['id'];
        $list = $this->dbResource->get_list($where, 10);

        // 副导航标志
        $this->assign('sign_sidenav','resource/index');
        $this->assign('list',$list);
        return $this->fetch();
    }

    // 资源库(拓展学习)
    public function index2()
    {
        $where = 'a.auth = 0 AND a.teacher_id = '.$this->info['id'];
        $list = $this->dbResource->get_list($where, 10);

        // 副导航标志
        $this->assign('sign_sidenav','resource/index');
        $this->assign('list',$list);
        return $this->fetch('index');
    }

    

    // 添加
    public function add(){
        $title = ['name' => '添加资源', 'en'=>'New Resource'];
        $course_list = $this->dbCourse->mycourselist($this->info['id']);

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();
            
            $file = request()->file('resource');

            // 上传到框架应用根目录/public/uploads/ 目录下
            if($file){
                $info = $file->validate(['ext'=>'jpg,png,gif,jpeg,doc,docx,xls,xlsx,pdf,mp3,mp4,wmv,avi,txt'])->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                    
                    $params['resource'] =  $info->getSaveName();
                    $params['resource'] =  str_replace("\\", "/", $params['resource']);
                    $params['sid'] = date('ymd'). str_pad(rand(1,999), 3, '0',STR_PAD_LEFT);

                    $params['teacher_id'] = $this->info['id'];
                    $params['add_time'] = date('Y-m-d H:i:s');

                    if ($this->dbResource->add($params)) {
                        $this->success('添加成功','resource/index');
                    }else{
                        $this->error('添加失败');
                    }
                    
                }else{
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
        }

        // 副导航标志
        $this->assign('sign_sidenav','resource/add');
        $this->assign('course_list',$course_list);
        $this->assign('title',$title);
        return $this->fetch();
    }

    // 查看
    public function item() {
        $title = ['name' => '查看资源', 'en'=>'View Resource'];
        $id = intval( Request::instance()->param('id') );
        $item = $this->dbResource->get_one($id);
        if (empty($item))
            $this->error('找不到资源');

        $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $item['resource'];
        $item['size'] = filesize($file_name)/1000;
        clearstatcache();
 
        $this->assign('sign_sidenav','resource/index');
        $this->assign('item',$item);
        $this->assign('title',$title);
        return $this->fetch();

    }

    // 下载
    public function download() {
        $id = intval( Request::instance()->param('id') );

        $item = $this->dbResource->get_one($id);
        if (empty($item))
            $this->error('找不到资源');

        $ext = strrchr($item['resource'], '.');

        $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $item['resource'];        //下载文件

        //检查文件是否存在
        if (! file_exists($file_name)) {
            exit("找不到资源");
        } else {
            //打开文件
            $file = fopen($file_name, "r");
            //输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: " . filesize($file_name));
            Header("Content-Disposition: attachment; filename=" . $item['name'] . $ext);
            //输出文件内容
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_name));
            fclose($file);
            exit();
        }
    }




}