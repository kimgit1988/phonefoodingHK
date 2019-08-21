<?php
namespace app\common\model;
use think\Model;
use \think\Response;

class Article extends Model
{

    protected $type = [
        'id'          => 'integer',
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];

    public function category()
    {
        return $this->belongsTo('articlecat', 'category_id', 'id');
    }

    /**
     * [aricleAdd description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function articleAdd(array $params)
    {
        return $this->save([
            'category_id' => $params['category_id'],
            'title'       => $params['title'],
            'thumbnail'   => $params['thumbnail'],
            'description' => $params['description'],
            'attr' => $params['attr'],
            'keyword'     => $params['keyword'],
            'content'     => isset($params['editorValue']) ? $params['editorValue'] :'',
            'create_time'     => time(), 
        ]);
    }

/**
     * [aricleAdd description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016
     * @param  array  $params [description]
     * @return [type] [description]
     */
    public function articleEdit(array $params,$id)
    {
        return $this->where('id',$id)->update([
            'category_id' => $params['category_id'],
            'title'       => $params['title'],
            'description' => $params['description'],
            'attr' => $params['attr'],
            'keyword'     => $params['keyword'],
            'content'     => isset($params['editorValue']) ? $params['editorValue'] :'',
            'update_time'     => time(), 
        ]);
    }

    /**
     * [deleteAricle description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-22
     * @param  [type] $id [description]
     * @return [type] [description]
     */
    public function deleteArticle($id)
    {
        $articleRow = self::get($id);
        $filename=".".$articleRow->thumbnail;
        //删除这篇文章的时候，先删除它的缩略图
        if (is_file($filename)&&file_exists($filename)) {
            unlink($filename);
        }
      return $articleRow->delete();  
    }
   
   
   /**
     * [搜索本栏目 description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10-22
     * @param  array  $params [description]
     * @return [type] [description]
     */

      public static function searchArticle()
    {
       return   self::field('id,description,title,thumbnail,create_time');
     
    }

}
