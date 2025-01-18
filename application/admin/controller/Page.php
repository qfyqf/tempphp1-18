<?php
/**
 * User: hejun
 * Date: 2019/7/29
 * Time: 16:41
*/
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;



class Page extends Base
{

    public function index()
    {
        $title = ['name' => '页面管理', 'en'=>'Page'];

        $list = DB::name('page')->order('id asc')->paginate(15);

        $this->assign('list',$list);
        $this->assign('title',$title);
        return $this->fetch();
    }


    public function upd()
    {
        $title = ['name' => '修改页面', 'en' => 'Update Page'];
        $id = intval( Request::instance()->param('id') );
        if ($id<1)
            $this->error('OPTION ERROR');

        $item = $item = DB::name('page')->where('id',$id)->find();

        if (Request::instance()->isPost()){
            $params = Request::instance()->param();
            if (!$params['title'] || !$params['content'] )   
                $this->error('请将内容填完整');

            $add = [
                'title'  => $params['title'],
                'content'  => $params['content']
            ];

            if (DB::name('page')->where('id',$id)->update($add))
                $this->success('修改成功','page/index');
            else              
                $this->error('修改失败');
        }

        // 副导航标志
        $this->assign('item',$item);
        $this->assign('title',$title);
        return $this->fetch();
    }



}