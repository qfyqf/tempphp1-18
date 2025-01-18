<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/29
 * Time: 11:52
 */

namespace app\student\model;

use think\Model;
use think\Session;

class MyModel extends Model
{
    protected $studentId;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->studentId = Session::get('id');
    }

    /**
     * 共用处理函数
     * @param $info
     * @return array
     */
    public function result($info)
    {
        if (isset($info) && !empty($info)) {
            $result = $this->message('获取数据成功!',0, $info);
        } else {
            $result = $this->message('获取数据失败!', 1);
        }

        return $result;
    }

    /**
     * 返回格式处理
     * @param string $msg 数据提示信息
     * @param int $error  错误代码
     * @param array $data  数据
     * @return array
     */
    public function message($msg = '', $error = 0, $data = [])
    {
        $message = [
            'msg' => $msg,
            'error' => $error,
            'data' => $data
        ];

        return $message;
    }
}
