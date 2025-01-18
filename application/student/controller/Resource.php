<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/23
 * Time: 15:20
 */

namespace app\student\controller;

use app\student\model\Resource as newResource;
use think\Request;

class Resource extends MyController
{
    protected $resource;

    public function __construct()
    {
        parent::__construct();
        $this->resource = new newResource();

    }

    /**
     * 资源列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $info = $this->resource->getResource()['data'];
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();
        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('resource', $info);
        return $this->fetch('index');
    }


    public function index2()
    {
        $info = $this->resource->getResource2()['data'];
        $pages = $info->render();
        $current = $info->currentPage();
        $total = $info->total();
        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('page', $pages);
        $time = date("Y-m-d H:i:s");
        $this->assign('time', $time);
        $this->assign('resource', $info);
        return $this->fetch('index');
    }

    /**
     * 获取资源信息
     * @param $id int 资源ID
     * @param $back int 返回页面判定
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getResourceInfo($id, $back)
    {
        $info = $this->resource->getResourceInfo($id);
        $status = $this->resource->getStatus($id);
        $this->assign('status', $status);
        $this->assign('back', $back);
        $this->assign('resource', $info['data']);
        return $this->fetch('info');
    }


    /**
     * 获取收藏信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCollectInfo()
    {
        $info = $this->resource->getCollectInfo();
        $this->assign('resource', $info['data']);
        return $this->fetch('resource');
    }

    /**
     * 收藏
     * @param $id
     * @param $status
     */
    public function collect($id,$status)
    {
        //判断是否已收藏，避免重复收藏
        $collect = $this->resource->getStatus($id);

        if($collect) {
            if($status) {
                $this->error('已收藏');
            } else {
                $result = $this->resource->cancel($id);
                if ($result) {
                    $this->success('取消收藏成功！');
                } else {
                    $this->error('出错');
                }
            }

        } else {
            $result = $this->resource->collect($id);
            if ($result) {
                $this->success('收藏成功！',url('resource/getResourceInfo',['id' => $id, 'back' => 0]));
            } else {
                $this->error('出错');
            }
        }

    }

    /**
     * 下载
     */
    public function download($id)
    {
        //下载文件名
        $info = $this->resource->getInfo($id);
        $file_name = $info['name'];
        $ext = pathinfo($info['resource'],PATHINFO_EXTENSION);

        //下载文件存放目录
        $file_dir = ROOT_PATH . 'public' . DS . 'uploads' . DS . str_replace('/',DS, $info['resource']);
        //检查文件是否存在
        if (!file_exists($file_dir)) {
            $this->error('资源找不到');
        } else {
            //打开文件
            $file = fopen($file_dir, "r");
            //输入文件标签
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: " . filesize($file_dir));
            Header("Content-Disposition: attachment; filename=" . $file_name . "." .$ext);
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir));
            fclose($file);
            exit();
        }
    }

    /**
     * 资源搜索
     * @return mixed
     */
    public function search()
    {
        $param = Request::instance()->param();
        $page = $this->resource->getSearchInfo($param['resource'])['data'];
        $pages = $page->render();
        $current = $page->currentPage();
        $total = $page->total();
        $list = $page->listRows();

        $this->assign('current', $current);
        $this->assign('total', $total);
        $this->assign('list', $list);
        $this->assign('info', $page);
        $this->assign('page', $pages);

        return $this->fetch('resource/index');
    }
}

