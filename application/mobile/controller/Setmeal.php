<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
use think\View;//视图
use think\Controller;//控制器
use think\Validate;
use think\Redirect;//重定向
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库

class Setmeal extends Base {
    public function index() {
        $contact_number = session('mob_user.contact_number');
        $meal = DB::name('SetMeal')->where('isDelete',0)->select();
        return $this->fetch();
    }
    public function add(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            $save = array(
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'remark'=>$post['remark'],
                'detail'=>$post['detail'],
                'status'=>!empty($post['status'])?$post['status']:'',
                'totlePrice'=>$post['price'],
                'imgUrl'=>$post['pic_path'],
                'thumbnailUrl'=>$post['thumb_path'],
                'categoryId'=>$post['categoryId'],
                'categoryName'=>$post['categoryName'],
                // 'expiryTimeType'=>!empty($post['validityTime'])?$post['validityTime']:'',
                'contactNumber'=>$contact_number,
            );
            //          if($save['expiryTimeType']==1){
            //          	$save['startDate'] = strtotime($post['startdate']);
            //          	$save['endDate'] = strtotime($post['enddate']);
            //          	$save['startTime'] = '';
            //          	$save['endTime'] = '';
            // }else if($save['expiryTimeType']==2){
            //          	$save['startTime'] = $post['starttime'];
            //          	$save['endTime'] = $post['endtime'];
            //          	$save['startDate'] = '';
            //          	$save['endDate'] = '';
            // }else{
            //          	$save['startDate'] = '';
            //          	$save['endDate'] = '';
            //          	$save['startTime'] = '';
            //          	$save['endTime'] = '';
            // }
            if(!empty($save['imgUrl'])){
                $isbase['imgUrl'] = is_base64_picture($save['imgUrl']);
            }else{
                $this->error('請上傳圖片');die;
            }
            if($isbase['imgUrl']){
                // base64转图片
                $img = save_base_img($save['imgUrl'],'uploads/foods');
                // 图片地址保存
                $save['imgUrl'] = $img['path'];
                // 生成缩略图 这里不需要
                $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
                $save['thumbnailUrl'] = $thumb['msg'];
            }
            if (loader::validate('Setmeal')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Setmeal')->getError());
            }
            $res = DB::name('SetMeal')->insert($save);
            if($res!==false){
                return $this->success('添加成功', Url::build('foods/index'));
            }else{
                return $this->success('添加失敗');
            }
        }else{
            $contact_number = session('mob_user.contact_number');
            $category = DB::name('category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact_number)->where('status',1)->where('isDelete',0)->select();
            $this->assign('category',$category);
            return $this->fetch();
        }

    }
    public function edit(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            $save = array(
                'id'=>$post['id'],
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'remark'=>$post['remark'],
                'detail'=>$post['detail'],
                'status'=>$post['status'],
                'totlePrice'=>$post['price'],
                'imgUrl'=>$post['pic_path'],
                'thumbnailUrl'=>$post['thumb_path'],
                'categoryId'=>$post['categoryId'],
                'categoryName'=>$post['categoryName'],
                // 'expiryTimeType'=>!empty($post['validityTime'])?$post['validityTime']:'',
            );
            //          if($save['expiryTimeType']==1){
            //          	$save['startDate'] = strtotime($post['startdate']);
            //              $save['endDate'] = strtotime($post['enddate']);
            //          	$save['startTime'] = '';
            //          	$save['endTime'] = '';
            // }else if($save['expiryTimeType']==2){
            //          	$save['startTime'] = $post['starttime'];
            //          	$save['endTime'] = $post['endtime'];
            //          	$save['startDate'] = '';
            //          	$save['endDate'] = '';
            // }else{
            //          	$save['startDate'] = '';
            //          	$save['endDate'] = '';
            //          	$save['startTime'] = '';
            //          	$save['endTime'] = '';
            // }
            if(!empty($save['imgUrl'])){
                $isbase['imgUrl'] = is_base64_picture($save['imgUrl']);
            }else{
                $this->error('請上傳圖片');die;
            }
            if($isbase['imgUrl']){
                // base64转图片
                $img = save_base_img($save['imgUrl'],'uploads/foods');
                // 图片地址保存
                $save['imgUrl'] = $img['path'];
                // 生成缩略图 这里不需要
                $thumb = img_create_small($img['root'],80,80,"uploads/Thumb");
                $save['thumbnailUrl'] = $thumb['msg'];
            }
            if (loader::validate('Setmeal')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Setmeal')->getError());
            }
            $res = DB::name('SetMeal')->where('id',$post['id'])->where('contactNumber',$contact_number)->update($save);
            if($res!==false){
                return $this->success('編輯成功', Url::build('foods/index'));
            }else{
                return $this->success('編輯失敗');
            }
    	}else{
    		$id = input('id');
            $action = input('action');
            $action = !empty($action)?$action:1;
    		$contact_number = session('mob_user.contact_number');
    		$meal = DB::name('SetMeal')
	    		->where('id',$id)
	    		->where('contactNumber',$contact_number)
	    		->where('isDelete',0)
	    		->find();
	    	$mealCategoryall = DB::name('SetMealCategory')
                ->alias('c')
                ->field('c.*,count(i.cid) as foodNumber')
                ->join('mos_set_meal_info i','c.id = i.cid AND i.isDelete = 0','left')
	    		->where('c.mid',$id)
	    		->where('c.isDelete',0)
                ->group('c.id')
                ->order('c.id asc')
	    		->select();
            $mealCategory = DB::name('SetMealCategory')
                ->alias('c')
                ->field('c.*,count(i.cid) as foodNumber')
                ->join('mos_set_meal_info i','c.id = i.cid AND i.isDelete = 0','left')
                ->join('mos_goods g','g.id = i.gid','left')
                ->where('c.mid',$id)
                ->where('c.isDelete',0)
                ->where('g.isDelete',0)
                ->where('g.disable',1)
                ->group('c.id')
                ->order('c.id asc')
                ->select();
            $mealc = [];
            foreach ($mealCategory as $item) {
                $mealc[$item['id']] = $item;
            }
            foreach($mealCategoryall as &$v)
            {
                if(in_array($v['id'],array_column($mealc,'id'))) {
                    $v['foodNumber'] = $mealc[$v['id']]['foodNumber'];
                }else{
                    $v['foodNumber'] = 0;
                }
            }
	        $category = DB::name('category')
		        ->field('id,name')
		        ->where('typeNumber','trade')
		        ->where('contactNumber',$contact_number)
		        ->where('status',1)
		        ->where('isDelete',0)
		        ->order('id desc')
		        ->select();
            $this->assign('action',$action);
	        $this->assign('meal',$meal);
	        $this->assign('mealCategory',$mealCategoryall);
	        $this->assign('category',$category);
	    	return $this->fetch();
    	}
    }

    public function del(){
    	if( Request::instance()->isPost() ) {
    		$id = input('id');
    		$contact_number = session('mob_user.contact_number');
    		$res = DB::name('SetMeal')->where('id',$id)->where('contactNumber',$contact_number)->update(['utime'=>time(),'isDelete'=>1]);
            if($res!==false){
                return $this->success('刪除成功', Url::build('foods/index'));
            }else{
                return $this->success('刪除失敗');
            }
    	}
    }

    public function addcategory(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            $contact_number = session('mob_user.contact_number');
            $meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$params['mid'])->where('isDelete',0)->find();
            if($meal){
                $category = array(
                    'mid'				=> $params['mid'],
                    'name'				=> $params['name'],
                    'categoryMaxNumber'	=> $params['categoryNumber'],
                    'goodsMaxNumber'	=> !empty($params['goodsMax'])?$params['goodsMax']:1,
                    'sort'				=> !empty($params['sort'])?$params['sort']:1,
                );
                if (loader::validate('Setmealcategory')->scene('add')->check($category) === false) {
                    return $this->error(loader::validate('Setmealcategory')->getError());
                }
                // if(!empty($params['food'])){
                // 	$info = array();
                // 	Db::startTrans();
                //        $code = 1;
                //        $res = DB::name('setMealCategory')->insertGetId($category);
                //        foreach ($params['food'] as $key => $val) {
                //        	$info[] = array('mid'=>$params['mid'],'cid'=>$id,'gid'=>$key,'ctime'=>time());
                //        }
                //        $res = Db::name('setMealInfo')->insertAll($info);
                //        if($res!==false){
                //        	Db::commit();
                // 		$this->success('添加成功');
                //        }else{
                //        	DB::rollback();
                // 		$this->error('添加失败');
                //        }
                //    }else{
                $id = DB::name('setMealCategory')->insertGetId($category);
                if($id!==false){
                    $this->success('添加成功');
                }else{
                    $this->error('添加失敗');
                }
                // }
            }else{
                $this->error('添加失敗');
            }
        }else{
            $contact_number = session('mob_user.contact_number');
            $mid = input('mid');
            $category = DB::name('category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact_number)->where('status',1)->where('isDelete',0)->select();
            $this->assign('mid',$mid);
            $this->assign('category',$category);
            return $this->fetch();
        }
    }

    public function editcategory(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            $contact_number = session('mob_user.contact_number');
            $meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$params['mid'])->where('isDelete',0)->find();
            if($meal){
                $category = array(
                    'id'				=> $params['cid'],
                    'mid'				=> $params['mid'],
                    'name'				=> $params['name'],
                    'categoryMaxNumber'	=> $params['categoryNumber'],
                    'goodsMaxNumber'	=> !empty($params['goodsMax'])?$params['goodsMax']:1,
                    'sort'				=> !empty($params['sort'])?$params['sort']:1,
                );
                if (loader::validate('Setmealcategory')->scene('edit')->check($category) === false) {
                    return $this->error(loader::validate('Setmealcategory')->getError());
                }
                $res = DB::name('setMealCategory')->where('id',$category['id'])->update($category);
                if($res!==false){
                    $this->success('編輯成功');
                }else{
                    $this->error('編輯失敗');
                }
            }else{
                $this->error('編輯失敗');
            }
        }else{
            $list = array();
            $return = array();
            $mid = input('mid');
            $cid = input('cid');
            $contact_number = session('mob_user.contact_number');
            $meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$mid)->where('isDelete',0)->find();
            if($meal){
                $mealCategory = DB::name('SetMealCategory')->where('id',$cid)->where('isDelete',0)->find();
                $this->assign('mid',$mid);
                $this->assign('cid',$cid);
                $this->assign('mealCategory',$mealCategory);
                return $this->fetch();
            }
        }
    }

    public function food(){
    	$list = array();
    	$return = array();
    	$mid = input('mid');
    	$cid = input('cid');
    	$contact_number = session('mob_user.contact_number');
    	$meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$mid)->where('isDelete',0)->find();
    	if($meal){
		    $categoryFood = DB::name('SetMealInfo')
                  ->field('i.*,g.name,g.thumbnailUrl')
                  ->alias('i')
                  ->join('mos_goods g','i.gid = g.id AND g.isDelete = 0','left')
                  ->where('i.mid',$mid)
                  ->where('i.cid',$cid)
                  ->where('i.isDelete',0)
                  ->where('g.disable',1)
                  ->select();
    		$mealCategory = DB::name('SetMealCategory')->where('id',$cid)->where('isDelete',0)->find();
		    $this->assign('mid',$mid);
		    $this->assign('cid',$cid);
		    $this->assign('mealCategory',$mealCategory);
		    $this->assign('categoryFood',$categoryFood);
			return $this->fetch();
    	}
    }

    public function delcategory(){
        if( Request::instance()->isPost() ) {
            $id = input('id');
            $contact_number = session('mob_user.contact_number');
            $category = DB::name('SetMealCategory')->where('id',$id)->find();
            $meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$category['mid'])->where('isDelete',0)->find();
            if($meal){
                $res = DB::name('SetMealCategory')->where('id',$id)->update(['utime'=>time(),'isDelete'=>1]);
                if($res!==false){
                    return $this->success('刪除成功');
                }else{
                    return $this->success('刪除失敗');
                }
            }else{
                return $this->success('刪除成功');
            }

        }
    }
    public function addfood(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            $contact_number = session('mob_user.contact_number');
            $meal = DB::name('SetMeal')
                ->where('contactNumber',$contact_number)
                ->where('id',$params['mid'])
                ->where('isDelete',0)
                ->find();
            $time = time();
            if($meal){
                if(!empty($params['food'])){
                    $save = array();
                    foreach ($params['food'] as $key => $val) {
                        $save[] = ['mid'=>$params['mid'],'cid'=>$params['cid'],'gid'=>$val,'ctime'=>$time];
                    }
                    $res = DB::name('SetMealInfo')->insertAll($save);
                    if($res){
                        $this->success('添加成功');
                    }else{
                        $this->success('添加失敗');
                    }
                }else{
                    $this->error('沒有新添加的菜式');
                }
            }else{
                $this->error('添加失敗');
            }
        }else{
            $list = array();
            $return = array();
            $mid = input('mid');
            $cid = input('cid');
            $categoryFoodId = array();
            $contact_number = session('mob_user.contact_number');
            $category = DB::name('Category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
            foreach ($category as $key => $val) {
                $list[$val['id']] = $val;
            }
            $foods = DB::name('Goods')->field('id,name,categoryId,thumbnailUrl,printerId')->where('contactNumber',$contact_number)->where('isDelete',0)->select();
            foreach ($foods as $key => $val) {
                $list[$val['categoryId']]['_food'][] = $val;
            }

            foreach ($list as $k => $v) {
                if(!empty($v['_food'])){
                    $return[] = $v;
                }
            }
            $mealCategory = DB::name('SetMealCategory')->where('id',$cid)->where('isDelete',0)->find();
            $categoryFood = DB::name('SetMealInfo')->where('mid',$mid)->where('cid',$cid)->where('isDelete',0)->select();
            $category = DB::name('category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact_number)->where('status',1)->where('isDelete',0)->select();
            foreach ($categoryFood as $key => $val) {
                $categoryFoodId[] = $val['gid'];
            }
            $this->assign('mid',$mid);
            $this->assign('cid',$cid);
            $this->assign('category',$category);
            $this->assign('mealCategory',$mealCategory);
            $this->assign('categoryFoodId',$categoryFoodId);
            $this->assign('list',$return);
            $this->assign('foods',$foods);
            return $this->fetch();
        }

    }
    public function delfood(){
        if( Request::instance()->isPost() ) {
            $params = input('param.');
            if(!empty($params['delfood'])){
                $contact_number = session('mob_user.contact_number');
                $meal = DB::name('SetMeal')->where('contactNumber',$contact_number)->where('id',$params['mid'])->where('isDelete',0)->find();
                if($meal){
                    $res = true;
                    DB::startTrans();
                    try{
                        foreach ($params['delfood'] as $key => $val) {
                            $res = DB::name('setMealInfo')->where('mid',$params['mid'])->where('cid',$params['cid'])->where('gid',$val)->where('isDelete',0)->update(['isDelete'=>1,'utime'=>time()]);
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        $res = false;
                        // 回滚事务
                        Db::rollback();
                    }
                    if($res!==false){
                        return $this->success('刪除成功');
                    }else{
                        return $this->error('刪除失敗');
                    }
                }else{
                    return $this->error('刪除失敗');

                }
            }else{
                return $this->error('没有需要删除的菜式');
            }

        }
    }

}