<?php
namespace app\common\model;
use \think\Db;
use \think\Model;
class Focus extends Model
{
    protected $auto = ['status'];
    protected $type = [
        'id'          => 'integer',
        'status'      => 'integer',
        'sort'        => 'integer',
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    /**
     * [position description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return   [type]                   [description]
     */
    public function position()
    {
        return $this->belongsTo('focus_position', 'position_id', 'id');
    }

    /**
     * 添加焦点图
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function focusAdd(array $params)
    {
        return $this->save([
            'position_id' => $params['position_id'],
            'title'       => $params['title'],
            'url'         => $params['url'],
            'image'       => $params['image'],
            'remark'      => $params['remark'],
            'status'      => $params['status'],
            'sort'        => $params['sort'],
            'create_time' => time(),
        ]);
    }

    /**
     * 编辑焦点图
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function focusEdit(array $params)
    {
        // $image = Db::name('focus')->where(['id' => $params['id']])->value('image');

        // if ($image != $params['focus_image']) {
        //     Strings::deleteFile($image);
        // }

        return $this->update([
            'position_id' => $params['position_id'],
            'title'       => $params['title'],
            'url'         => $params['url'],
            // 'image'       => $params['focus_image'],
            'remark'      => $params['remark'],
            'status'      => $params['status'],
            'sort'        => $params['sort'],
            'update_time' => time(),
        ], ['id' => $params['id']]);
    }

    /**
     * 删除
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    [type]                   $id [description]
     * @return   [type]                       [description]
     */
    public function deleteFocus($id)
    {
         $focus= self::get($id);
        $image=".".$focus->image;
        if (is_file($image)&&file_exists($image)) {
            unlink($image);
        }
      return $focus->delete();  
    }
    /**
     * 获取状态
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    strings                   $value [description]
     * @return   [type]                          [description]
     */
    protected function getStatusAttr($value)
    {
        $status = [0 => '禁用', 1 => '啟用'];
        return $status[$value];
    }

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
