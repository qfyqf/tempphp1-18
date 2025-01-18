<?php
/**
 * Created by PhpStorm.
 * User: xiaowenzi
 * Date: 2019/3/20
 * Time: 21:09
 */

namespace app\student\controller;


use app\admin\model\Admin;
use app\student\model\Student;
use think\Log;
use think\Request;
use think\Session;
use think\DB;
use app\student\model\Index as dbIndex;

class Index extends MyController
{
    protected $index;

    /**
     * 初始化相关资源
     * Index constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->index = new dbIndex();
    }

    /**
     * 获取学生姓名
     * @return mixed
     */
    public function getName()
    {
        $name = $this->index->getSName($this->studentId);
        return $name;
    }

    /**
     * 获取个人信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo()
    {
        //TODO 输出个人信息到页面
        $info = $this->index->getInfo($this->studentId);

        $info['className'] = DB::name('classinfo')->where('id',$info['classID'])->value('className');
        $this->assign('info', $info);
        return $this->fetch('index');
    }

    /**
     * 更新个人信息
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateInfo()
    {
        $param = Request::instance()->param();
        if ($param) {
            $result = $this->index->updateInfo($this->studentId, $param);
            if ($result) {
                $this->success('修改成功', 'index/getInfo');
            } else {
                $this->error('修改失败');
            }
        } else {
            $info = $this->index->getInfo($this->studentId);
            $this->assign('info', $info);
            return $this->fetch('update');
        }
    }

    //修改用户密码
    public function updatePassword()
    {
        if (Request::instance()->isPost()) {
            try {
                $params = Request::instance()->param();
                $password = trim($params['password'] ?? '');
                $repassword = trim($params['repassword'] ?? '');

                //校验参数
                if (empty($password)) throw new \Exception('新密码不能为空，请输入新密码。', 20000);
                if (empty($repassword)) throw new \Exception('请再次输入的新密码。', 20000);
                if ($password != $repassword) throw new \Exception('两次密码不一致，请重新输入。', 20000);

                //修改密码
                $updateData = [
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ];
                Student::updateInfo($this->studentId, $updateData);
            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . var_export($e->getTrace(),true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('修改成功', 'index/getInfo');
        } else {
            $title = ['name' => '修改密码', 'en' => 'Update Password'];

            // 副导航标志
            $this->assign('sign_sidenav', 'index/getInfo');
            $this->assign('title', $title);
            return $this->fetch('updatePassword');
        }
    }

    public function updatePwd()
    {
        $param = Request::instance()->param();
        if ($param) {
            $result = $this->index->updateInfo($this->studentId, $param);
            if ($result) {
                echo '成功';
            } else {
                echo '失败';
            }
        } else {
            $info = $this->index->getInfo($this->studentId);
            $this->assign('info', $info);
            return $this->fetch('update');
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if (Session::has('role')) {
            Session::delete('role');
        }

        if (Session::has('id')) {
            Session::delete('id');
        }
        $this->redirect('login');
    }
}
