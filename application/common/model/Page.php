<?php
namespace app\common\model;
use\think\Model;

class Page extends Model {
    protected $auto = ['description'];
    protected $type = [
        'sort' => 'integer',
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    /**
     * [pageAdd description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function pageAdd(array $params) {
        return $this->save([
            'parent_id' => $params['parent_id'],
            'title' => $params['title'],
            'description' => $params['description'],
            'keyword' => $params['keyword'],
            'content' => $params['content'],
            'sort' => $params['sort'],
        ]);
    }

    /**
     * [pageEdit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function pageEdit(array $params) {
        return $this->save([
            'parent_id' => $params['parent_id'],
            'title' => $params['title'],
            'description' => $params['description'],
            'keyword' => $params['keyword'],
            'content' => $params['content'],
            'sort' => $params['sort'],
        ], ['id' => $params['id']]);
    }

    /**
     * [deletePage description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    [type]                   $id [description]
     * @return   [type]                       [description]
     */
    public function deletePage($id) {
        $pageRow = self::get($id);
        if ($pageRow == false) {
            $this->error = "分類不存在";
            return false;
        }

        if ($pageRow->parent()->count() > 0) {
            $this->error = "本分類下還有其他分類,不能刪除";
            return false;
        }

        return $pageRow->delete();
    }
    /**
     * 设置简介
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param    [type]                   $description [description]
     * @param    [type]                   $data        [description]
     */
    //thinkphp5里面有个获取器！，修改器。自己可以看看@这里是个修改器setDescriptionAttr 针对  description字段
//  protected function setDescriptionAttr($description, $data) {
//      if ($description) {
//          return $description;
//      }
//      return isset($data['content']) ? mb_substr(strip_tags($data['content']), 0, 100, 'utf-8') : '';
//  }

    /**
     * [user description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return   [type]                   [description]
     */
    public function parent() {
        return $this->hasMany('page', 'parent_id', 'id');
    }

    /**
     * 列表页的字段
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return   [type]                   [description]
     */
    public static function listField() {
        return self::field('id,parent_id,title,description,keyword,sort,update_time');
    }

    /**
     * 列表页的字段
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return   [type]                   [description]
     */
    public static function selectField() {
        return self::field('id,title,parent_id');
    }

}