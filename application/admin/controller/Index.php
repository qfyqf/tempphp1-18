<?php
/**
 * User: hejun
 * Date: 2019/3/18
 * Time: 11:43
 */

namespace app\admin\controller;

use app\admin\model\Admin;
use app\student\model\Student;
use app\teach\model\ClassInfo;
use app\teach\model\Teacher;
use app\teach\model\TeacherClass;
use think\{
    Controller, Request, Session, Db, Log
};

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Url;

class Index extends Base
{
    // 学生账号管理
    public function index()
    {
        $map['a.status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['name|sid'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $field = 'a.*, c.className';
        $list = DB::name('student')->alias('a')
            ->field($field)
            ->join('vr_classinfo c', 'c.id=a.classID', 'LEFT')
            ->where($map)
            ->order('a.id desc')
            ->paginate(10);

        $this->assign('list', $list);
        $this->assign('role', 0);
        return $this->fetch();
    }

    // 教师账号管理
    public function teacher()
    {
        $map = [];
        $map['status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['name|sid'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $list = DB::name('teacher')
            ->where($map)
            ->order('id desc')
            ->paginate(10);

        $teacherClassInfo =[];
        if (!empty($list->items())) {
            $teacherIds = $classIds = $classList = [];
            $teacherIds = array_unique(array_column($list->items(), 'id'));
            //批量获取老师对应的班级信息
            $teacherClassList = TeacherClass::getListByTeacherIds($teacherIds);
            if (!empty($teacherClassList)) {
                $classIds = array_unique(array_column($teacherClassList, 'class_id'));
                if (!empty($classIds)) {
                    $classList = ClassInfo::getListByIds($classIds);
                    if (!empty($classList)) {
                        $classList = array_column($classList, 'className', 'id');
                    }
                }

                //初始化
                foreach ($teacherIds as $teacherID) {
                    $teacherClassInfo[$teacherID] = '';
                }
                foreach ($teacherClassList as $item) {
                    $teacherClassInfo[$item['teacher_id']] .= $classList[$item['class_id']].',' ?? '';
                }

            }
        }


        $this->assign('list', $list);
        $this->assign('teacher_class_info', $teacherClassInfo);
        $this->assign('role', 1);

        return $this->fetch('index');
    }

    // 管理员账号管理
    public function admin()
    {
        $map['status'] = 1;
        if ($ss = Request::instance()->param('ss')) {
            $map['name|sid'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $list = DB::name('admin')->where($map)->order('id desc')->paginate(10);
        $this->assign('list', $list);
        $this->assign('role', 2);

        if ($this->info['id'] > 1) {
            return $this->fetch('admin');
        } else {
            return $this->fetch('index');
        }
    }

    // 班级管理
    public function classinfo()
    {
        $map['classID'] = ['gt', 0];
        if ($ss = Request::instance()->param('ss')) {
            $map['className'] = ['LIKE', '%' . $ss . '%'];
            $this->assign('ss', $ss);
        }
        $list = DB::name('classinfo')->where($map)->order('grade desc')->paginate(10);
        $this->assign('list', $list);
        return $this->fetch();
    }


    /*
     * 导入用户数据，文件格式：xls, xlsx
     * 导入字段依次为：
     * 类型（学生，老师）
     * 编号
     * 姓名
     * 账号
     * 密码
     * 性别
     * 电话
     */
    public function import_user()
    {
        $title = ['name' => '用户导入', 'en' => 'User Import'];
        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            $file = request()->file('resource');

            // 上传到框架应用根目录/public/uploads/ 目录下
            if ($file) {
                $info = $file->validate(['size' => 15678000, 'ext' => 'xls,xlsx'])->move(ROOT_PATH . 'public' . DS . 'excel');
                if ($info) {
                    $file = './excel/' . $info->getSaveName();
                    $re = $this->readExcel($file);
                    array_shift($re);
                    foreach ($re as $v) {
                        // 判断账号是否存在
                        $is_student = DB::name('student')->field('id, account')->where('account', $v[3])->find();
                        if (!empty($is_student)) {
                            echo '账号【' . $v[3] . '】已存在，不能重复导入！<br>';
                            continue;
                        }

                        $is_teacher = DB::name('teacher')->field('id, account')->where('account', $v[3])->find();
                        if (!empty($is_teacher)) {
                            echo '账号【' . $v[3] . '】已存在，不能重复导入！<br>';
                            continue;
                        }

                        $is_admin = DB::name('admin')->field('id, account')->where('account', $v[3])->find();
                        if (!empty($is_admin)) {
                            echo '账号【' . $v[3] . '】已存在，不能重复导入！<br>';
                            continue;
                        }

                        $add['sid'] = $v[1];
                        $add['name'] = $v[2];
                        $add['account'] = $v[3];
                        $add['password'] = empty($v[4]) ? password_hash($v[1], PASSWORD_DEFAULT) : password_hash($v[4], PASSWORD_DEFAULT);
                        $add['sex'] = ($v[6] == '男') ? 1 : 0;
                        // $add['phone'] = $v[7];


                        // 班级
                        $classinfo = DB::name('classinfo')->where('className', $v['5'])->find();
                        if (empty($classinfo)) {
                            echo '班级错误【' . $v['3'] . '】导入失败！<br>';
                            continue;
                        } else {
                            $add['classID'] = $classinfo['id'];
                        }

                        if ($v[0] == '学生') {
                            if (DB::name('student')->insert($add)) {
                                echo '学生账号【' . $v[1] . $v[3] . '】导入成功！<br>';
                            } else {
                                echo '学生账号【' . $v[1] . $v[3] . '】导入失败！<br>';
                            }
                        } elseif ($v[0] == '老师') {

                            if (DB::name('teacher')->insert($add)) {
                                echo '老师账号【' . $v[3] . '】导入成功！<br>';
                            } else {
                                echo '老师账号【' . $v[3] . '】导入失败！<br>';
                            }


                        } else {
                            echo '未知账号【' . $v[3] . '】导入失败！<br>';
                        }
                    }
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            exit();
        }

        $this->assign('title', $title);
        return $this->fetch();
    }

    public function readExcel($file, $sheet = 0, $columnCnt = 0)
    {

        if (empty($file) or !file_exists($file)) {
            $this->error('文件不存在');
        }

        $objRead = IOFactory::createReader('Xlsx');

        if (!$objRead->canRead($file)) {
            $objRead = IOFactory::createReader('Xls');
            if (!$objRead->canRead($file)) {
                $this->error('只支持导入Excel文件');
            }
        }
        $objRead->setReadDataOnly(true);

        /* 建立excel对象 */
        $obj = $objRead->load($file);
        /* 获取指定的sheet表 */
        $currSheet = $obj->getSheet($sheet);

        if (0 == $columnCnt) {
            /* 取得最大的列号 */
            $columnH = $currSheet->getHighestColumn();
            /* 兼容原逻辑，循环时使用的是小于等于 */
            $columnCnt = Coordinate::columnIndexFromString($columnH);
        }

        /* 获取总行数 */
        $rowCnt = $currSheet->getHighestRow();
        $data = [];

        /* 读取内容 */
        for ($_row = 1; $_row <= $rowCnt; $_row++) {
            for ($_column = 1; $_column <= $columnCnt; $_column++) {
                $cellName = Coordinate::stringFromColumnIndex($_column);
                $cellId = $cellName . $_row;
                $cell = $currSheet->getCell($cellId);
                $tmp[] = $cell->getValue();
            }
            $data[] = $tmp;
            $tmp = array();
        }

        return $data;


    }

    public function addaccount()
    {
        $title = ['name' => '添加账号', 'en' => 'Add Account'];

        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();

            if (!$params['sid'] || !$params['name'] || !$params['account'] || !$params['password'])
                $this->error('请将内容填完整');

            switch ($params['role']) {
                case 0:
                    $role = 'student';
                    $redirect = 'index/index';
                    break;
                case 1:
                    $role = 'teacher';
                    $redirect = 'index/teacher';
                    break;
                case 2:

                    if ($this->info['id'] != 1) {
                        $this->error('没有权限');
                    }
                    $role = 'admin';
                    $redirect = 'index/admin';
                    unset($params['classID']);
                    break;
                default:
                    break;
            }

            unset($params['role']);
            $params['password'] = password_hash($params['password'], PASSWORD_DEFAULT);

            if (DB::name($role)->insert($params))
                $this->success('添加成功', $redirect);
            else
                $this->error('添加失败');
        }
        $this->assign('title', $title);
        $this->assign('classlist', $classList);
        $this->assign('role', input('param.role'));
        return $this->fetch();
    }

    public function updaccount()
    {
        $title = ['name' => '修改信息', 'en' => 'Edit Personel Account'];
        $params = Request::instance()->param();
        switch ($params['role']) {
            case 0:
                $role = 'student';
                $rolename = '学生';
                $redirect = 'index/index';
                break;
            case 1:
                $role = 'teacher';
                $rolename = '教师';
                $redirect = 'index/teacher';
                break;
            case 2:
                $role = 'admin';
                $rolename = '管理员';
                $redirect = 'index/admin';
                break;
            default:
                break;
        }

        $item = DB::name($role)->where('id', $params['id'])->find();

        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            $id = $params['id'];
            unset($params['role']);
            unset($params['id']);

            if (DB::name($role)->where('id', $id)->update($params))
                $this->success('修改成功', $redirect);
            else
                $this->error('修改失败');
        }
        $this->assign('title', $title);
        $this->assign('item', $item);

        $this->assign('role', input('param.role'));
        $this->assign('roleid', $params['role']);

        $this->assign('rolename', $rolename);
        $this->assign('classlist', $classList);
        return $this->fetch();
    }

    public function delaccount()
    {

        $params = Request::instance()->param();
        $id = intval($params['id']);
        switch ($params['role']) {
            case 0:
                $role = 'student';
                $rolename = '学生';
                $redirect = 'index/index';
                break;
            case 1:
                $role = 'teacher';
                $rolename = '教师';
                $redirect = 'index/teacher';
                break;
            case 2:
                $this->error('操作错误');
                break;
            default:
                $this->error('操作错误');
                break;
        }

        DB::startTrans();
        try {
            DB::name($role)->where('id', $id)->update(['status' => 0]);
            if ($params['role'] == 1) {
                //删除老师，需要删除删除老师与班级的绑定关系
                TeacherClass::clearByTeacherID($id);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            if ($e->getCode() == 20000) {
                $this->error($e->getMessage());
            } else {
                Log::error('异常文件：' . $e->getFile());
                Log::error('异常行号：' . $e->getLine());
                Log::error('异常信息：' . $e->getMessage());
                Log::error('异常代码：' . $e->getCode());
                Log::error('异常trace：' . print_r($e->getTrace(), true));
                $this->error('服务异常，请联系管理员');
            }
        }
        $this->success('删除成功', $redirect);


    }

    public function delaccounts()
    {
        $params = Request::instance()->param();
        switch ($params['role']) {
            case 0:
                $role = 'student';
                $rolename = '学生';
                $redirect = 'index/index';
                break;
            case 1:
                $role = 'teacher';
                $rolename = '教师';
                $redirect = 'index/teacher';
                break;
            case 2:
                $this->error('操作错误');
                break;
            default:
                $this->error('操作错误');
                break;
        }
        $ids = explode('_', $params['ids']);
        if (empty($ids)) $this->error('请选择删除对象');

        DB::startTrans();
        try {
            DB::name($role)->where('id', 'in', $ids)->update(['status' => 0]);
            if ($params['role'] == 1) {
                //删除老师，需要删除删除老师与班级的绑定关系
                TeacherClass::clearByTeacherIds($ids);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            if ($e->getCode() == 20000) {
                $this->error($e->getMessage());
            } else {
                Log::error('异常文件：' . $e->getFile());
                Log::error('异常行号：' . $e->getLine());
                Log::error('异常信息：' . $e->getMessage());
                Log::error('异常代码：' . $e->getCode());
                Log::error('异常trace：' . print_r($e->getTrace(), true));
                $this->error('服务异常，请联系管理员');
            }
        }
        $this->success('删除成功', $redirect);
    }


    public function addclass()
    {
        $title = ['name' => '添加班级', 'en' => 'Add Class'];

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();

            if (!$params['classID'] || !$params['className'])
                $this->error('请将内容填完整');

            if (DB::name('classinfo')->insert($params))
                $this->success('添加成功', 'index/classinfo');
            else
                $this->error('添加失败');
        }
        $this->assign('title', $title);
        return $this->fetch();
    }


    public function updclass()
    {
        $title = ['name' => '修改班级', 'en' => 'Upd Class'];
        $params = Request::instance()->param();

        $item = DB::name('classinfo')->where('classID', $params['id'])->find();

        if (Request::instance()->isPost()) {
            $params = Request::instance()->param();
            $id = $params['id'];
            unset($params['id']);

            if (DB::name('classinfo')->where('classID', $id)->update($params))
                $this->success('修改成功', 'index/classinfo');
            else
                $this->error('修改失败');
        }
        $this->assign('title', $title);
        $this->assign('item', $item);
        return $this->fetch();
    }

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
        }
    }

    public function logout()
    {
        if (Session::has('role')) Session::delete('role');
        if (Session::has('id')) Session::delete('id');
        $this->redirect('/');
    }

    //管理员修改学生、老师密码
    public function updatePassword()
    {
        $params = Request::instance()->param();
        $ID = intval($params['id']);
        $roleID = intval($params['role']);
        $password = trim($params['password'] ?? '');
        $repassword = trim($params['repassword'] ?? '');

        if (Request::instance()->isPost()) {
            try {
                //校验参数
                if ($ID < 1) throw new \Exception('ID参数错误', 20000);
                if (!in_array($roleID, [0, 1])) throw new \Exception('Role参数错误', 20000);
                if (empty($password)) throw new \Exception('新密码不能为空，请输入新密码。', 20000);
                if (empty($repassword)) throw new \Exception('请再次输入的新密码。', 20000);
                if ($password != $repassword) throw new \Exception('两次密码不一致，请重新输入。', 20000);

                //修改密码
                $updateData = [
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ];

                if ($roleID == 0) {
                    $userInfo = Student::getInfo($ID);
                    if (empty($userInfo)) throw new \Exception('学生信息不存在', 20000);
                    Student::updateInfo($ID, $updateData);
                    $jumpUrl = 'index/index';
                } else {
                    $userInfo = Teacher::getInfo($ID);
                    if (empty($userInfo)) throw new \Exception('老师信息不存在', 20000);
                    Teacher::updateInfo($ID, $updateData);
                    $jumpUrl = 'index/teacher';
                }

            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('修改成功', $jumpUrl);

        } else {
            if ($ID < 1) $this->error('ID参数错误');
            if (!in_array($roleID, [0, 1])) $this->error('Role参数错误');

            $roleName = '';
            $userInfo = [];
            if ($roleID == 0) {
                $roleName = '学生';
                $userInfo = Student::getInfo($ID);
                if (empty($userInfo)) $this->error('学生信息不存在');
            } else {
                $roleName = '老师';
                $userInfo = Teacher::getInfo($ID);
                if (empty($userInfo)) $this->error('老师信息不存在');
            }

            $title = ['name' => '修改' . $roleName . '密码', 'en' => 'Update Password'];
            $this->assign('user_id', $ID);
            $this->assign('user_role', $roleID);
            $this->assign('role_name', $roleName);
            $this->assign('user_info', $userInfo);
            $this->assign('title', $title);
            return $this->fetch();
        }
    }

    //添加学生
    public function addStudent()
    {
        //获取班级
        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            try {
                $params = Request::instance()->param();
                $sid = intval($params['sid'] ?? 0);
                $name = trim($params['name'] ?? '');
                $account = trim($params['account'] ?? '');
                $password = trim($params['password'] ?? '');
                $classID = intval($params['classID'] ?? 0);
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $birthday = trim($params['birthday'] ?? '');
                $address = trim($params['address'] ?? '');

                //参数校验
                //if ($sid < 1) throw new \Exception('学生学号格式错误', 20000);
                if (empty($name)) throw new \Exception('学生姓名不能为空', 20000);
                if (empty($account)) throw new \Exception('学生账号不能为空', 20000);
                if (empty($password)) throw new \Exception('学生密码不能为空', 20000);
                if ($classID < 1) throw new \Exception('请选择班级', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $studentInfo = Student::getInfoByAccount($account);
                $teacherInfo = Teacher::getInfoByAccount($account);
                $adminInfo = Admin::getInfoByAccount($account);
                if (!empty($studentInfo) || !empty($teacherInfo) || !empty($adminInfo)) throw new \Exception('账号信息已存在', 20000);

                //--2.校验班级
                $classIds = array_unique(array_column($classList, 'id'));
                if (!in_array($classID, $classIds)) throw new \Exception('请选择正确的班级信息', 20000);

                //写入数据
                $insertData = [
                    'sid' => $sid,
                    'name' => $name,
                    'account' => $account,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => '',
                    'homepage' => '',
                    'address' => $address,
                    'classID' => $classID,
                    'status' => 1,
                ];

                Student::insert($insertData);
                $jumpUrl = 'index/index';

            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('添加学生成功', $jumpUrl);
        } else {
            $title = ['name' => '添加学生', 'en' => 'Add Student'];
            $this->assign('title', $title);
            $this->assign('classlist', $classList);
            $this->assign('role', 0);
            return $this->fetch();
        }
    }

    //编辑学生
    public function editStudent()
    {
        $params = Request::instance()->param();

        //获取班级
        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            try {
                $ID = intval($params['id'] ?? 0);
                $role = intval($params['role'] ?? 0);
                $sid = intval($params['sid'] ?? 0);
                $name = trim($params['name'] ?? '');
                //$account = trim($params['account'] ?? '');
                //$password = trim($params['password'] ?? '');
                $classID = intval($params['classID'] ?? 0);
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $birthday = trim($params['birthday'] ?? '');
                $address = trim($params['address'] ?? '');

                //参数校验
                if ($ID < 1) throw new \Exception('学生ID参数错误', 20000);
                if ($role != 0) throw new \Exception('学生角色错误', 20000);
                //if ($sid < 1) throw new \Exception('学生学号格式错误', 20000);
                if (empty($name)) throw new \Exception('学生姓名不能为空', 20000);
                //if (empty($account)) throw new \Exception('学生账号不能为空', 20000);
                //if (empty($password)) throw new \Exception('学生密码不能为空', 20000);
                if ($classID < 1) throw new \Exception('请选择班级', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $studentInfo = Student::getInfo($ID);
                if (empty($studentInfo)) throw new \Exception('学生信息不存在', 20000);

                //--2.校验班级
                $classIds = array_unique(array_column($classList, 'id'));
                if (!in_array($classID, $classIds)) throw new \Exception('请选择正确的班级信息', 20000);

                //更新数据
                $updateData = [
                    'sid' => $sid,
                    'name' => $name,
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => '',
                    'homepage' => '',
                    'address' => $address,
                    'classID' => $classID,
                    'status' => 1,
                ];

                Student::updateInfo($ID, $updateData);
                $jumpUrl = Url::build('admin/index/editStudent', ['id' => $ID, 'role' => 0]);
            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('更新学生信息成功', $jumpUrl);
        } else {
            $title = ['name' => '编辑学生信息', 'en' => 'Edit Student Infomation'];
            $studnetID = intval($params['id'] ?? 0);
            $role = intval($params['role'] ?? 0);

            if ($studnetID < 1) $this->error('学生ID参数错误');
            if ($role != 0) $this->error('学生角色参数错误');

            $studentInfo = Student::getInfo($studnetID);
            if (empty($studentInfo)) $this->error('学生信息不存在');

            $this->assign('title', $title);
            $this->assign('classlist', $classList);
            $this->assign('role', 0);
            $this->assign('student_info', $studentInfo);
            return $this->fetch();
        }
    }

    //添加教师
    public function addTeacher()
    {
        //获取班级
        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            DB::startTrans();
            try {
                $params = Request::instance()->param();
                $name = trim($params['name'] ?? '');
                $account = trim($params['account'] ?? '');
                $password = trim($params['password'] ?? '');
                $classIds = (array)($params['classID'] ?? []);
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $degree = trim($params['degree'] ?? '');
                $homepage = trim($params['homepage'] ?? '');
                $birthday = trim($params['birthday'] ?? '');
                $address = trim($params['address'] ?? '');

                //参数校验
                if (empty($name)) throw new \Exception('教师姓名不能为空', 20000);
                if (empty($account)) throw new \Exception('教师账号不能为空', 20000);
                if (empty($password)) throw new \Exception('教师密码不能为空', 20000);
                if (empty($classIds)) throw new \Exception('请选择班级', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $studentInfo = Student::getInfoByAccount($account);
                $teacherInfo = Teacher::getInfoByAccount($account);
                $adminInfo = Admin::getInfoByAccount($account);
                if (!empty($studentInfo) || !empty($teacherInfo) || !empty($adminInfo)) throw new \Exception('账号信息已存在', 20000);

                //--2.校验班级
                $allClassIds = array_unique(array_column($classList, 'id'));
                foreach ($classIds as $classID) {
                    if (!in_array($classID, $allClassIds)) throw new \Exception('请选择正确的班级信息', 20000);
                }

                //写入教师基本信息
                $insertData = [
                    'sid' => 0,
                    'name' => $name,
                    'account' => $account,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => $degree,
                    'homepage' => $homepage,
                    'address' => $address,
                    'classID' => 0,//已经废弃，教师班级关系已经转移到vr_teacher_class表中
                    'status' => 1,
                ];
                $teacherID = Teacher::insertGetId($insertData);

                //写入教师班级信息
                $insertData = [];
                foreach ($classIds as $classID) {
                    $insertData[] = [
                        'teacher_id' => $teacherID,
                        'class_id' => $classID,
                        'create_time' => time(),
                        'status' => 1,
                    ];
                }
                TeacherClass::insertAll($insertData);
                DB::commit();
                $jumpUrl = 'index/teacher';
            } catch (\Exception $e) {
                DB::rollback();

                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('添加老师成功', $jumpUrl);
        } else {
            $title = ['name' => '添加教师', 'en' => 'Add Teacher'];
            $this->assign('title', $title);
            $this->assign('classlist', $classList);
            $this->assign('role', 0);
            return $this->fetch();
        }
    }

    //编辑教师
    public function editTeacher()
    {
        $params = Request::instance()->param();

        //获取班级
        $classList = DB::name('classinfo')->order('grade desc')->limit(100)->select();

        if (Request::instance()->isPost()) {
            DB::startTrans();
            try {
                $ID = intval($params['id'] ?? 0);
                $role = intval($params['role'] ?? 0);
                //$sid = intval($params['sid'] ?? 0);
                $name = trim($params['name'] ?? '');
                //$account = trim($params['account'] ?? '');
                //$password = trim($params['password'] ?? '');
                $classIds = (array)($params['classID'] ?? []);
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $birthday = trim($params['birthday'] ?? '');
                $address = trim($params['address'] ?? '');
                $degree = trim($params['degree'] ?? '');
                $homepage = trim($params['homepage'] ?? '');

                //参数校验
                if ($ID < 1) throw new \Exception('教师ID参数错误', 20000);
                if ($role != 1) throw new \Exception('教师角色错误', 20000);
                //if ($sid < 1) throw new \Exception('学生学号格式错误', 20000);
                if (empty($name)) throw new \Exception('教师姓名不能为空', 20000);
                //if (empty($account)) throw new \Exception('学生账号不能为空', 20000);
                //if (empty($password)) throw new \Exception('学生密码不能为空', 20000);
                if (empty($classIds)) throw new \Exception('请选择班级', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $teacherInfo = Teacher::getInfo($ID);
                if (empty($teacherInfo)) throw new \Exception('教师信息不存在', 20000);

                //--2.校验班级
                $allClassIds = array_unique(array_column($classList, 'id'));
                foreach ($classIds as $classID) {
                    if (!in_array($classID, $allClassIds)) throw new \Exception('请选择正确的班级信息', 20000);
                }


                //--1.更新教师基本信息
                $updateData = [
                    'sid' => 0,
                    'name' => $name,
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => $degree,
                    'homepage' => $homepage,
                    'address' => $address,
                    'classID' => 0,//已经废弃，教师班级关系已经转移到vr_teacher_class表中
                    'status' => 1,
                ];

                Teacher::updateInfo($ID, $updateData);
                //--2.更新教师班级信息
                TeacherClass::clearByTeacherID($ID);

                $insertData = [];
                foreach ($classIds as $classID) {
                    $insertData[] = [
                        'teacher_id' => $ID,
                        'class_id' => $classID,
                        'create_time' => time(),
                        'status' => 1,
                    ];
                }
                TeacherClass::insertAll($insertData);
                DB::commit();
                $jumpUrl = Url::build('admin/index/editTeacher', ['id' => $ID, 'role' => 1]);
            } catch (\Exception $e) {
                DB::rollback();

                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('更新教师信息成功', $jumpUrl);
        } else {
            $title = ['name' => '编辑教师信息', 'en' => 'Edit Teacher Infomation'];
            $teacherID = intval($params['id'] ?? 0);
            $role = intval($params['role'] ?? 0);

            if ($teacherID < 1) $this->error('教师ID参数错误');
            if ($role != 1) $this->error('教师角色参数错误');

            //获取教师信息
            $teacherInfo = Teacher::getInfo($teacherID);
            if (empty($teacherInfo)) $this->error('教师信息不存在');

            //获取教师的班级信息
            $teacherClassIds = [];
            $teacherClassList = TeacherClass::getListByTeacherID($teacherID);
            if (!empty($teacherClassList)) $teacherClassIds = array_unique(array_column($teacherClassList, 'class_id'));

            $this->assign('title', $title);
            $this->assign('classlist', $classList);
            $this->assign('role', 1);
            $this->assign('teacher_info', $teacherInfo);
            $this->assign('teacher_classIds', $teacherClassIds);
            return $this->fetch();
        }
    }

    //添加管理员
    public function addAdmin()
    {
        if (Request::instance()->isPost()) {
            try {
                $params = Request::instance()->param();
                $name = trim($params['name'] ?? '');
                $account = trim($params['account'] ?? '');
                $password = trim($params['password'] ?? '');
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $address = trim($params['address'] ?? '');

                //参数校验
                if (empty($name)) throw new \Exception('教师姓名不能为空', 20000);
                if (empty($account)) throw new \Exception('教师账号不能为空', 20000);
                if (empty($password)) throw new \Exception('教师密码不能为空', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $studentInfo = Student::getInfoByAccount($account);
                $teacherInfo = Teacher::getInfoByAccount($account);
                $adminInfo = Admin::getInfoByAccount($account);
                if (!empty($studentInfo) || !empty($teacherInfo) || !empty($adminInfo)) throw new \Exception('账号信息已存在', 20000);

                //写入数据
                $insertData = [
                    'sid' => 0,
                    'name' => $name,
                    'account' => $account,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => '',
                    'homepage' => '',
                    'address' => $address,
                    'status' => 1,
                ];
                Admin::insert($insertData);
                $jumpUrl = 'index/admin';
            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('添加管理员成功', $jumpUrl);
        } else {
            $title = ['name' => '添加管理员', 'en' => 'Add Administrator'];
            $this->assign('title', $title);
            $this->assign('role', 2);
            return $this->fetch();
        }
    }

    //编辑管理员
    public function editAdmin()
    {
        $params = Request::instance()->param();

        if (Request::instance()->isPost()) {
            try {
                $ID = intval($params['id'] ?? 0);
                $role = intval($params['role'] ?? 0);
                $name = trim($params['name'] ?? '');
                //---------------------------------------------
                $sex = intval($params['sex'] ?? 0);
                $email = trim($params['email'] ?? '');
                $phone = trim($params['phone'] ?? '');
                $birthday = trim($params['birthday'] ?? '');
                $address = trim($params['address'] ?? '');
                $degree = trim($params['degree'] ?? '');
                $homepage = trim($params['homepage'] ?? '');

                //参数校验
                if ($ID < 1) throw new \Exception('教师ID参数错误', 20000);
                if ($role != 2) throw new \Exception('教师角色错误', 20000);
                if (empty($name)) throw new \Exception('教师姓名不能为空', 20000);

                //数据有效性校验
                //--1.校验登录账号
                $adminInfo = Admin::getInfo($ID);
                if (empty($adminInfo)) throw new \Exception('管理员信息不存在', 20000);

                //更新数据
                $updateData = [
                    'sid' => 0,
                    'name' => $name,
                    'email' => $email,
                    'sex' => $sex,
                    'phone' => $phone,
                    'birthday' => !empty($birthday) ? $birthday : date('Y-m-d'),
                    'card_id' => '',
                    'degree' => $degree,
                    'homepage' => $homepage,
                    'address' => $address,
                    'status' => 1,
                ];

                Admin::updateInfo($ID, $updateData);
                $jumpUrl = Url::build('admin/index/editAdmin', ['id' => $ID, 'role' => 2]);
            } catch (\Exception $e) {
                if ($e->getCode() == 20000) {
                    $this->error($e->getMessage());
                } else {
                    Log::error('异常文件：' . $e->getFile());
                    Log::error('异常行号：' . $e->getLine());
                    Log::error('异常信息：' . $e->getMessage());
                    Log::error('异常代码：' . $e->getCode());
                    Log::error('异常trace：' . print_r($e->getTrace(), true));
                    $this->error('服务异常，请联系管理员');
                }
            }
            $this->success('更新管理员信息成功', $jumpUrl);
        } else {
            $title = ['name' => '编辑管理员信息', 'en' => 'Edit Administrator Infomation'];
            $adminID = intval($params['id'] ?? 0);
            $role = intval($params['role'] ?? 0);

            if ($adminID < 1) $this->error('管理员ID参数错误');
            if ($role != 2) $this->error('管理员角色参数错误');

            $adminInfo = Admin::getInfo($adminID);
            if (empty($adminInfo)) $this->error('管理员信息不存在');

            $this->assign('title', $title);
            $this->assign('role', 1);
            $this->assign('admin_info', $adminInfo);
            return $this->fetch();
        }
    }

}