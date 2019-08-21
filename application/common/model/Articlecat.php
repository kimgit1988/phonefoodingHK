<?php
namespace app\common\model;
use \think\Model;
class Articlecat extends Model
{
    /**
     * [关联模型 一对多 description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @return [type]                   [description]
     */
    public function parent()
    {
        return $this->hasMany('articlecat', 'parent_id', 'id');
    }

    /**
     * [ariclecatAdd description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function articlecatAdd(array $params)
    {
        return $this->save([
            'parent_id'   => $params['parent_id'],
            'title'       => $params['title'],
            'description' => $params['description'],
            'keyword'     => $params['keyword'],
            'sort'        => $params['sort'],
            'create_time' => time(),
        ]);
    }

    /**
     * [ariclecateEdit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @param    array                    $params [description]
     * @return   [type]                           [description]
     */
    public function ArticlecatEdit(array $params)
    {
        return $this->update([
            'parent_id'   => $params['parent_id'],
            'title'       => $params['title'],
            'description' => $params['description'],
            'keyword'     => $params['keyword'],
            'sort'        => $params['sort'],
             'update_time' => time(),
        ], ['id' => $params['id']]);
    }

    /**
     * [deleteAriclecategory description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-26
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function deleteArticlecat($id)
    {
        $articlecatRow = self::get($id);
        if ($articlecatRow == false) {
            $this->error = "分类不存在";
            return false;
        }
        //调用的上面的关联模型parent
        if ($articlecatRow->parent()->count() > 0) {
            $this->error = "本分类下还有其他分类,不能删除";
            return false;
        }
        return $articlecatRow->delete();
    }

    /**
     * 列表页的字段
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-23
     * @return   [type]                   [description]
     */
    public static function listField()
    {
        return self::field('id,parent_id,title,sort,update_time');
    }

    /**
     * 列表页的字段
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-23
     * @return   [type]    [description]
     */
    public static function selectField()
    {
        return self::field('id,title,parent_id');
    }



  




}
