<?php
/**
 * User: hejun
 * Date: 2019/3/18
 * Time: 11:43
 */

namespace app\teach\controller;

use app\admin\model\Admin;
use app\student\model\{
    Student, StudentOption
};
use app\teach\model\{
    ClassInfo, Course, Teacher
};
use think\{
    Controller, Log, Request, Session, Db, Url
};

use app\teach\model\Course as dbCourse;
use PhpOffice\PhpSpreadsheet\{
    Spreadsheet, Xlsx, Alignment
};


class ClassesAndStudents extends Base
{
    protected $dbCourse;

    public function _initialize()
    {
        $this->dbCourse = new dbCourse();
    }

    //我的班级
    public function myClasses()
    {
        $classIds = $this->info['classIds'];

        $classes = null;
        if (!empty($classIds)) {
            //获取班级信息
            $where = [];
            $where['id'] = ['in', $classIds];
            $classes = ClassInfo::where($where)->paginate(10);
        }
        $title = ['name' => '我的班级', 'en' => 'My Classes'];
        $this->assign('title', $title);
        $this->assign('classes', $classes);
        return $this->fetch();
    }

    //我的学生
    public function myStudents()
    {
        $classIds = $this->info['classIds'];

        $params = Request::instance()->param();
        $classID = intval($params['class_id'] ?? 0);
        $studentName = trim($params['student_name'] ?? '');

        //获取班级
        $where = [];
        $where['id'] = ['in', $this->info['classIds']];
        $classList = ClassInfo::where($where)->order('grade desc')->limit(100)->select();

        $students = null;
        $classes = [];
        if (!empty($classIds)) {
            //根据班级获取学生信息
            $where = [
                'status' => 1,
            ];
            if ($classID > 0) {
                $where['classID'] = ['=', $classID];
            } else {
                $where['classID'] = ['in', $classIds];
            }

            if (!empty($studentName)) {
                $where['name'] = ['like', '%' . $studentName . '%'];
            }

            $students = Student::where($where)->paginate(10, false, ['query' => request()->param()]);
            if (!empty($students->items())) {
                $classIds = array_unique(array_column($students->items(), 'classID'));
                $classes = ClassInfo::getListByIds($classIds);
            }
        }

        $title = ['name' => '我的学生', 'en' => 'My Students'];
        $this->assign('title', $title);
        $this->assign('classes', $classes);
        $this->assign('classList', $classList);
        $this->assign('students', $students);
        $this->assign('search', [
            'class_id' => $classID,
            'student_name' => $studentName,
        ]);
        return $this->fetch();
    }

    //编辑学生
    public function editStudent()
    {
        $params = Request::instance()->param();

        //获取班级
        $where = [];
        $classList = ClassInfo::where($where)->order('grade desc')->limit(100)->select();

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
                $jumpUrl = Url::build('teach/ClassesAndStudents/editStudent', ['id' => $ID, 'role' => 0]);
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

    //教师修改学生密码
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
                if ($roleID != 0) throw new \Exception('Role参数错误', 20000);
                if (empty($password)) throw new \Exception('新密码不能为空，请输入新密码。', 20000);
                if (empty($repassword)) throw new \Exception('请再次输入的新密码。', 20000);
                if ($password != $repassword) throw new \Exception('两次密码不一致，请重新输入。', 20000);

                //修改密码
                $updateData = [
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ];

                $userInfo = Student::getInfo($ID);
                if (empty($userInfo)) throw new \Exception('学生信息不存在', 20000);
                Student::updateInfo($ID, $updateData);
                $jumpUrl = 'ClassesAndStudents/myStudents';

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
        //获取班级,限制在当前老师所在的班级
        $where = [];
        $where['id'] = ['in', $this->info['classIds']];
        $classList = ClassInfo::where($where)->order('grade desc')->limit(100)->select();

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
                $jumpUrl = 'ClassesAndStudents/myStudents';
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

    //选课学生
    public function electiveStudents()
    {
        $params = Request::instance()->param();
        $classID = intval($params['class_id'] ?? 0);
        $courseID = intval($params['course_id'] ?? 0);
        $studentName = trim($params['student_name'] ?? '');

        //获取班级,限制在当前老师所在的班级
        $where = [];
        $where['id'] = ['in', $this->info['classIds']];
        $classList = ClassInfo::where($where)->order('grade desc')->limit(100)->select();


        $where = $courseIds = $studentIds = $studentsInfo = $classesInfo = $coursesInfo = [];
        //获取当前老师的课程
        $courseList = Course::getListByTeacherID($this->info['id'], [1]);
        if (!empty($courseList)) {
            $courseIds = array_unique(array_column($courseList, 'id'));
        }

        //获取老师当前班级的学生
        $classIds = $classID > 0 ? [$classID] : $this->info['classIds'];//指定班级搜索
        $studentList = Student::getListByClassIds($classIds, [1]);
        if (!empty($studentList)) {
            $studentIds = array_unique(array_column($studentList, 'id'));
        }

        //按学生姓名搜索
        if (!empty($studentName)) {
            $studentList = Student::getListByStudentName($studentName);
            if (!empty($studentList)) {
                $studentIds = array_intersect($studentIds, array_unique(array_column($studentList, 'id')));
            } else {
                $studentIds = [];
            }
        }

        if ($courseID > 0) {
            $where['course_id'] = ['=', $courseID];//指定搜索课程
        } else {
            $where['course_id'] = ['in', $courseIds];
        }

        $where['student_id'] = ['in', $studentIds];
        $list = StudentOption::where($where)->order('id desc')->paginate(10);
        unset($studentList, $courseIds, $studentIds);

        $coursesInfo = Course::getListByTeacherID($this->info['id']);
        if (!empty($coursesInfo)) {
            $tmp = [];
            foreach ($coursesInfo as $course) {
                $tmp[$course['id']] = [
                    'name' => $course['name'],
                    'year' => $course['year'],
                    'term' => $course['term'],
                ];
            }
            //$coursesInfo = array_column($coursesInfo, 'name', 'id');
            $coursesInfo = $tmp;
            unset($tmp);
        }

        if (!empty($list->items())) {
            $studentIds = array_unique(array_column($list->items(), 'student_id'));

            if (!empty($studentIds)) {
                $students = Student::getListByStudentIds($studentIds);
                if (!empty($students)) {
                    $classIds = array_unique(array_column($students, 'classID'));
                    if (!empty($classIds)) {
                        $classesInfo = ClassInfo::getListByIds($classIds);
                        if (!empty($classesInfo)) $classesInfo = array_column($classesInfo, 'className', 'id');
                    }
                    foreach ($students as $student) {
                        $studentsInfo[$student['id']]['name'] = $student['name'];
                        $studentsInfo[$student['id']]['phone'] = $student['phone'];
                        $studentsInfo[$student['id']]['className'] = $classesInfo[$student['classID']] ?? '-';
                    }
                    unset($students, $classesInfo);
                }
            }
        }

        $title = ['name' => '选课记录', 'en' => 'Students Elective'];
        $this->assign('list', $list);
        $this->assign('students', $studentsInfo);
        $this->assign('courses', $coursesInfo);
        $this->assign('courseList', $courseList);
        $this->assign('classList', $classList);
        $this->assign('title', $title);
        $this->assign('search', [
            'class_id' => $classID,
            'course_id' => $courseID,
            'student_name' => $studentName,
        ]);
        return $this->fetch();
    }

    //选课
    public function electiveCourse()
    {
        $params = Request::instance()->param();
        $studentID = intval($params['student_id']);

        if (Request::instance()->isPost()) {
            try {
                $courseID = intval($params['course_id']);
                if ($studentID < 1) throw new \Exception('学生ID参数错误', 20000);
                if ($courseID < 1) throw new \Exception('课程ID参数错误，请选择课程', 20000);

                //校验学生信息
                $studentInfo = Student::getInfo($studentID);
                if (empty($studentInfo)) throw new \Exception('学生信息不存在', 20000);

                //校验课程信息
                $courseInfo = Course::getInfo($courseID);
                if (empty($courseInfo)) throw new \Exception('课程信息不存在', 20000);

                //校验是否已经存在该选课记录
                $studentOptions = StudentOption::getStudentOption($studentID);
                $courseIds = [];
                if (!empty($studentOptions)) {
                    $courseIds = array_unique(array_column($studentOptions, 'course_id'));
                }

                //学生没有选此门课程，可选课
                if (!in_array($courseID, $courseIds)) {
                    $result = (new \app\student\model\Course())->optionCourse($studentID, [$courseID]);
                    if ($result['error'] != 0) throw new \Exception($result['msg'], 20000);
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
            $this->success('选课成功', 'ClassesAndStudents/electiveStudents');
        } else {
            if ($studentID < 1) $this->error('学生ID参数错误');

            //获取学生信息
            $studentInfo = Student::getInfo($studentID);
            if (empty($studentInfo)) $this->error('学生信息不存在');

            //获取学生班级信息
            $classID = intval($studentInfo['classID']);
            $classInfo = ClassInfo::getInfo($classID);

            //获取当前老师的课程
            $courseList = Course::getListByTeacherID($this->info['id'], [1]);
            if (empty($courseList)) $this->error('您没有课程数据，请先添加课程');

            $title = ['name' => '选课', 'en' => 'Elective Course'];
            $this->assign('title', $title);
            $this->assign('student', $studentInfo);
            $this->assign('classInfo', $classInfo);
            $this->assign('courses', $courseList);
            return $this->fetch();
        }
    }
}