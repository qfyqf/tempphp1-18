<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/29
 * Time: 12:02
 */

namespace app\student\controller;


use think\Controller;
use think\Session;

class MyController extends Controller
{

    protected $studentId;
    protected $role;

    /**
     * 初始化相关资源
     * Index constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $student = Session::get();
        if (isset($student['id'])) {
            $this->studentId = $student['id'];
        }

        if (isset($student['role'])) {
            $this->role = $student['role'];
        }
        if (!(isset($this->role) && $this->role == 0 && isset($this->studentId))) {
            $this->redirect('/');
        }
    }
}

