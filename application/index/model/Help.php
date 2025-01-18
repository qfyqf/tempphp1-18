<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/4/18
 * Time: 10:35
 */

namespace app\index\model;


use think\Controller;
use think\Db;
use think\Request;

class Help extends Controller
{
    protected $name = 'help';
    protected $role;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * 获取各级标题
     * @param $type int 类型，0，学生；1，老师
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTitle($type)
    {
        $result = Db::name($this->name)
            ->where('type', $type)
            ->order('orderid')
            ->field('id,pid,title,orderid')
            ->select();
        $subTitle = [];
        foreach ($result as $value) {
            if ($value['pid'] == 0) {
                $title[$value['orderid']] = $value;
            } else {
                $subTitle[$value['pid']][$value['orderid']] = $value;
            }
        }

        ksort($title);
        $title = array_values($title);

        if (isset($subTitle)) {
            foreach ($subTitle as &$value) {
                ksort($value);
                $value = array_values($value);
            }
        }


        $info = ['title' => $title, 'subTitle' => $subTitle];

        return $info;
    }

    /**
     * 获取内容
     * @param $id int 本页ID
     * @param $type int 类型，0，学生；1，老师
     * @return mixed
     */
    public function getContent($id,$type)
    {
        $newPosition = $this->getPosition($type);
        //得到当前页的位置
        foreach ($newPosition as $key => $value) {
            if ($id == $value['id']) {
                $order = $key;
            }
        }
        $newOrder = $newPosition[$order];
        return $this->setValue($newOrder, $newPosition);
    }

    /**
     * 上一页/下一页跳转
     * @param $id int 本页ID
     * @param $type int 类型，0，学生；1，老师
     * @param $operate int 操作，1，上一页；2，下一页
     * @return array|int
     */
    public function skip($id, $type, $operate)
    {
       $newPosition = $this->getPosition($type);
        //得到当前页的位置
        foreach ($newPosition as $key => $value) {
            if ($id == $value['id']) {
                $order = $key;
            }
        }

        if(!isset($order)) {
            return -2;
        }

        if ($operate == 1) {
            //上一页
            $order -= 1;
        } else {
            //下一页
            $order += 1;
        }

        //判断是否为第一页或最后一页
        if (isset($newPosition[$order])) {
            $newOrder = $newPosition[$order];
            $all = Db::name($this->name)
                ->where('type', $type)
                ->distinct('pid')
                ->column('pid');

            //判断是否为存在子标题的主标题
            while (in_array($newOrder['id'], $all)) {
                if ($operate == 1) {
                    $order -= 1;
                } else {
                    $order += 1;
                }

                if (isset($newPosition[$order])) {
                    $newOrder = $newPosition[$order];
                } else {
                    return -2;
                }
            }
            return $this->setValue($newOrder, $newPosition);
        } else {
            if ($operate == 1) {
                return 0;
            } else {
                return -1;
            }
        }
    }

    /**
     * 获取所有标题位置
     * @param $type int 类型，0，学生；1，老师
     * @return array
     */
    public function getPosition($type)
    {
        $sql = <<<SQL
SELECT a.pid,a.id,a.orderid,a.title,a.content,count(*) as sort 
FROM vr_help a 
JOIN vr_help b 
ON a.pid = b.pid and a.orderid >= b.orderid and a.type = b.type
WHERE a.type = :TYPE 
GROUP BY a.id,a.pid
ORDER BY orderid
SQL;
        $position = Db::query($sql, ['TYPE' => $type]);

        $newPosition = [];
        //得到所有内容的排序。
        foreach ($position as $item => $order) {
            if ($order['pid'] == 0) {
                array_push($newPosition, $order);
                foreach ($position as $key => $value) {
                    if ($value['pid'] == $order['id']) {
                        array_push($newPosition, $value);
                    }
                }
            }
        }

        return $newPosition;
    }

    /**
     * 设置返回数据
     * @param $newOrder array 当前位置信息
     * @param $newPosition array 所有位置信息
     * @return array
     */
    public function setValue($newOrder, $newPosition)
    {
        if ($newOrder['pid'] > 0) {
            foreach ($newPosition as $key => $value) {
                if ($newOrder['pid'] == $value['id']) {
                    $pnumber = $value['sort'] - 1;
                    $ptitle = $value['title'];
                    $number = $newOrder['sort'] - 1;
                }
            }
        } else {
            $number = 0;
            $pnumber = $newOrder['sort'] - 1;
            $ptitle = $newOrder['title'];
        }
        $title = $newOrder['title'];
        $content = $newOrder['content'];
        $result = [
            'number' => $number,
            'content' => $content,
            'pnumber' => $pnumber,
            'ptitle' => $ptitle,
            'title' => $title,
            'id' => $newOrder['id']
        ];

        return $result;
    }

}
