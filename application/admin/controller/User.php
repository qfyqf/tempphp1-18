<?php
/**
 * User: hejun
 * Date: 2019/3/25
 * Time: 11:43
 */

namespace app\admin\controller;

use app\admin\model\Admin;
use think\Controller;
use think\Log;
use think\Request;
use think\Db;


class User extends Base
{

    public function index()
    {
        $title = ['name' => '个人信息', 'en' => 'Personel Infomation'];
        // 副导航标志
        $this->assign('sign_sidenav', 'user/index');
        $this->assign('title', $title);
        return $this->fetch();
    }

    public function upd()
    {
        $title = ['name' => '修改资料', 'en' => 'Update Info'];
        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            if (!$params['account'])
                $this->error('请将内容填完整');

            if (DB::name('admin')->where('id', $this->info['id'])->update($params))
                $this->success('修改成功', 'user/index');
            else
                $this->error('修改失败');
        }

        // 副导航标志
        $this->assign('sign_sidenav', 'user/upd');
        $this->assign('title', $title);
        return $this->fetch();
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
                Admin::updateInfo($this->info['id'], $updateData);
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
            $this->success('修改成功', 'user/index');
        } else {
            $title = ['name' => '修改密码', 'en' => 'Update Password'];

            // 副导航标志
            $this->assign('sign_sidenav', 'user/updatePassword');
            $this->assign('title', $title);
            return $this->fetch();
        }
    }


}