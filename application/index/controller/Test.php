<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;

class Test extends Controller {

    public function index() {
        echo '测试控制器';
    }

    public function u3d_sendform() {
        $re = ['code'=>0, 'message'=>'ok' ];
        $student = Session::get();

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();
            $params['student'] = isset($student['id']) ? $student['id'] : 0 ;
            if (!$params['content']){
                $re = ['code'=>2, 'message'=>'内容不能为空' ];
                return json_encode($re,  JSON_UNESCAPED_UNICODE);
            }
            $params['createtime'] = date('Y-m-d H:i:s');
            $params['arrange_id'] = 0;

            if (DB::name('u3d_sendfrom')->insert($params)) {
                return json_encode($re,  JSON_UNESCAPED_UNICODE);
            }else{
                $re = ['code'=>2, 'message'=>'提交失败' ];
                return json_encode($re,  JSON_UNESCAPED_UNICODE);
            }

        }

        return $this->fetch();
    }

    public function readbase64() {
        $id = Request::instance()->param('id');
        if (!$id)
            exit('error');
        $img = DB::name('images')->where('id',$id)->find();
        if (empty($img)){
            exit('error');
        }else{
            echo '<img src="data:image/png;base64,'.$img['dat'].'">';
        }
    }

    // 客户端模拟上传文件图片接口
    public function upload() {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $info = $file->validate(['size'=>15678000,'ext'=>'jpg,png,gif,jpeg,doc,docx,xls,xlsx,pdf'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $path =  $info->getSaveName();
                $path = 'uploads' . DS . $path;
                return $path;
                exit;
            } else {
                // 上传失败获取错误信息
                return $file->getError();
                exit;
            }
        }else{
            return 'error';
            exit;
        }
    }


}