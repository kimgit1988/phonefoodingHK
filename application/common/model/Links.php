<?php
namespace app\common\model;
use think\Db;
use think\Model;

class Links extends Model
{
    // protected $auto = ['status'];

    protected $type = [
        'id'          => 'integer',
        'status'      => 'integer',
        'sort'        => 'integer',
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    /**
     * 添加友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function linksAdd(array $params)
    {
        return $this->save([
            'title'  => $params['title'],
            'url'    => $params['url'],
            'logo'   => $params['logo'],
            'linker' => $params['linker'],
            'status' => isset($params['status']) ? $params['status'] : 0,
            'sort'   => $params['sort'],
            'create_time' =>time(),
        ]);
    }

    /**
     * 修改友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function linksEdit(array $params)
    {
        // $logo = Db::name('links')->where(['id' => $params['id']])->value('logo');

        // if ($logo != $params['logo']) {
        //     Strings::deleteFile($logo);
        // }

        return $this->update([
            'title'  => $params['title'],
            'url'    => $params['url'],
            // 'logo'   => $params['logo'],
            'linker' => $params['linker'],
            'status' => $params['status'],
            'sort'   => $params['sort'],
            'update_time'   =>time(),
        ], ['id' => $params['id']]);
    }

    /**
     * 删除友情链接
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function deleteLinks($id)
    {
         $links= self::get($id);
        $logo=".".$links->logo;
        if (is_file( $logo)&&file_exists( $logo)) {
            unlink($logo);
        }
      return $links->delete();  
    }

    /**
     * 获取状态
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    strings                   $value [description]
     * @return   [type]                          [description]
     */
    // protected function getStatusAttr($value)
    // {
    //     $status = [0 => '禁用', 1 => '启用'];
    //     return $status[$value];
    // }

    /**
     * 获取排序
     * @param  [type] $sort [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    // protected function getSortAttr($sort, $data)
    // {
    //     return '<input type="text" value="' . $sort . '" class="sort"/>';
    // }

}
