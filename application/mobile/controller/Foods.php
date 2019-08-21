<?php
namespace app\mobile\controller;
use app\mobile\controller\Base;
use think\View;//视图
use think\Controller;//控制器
use think\Redirect;//重定向
use think\Validate;
use think\Session;//session
use think\Loader;//引入model
use think\Request;//请求
use think\File;//文件上传
use think\Url;//路由
use think\Db;//数据库

class Foods extends Base {
    public function index() {
        if(session('mob_user.is_contact')!=0){
            $contact_number = session('mob_user.contact_number');
        }else{
            $contact_number = '';
        }
        //搜索菜品
        if(Request::instance()->isPost()&&!empty(input('param.keyword'))) {
            $keyword = input('param.keyword');
            $foods = DB::name('goods')
                       ->field('id,'.__('name','name_en','name_other').' as name,thumbnailUrl,salePrice,number,categoryId,categoryName,'.__('remark','remark_en','remark_other').' as remark,disable')
                       ->where('contactNumber',$contact_number)
                       ->where('isDelete',0)
                       ->where(__('name','name_en','name_other'),'like','%'.$keyword.'%')
                       ->order('categoryId desc,id desc')
                       ->select();
            return json($foods);exit();
        }
        $category = DB::name('category')
            ->field('id,name')
            ->where('contactNumber',$contact_number)
            ->where('typeNumber','trade')
            ->where('isDelete',0)
            ->order('ordnum asc')
            ->select();
        $foods = DB::name('goods')
            ->field('id,name,thumbnailUrl,salePrice,number,categoryId,categoryName,remark,disable')
            ->where('contactNumber',$contact_number)
            ->where('isDelete',0)
            //->order('categoryId desc,id desc')
            ->order('sort asc')
            ->select();
        $meal = DB::name('setMeal')
            ->field('id,name,thumbnailUrl,totlePrice,categoryId,remark,status')
            ->where('contactNumber',$contact_number)
            ->where('isDelete',0)
            ->order('id desc')
            ->select();
        $list = array();
        foreach ($category as $key => $value) {
            $list[$value['id']]['id'] = $value['id'];
            $list[$value['id']]['name'] = $value['name'];
        }
        foreach ($foods as $key => $val) {
            $val['jumpType'] = 1;
            if(isset($list[$val['categoryId']])){
                $list[$val['categoryId']]['_child'][] = $val;
            }
        }
        foreach ($meal as $key => $val) {
            $val['jumpType'] = 2;
            if(isset($list[$val['categoryId']])){
                $list[$val['categoryId']]['_child'][] = $val;
            }
        }
        $id = session('mob_user.zid');
        $contact = DB::name('contact')->field('logoUrl,name')->where('number',$contact_number)->find();
        $this->assign('contact',$contact);
        $this->assign('list',$list);
        return $this->fetch();
    }

    //餐桌列表
    public function add(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            if(!empty($post['spec'])){
                foreach ($post['spec'] as $k => $v) {
                    foreach ($v as $key => $val) {
                        if(empty($val['price'])){
                            $val['price'] = 0;
                        }
                        $check   = array(
                            'price'  => $val['price'],
                        );
                        $rule = [
                            'price'  => 'number|egt:0',
                        ];
                        $msg = [
                            'price.number'  => '價格不正確',
                            'price.egt'     => '價格不正確',
                        ];
                        $validate = new Validate($rule, $msg);
                        if (!$validate->check($check)) {
                            return $this->error($validate->getError());
                        }
                    }
                }
            }
            $foodcount = DB::name('goods')->where('contactNumber',$contact_number)->count();
            $foodcount=sprintf("%06d", $foodcount);
            $save = array(
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'payType'=>$post['payType'],
                'number'=>$contact_number.$foodcount,
                'remark'=>$post['remark'],
                'remark_en'=>$post['remark_en'],
                'remark_other'=>$post['remark_other'],
                'detail'=>$post['detail'],
                'detail_en'=>$post['detail_en'],
                'detail_other'=>$post['detail_other'],
                'disable'=>$post['status'],
                'salePrice'=>$post['price'],
                'contactNumber'=>$contact_number,
                'imgUrl'=>$post['pic_path'],
                'thumbnailUrl'=>$post['thumb_path'],
                'originUrl'=>$post['originurl'],
                'categoryId'=>$post['categoryId'],
                'categoryName'=>$post['categoryName'],
            );
            !empty($post['departmentId'])&&$save['departmentId'] = $post['departmentId'];
            if($save['payType']==2){
                $save['payUnit'] = $post['payUnit'];
            }
            if(!empty($save['imgUrl'])){
                $isbase['imgUrl'] = is_base64_picture($save['imgUrl']);
            }else{
                $this->error('請上傳圖片');die;
            }
            if(!empty($save['originUrl']) && is_base64_picture($save['originUrl'])){
                $originimg = save_base_img($save['originUrl'],'uploads/origin');
                $save['originUrl'] = $originimg['path'];
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
            if (loader::validate('Goods')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Goods')->getError());
            }
            $res = DB::name('Goods')->insertGetId($save);
            if($res){
                $spec = array();
                if(!empty($post['spec'])){
                    foreach ($post['spec'] as $k => $v) {
                        foreach ($v as $key => $val) {
                            if(empty($val['price'])){
                                $val['price'] = 0;
                            }
                            $spec[] = ['gs_good_id'=>$res,'gs_spec_pid'=>$k,'gs_spec_id'=>$key,'is_default'=>array_key_exists('is_default',$val)?1:0,'is_repeat'=>array_key_exists('is_repeat',$val)?1:0,'gs_price'=>$val['price'],'contactNumber'=>$contact_number,'gs_spec_order'=>$val['order']];
                        }
                    }
                    if (($data = Loader::model('GoodsSpec')->gsAdd($spec)) === false) {
                        return $this->error(Loader::model('GoodsSpec')->getError());
                    }
                }
                return $this->success('添加成功', Url::build('foods/index'));
            }else{
                return $this->success('添加失敗');
            }
        }else{
            $contact_number = session('mob_user.contact_number');
            $showid = array();
            $speclist = array();
            $spec = DB::name('spec')->where('isDelete',0)->where('spec_pid',0)->where('contactNumber',$contact_number)->select();
            foreach ($spec as $key => $val) {
                $showid[] = $val['id'];
            }
            if(!empty($showid)){
                $showid = implode(',', $showid);
                $speclist = DB::name('spec')->where('isDelete',0)->where('spec_pid','in',$showid)->select();
                foreach ($speclist as $k => $v) {
                    $spec[] = $v;
                }
                $speclist = array();
                foreach ($spec as $key => $value) {
                    $speclist[$value['id']] = $value;
                }
                foreach ($speclist as $key => $val) {
                    if($val['spec_pid']!=0){
                        $speclist[$val['spec_pid']]['_child'][$val['id']] = $val;
                        unset($speclist[$key]);
                    }
                }
            }
            $category = DB::name('category')->field('id,name')->where('typeNumber','trade')->where('contactNumber',$contact_number)->where('status',1)->where('isDelete',0)->select();
            $department = DB::name('ContactDepartment')->field('id,name')->where('contactNumber',$contact_number)->where('isDelete',0)->order('id desc')->select();
            $this->assign('department',$department);
            $this->assign('category',$category);
            $this->assign('spec',$speclist);
            return $this->fetch();
        }

    }

    //餐桌列表
    public function edit(){
        if( Request::instance()->isPost() ) {
            $post = input('param.');
            $contact_number = session('mob_user.contact_number');
            if(!empty($post['spec'])){
                foreach ($post['spec'] as $k => $v) {
                    foreach ($v as $key => $val) {
                        if(empty($val['price'])){
                            $val['price'] = 0;
                        }
                        $check   = array(
                            'price'  => $val['price'],
                        );
                        $rule = [
                            'price'  => 'number|egt:0',
                        ];
                        $msg = [
                            'price.number'  => '價格不正確',
                            'price.egt'     => '價格不正確',
                        ];
                        $validate = new Validate($rule, $msg);
                        if (!$validate->check($check)) {
                            return $this->error($validate->getError());
                        }
                    }
                }
            }
            $save = array(
                'id'=>$post['id'],
                'name'=>$post['name'],
                'name_en'=>$post['name_en'],
                'name_other'=>$post['name_other'],
                'payType'=>$post['payType'],
                'remark'=>$post['remark'],
                'remark_en'=>$post['remark_en'],
                'remark_other'=>$post['remark_other'],
                'detail'=>$post['detail'],
                'detail_en'=>$post['detail_en'],
                'detail_other'=>$post['detail_other'],
                'disable'=>$post['status'],
                'salePrice'=>$post['price'],
                'imgUrl'=>$post['pic_path'],
                'thumbnailUrl'=>$post['thumb_path'],
                'originUrl'=>$post['originurl'],
                'categoryId'=>$post['categoryId'],
                'categoryName'=>$post['categoryName'],
            );
            !empty($post['departmentId'])&&$save['departmentId'] = $post['departmentId'];
            if($save['payType']==2){
                $save['payUnit'] = $post['payUnit'];
            }
            if(!empty($save['imgUrl'])){
                $isbase['imgUrl'] = is_base64_picture($save['imgUrl']);
            }else{
                $this->error('請上傳圖片');die;
            }
            if(!empty($save['originUrl']) && is_base64_picture($save['originUrl'])){
                $originimg = save_base_img($save['originUrl'],'uploads/origin');
                $save['originUrl'] = $originimg['path'];
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
            if (loader::validate('Goods')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Goods')->getError());
            }
            $res = DB::name('goods')->where('id',$post['id'])->where('contactNumber',$contact_number)->update($save);
            if($res!==false){
                if(!empty($post['spec'])){
                    $delold = array();
                    foreach ($post['spec'] as $k => $v) {
                        foreach ($v as $key => $val) {
                            if(empty($val['price'])){
                                $val['price'] = 0;
                            }
                            if(!empty($val['id'])){
                                $spec[] = ['id'=>$val['id'],'gs_good_id'=>$post['id'],'gs_spec_pid'=>$k,'is_default'=>array_key_exists('is_default',$val)?1:0,'is_repeat'=>array_key_exists('is_repeat',$val)?1:0,'gs_spec_id'=>$key,'gs_price'=>$val['price'],'contactNumber'=>$contact_number,'gs_spec_order'=>$val['order']];
                            }else{
                                $delold[] = ['gs_good_id'=>$post['id'],'gs_spec_pid'=>$k,'gs_spec_id'=>$key];
                                $spec[] = ['gs_good_id'=>$post['id'],'gs_spec_pid'=>$k,'gs_spec_id'=>$key,'is_default'=>array_key_exists('is_default',$val)?1:0,'is_repeat'=>array_key_exists('is_repeat',$val)?1:0,'gs_price'=>$val['price'],'contactNumber'=>$contact_number,'gs_spec_order'=>$val['order']];
                            }
                        }
                    }
                    if (($data = Loader::model('GoodsSpec')->gsEdit($spec)) === false) {
                        $this->error(Loader::model('GoodsSpec')->getError());
                    }
                }else{
                    //沒有規格清空已有規格
                    if (($data = Loader::model('GoodsSpec')->gsClear($post['id'])) === false) {
                        $this->error(Loader::model('GoodsSpec')->getError());
                    }
                }
                $this->success('修改成功', Url::build('foods/index'));
            }else{
                $this->success('修改失敗');
            }
        }else{
            $id = input('param.id');
            $contact_number = session('mob_user.contact_number');
            $speclist = array();
            $contactSpecs = DB::name('spec')
                ->where('isDelete',0)
                ->where('contactNumber',$contact_number)
                ->select();

            if(!empty($contactSpecs)){
                foreach($contactSpecs as $spec){
                    if($spec['spec_pid'] == 0){
                        /*这里只能单个地赋值，不能整个数组组合赋值*/
                        $speclist[$spec['id']]['id'] = $spec['id'];
                        $speclist[$spec['id']]['spec_pid'] = $spec['spec_pid'];
                        $speclist[$spec['id']]['spec_name'] = $spec['spec_name'];
                        $speclist[$spec['id']]['spec_disable'] = $spec['spec_disable'];
                        $speclist[$spec['id']]['spec_order'] = $spec['spec_order'];
                        $speclist[$spec['id']]['spec_price'] = $spec['spec_price'];
                        $speclist[$spec['id']]['contactNumber'] = $spec['contactNumber'];
                        $speclist[$spec['id']]['minselect'] = $spec['minselect'];
                        $speclist[$spec['id']]['maxselect'] = $spec['maxselect'];
                        $speclist[$spec['id']]['isDelete'] = $spec['isDelete'];
                        $speclist[$spec['id']]['is_repeat'] = $spec['is_repeat'];
                        $speclist[$spec['id']]['is_default'] = $spec['is_default'];
                    }else{
                        $speclist[$spec['spec_pid']]['_child'][$spec['id']] = $spec;
                    }
                }
            }

            $category = DB::name('category')
                ->field('id,name')
                ->where('typeNumber','trade')
                ->where('contactNumber',$contact_number)
                ->where('status',1)
                ->where('isDelete',0)
                ->select();

            $food = DB::name('goods')->field('id,name,name_en,name_other,imgUrl,thumbnailUrl,salePrice,number,categoryId,categoryName,disable,remark,remark_en,remark_other,detail,detail_en,detail_other,payType,payUnit,departmentId')
                ->where('id',$id)
                ->where('contactNumber',$contact_number)
                ->where('isDelete',0)
                ->find();

            $foodSpecs = DB::name('GoodsSpec')
                ->alias('gs')
                ->join('mos_spec s','s.id = gs.gs_spec_id','left')
                ->field('gs.*,s.isDelete')
                ->where('s.isDelete',0)
                ->where('gs.gs_good_id',$food['id'])
                ->where('gs.contactNumber',$contact_number)
                ->where('gs.isDelete',0)
                ->select();

            $goodsSpecList = array();

            foreach ($foodSpecs as $key => $spec) {

                if(!empty($speclist[$spec['gs_spec_pid']])){
                    $spec['name'] = $speclist[$spec['gs_spec_pid']]['_child'][$spec['gs_spec_id']]['spec_name'];
                    $goodsSpecList[$spec['gs_spec_pid']]['id'] = $spec['gs_spec_pid'];
                    $goodsSpecList[$spec['gs_spec_pid']]['name'] = $speclist[$spec['gs_spec_pid']]['spec_name'];
                    $goodsSpecList[$spec['gs_spec_pid']]['_child'][] = $spec;
                }
            }
            $department = DB::name('ContactDepartment')
                ->field('id,name')
                ->where('contactNumber',$contact_number)
                ->where('isDelete',0)
                ->order('id desc')
                ->select();
            $department_name = ['id'=>NULL,'name'=>'默認崗位'];
            if(count($department)>0){
                $food_department = empty($department)?[]:array_column($department,NULL,'id');
                if(isset($food_department[$food['departmentId']])){
                    $department_name = $food_department[$food['departmentId']];
                }
            }
            if(!$food){
                $this->error('找不到該菜式！');
            }else{
                $this->assign('department',$department);
                $this->assign('department_name',$department_name);
                $this->assign('spec',$speclist);
                $this->assign('goodsSpec',$goodsSpecList);
                $this->assign('foods',$food);
                $this->assign('category',$category);
            }
            return $this->fetch();
        }
    }

    // 圖片上傳
    public function uploadImg(){
        $request = Request::instance();
        $file = request()->file('image');
        $return = array();
        if($file){
            // 获取缩略图宽高
            $width  = config('Thumwidth');
            $height = config('Thumheight');
            // 保存缩略图
            $thumb = img_create_small($file,$width,$height,"uploads/Thumbnail");
            // 调用上传方法 保存原图
            $uploads = uploadPic($file,'uploads/foods');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($thumb['code']==1){
                $return['thumb'] = $thumb['msg'];
            }else{
                return $this->error($thumb['msg']);
            }
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                $return['code'] = 1;
                $return['msg']  = $uploads['msg'];
                return  $return;
            }else{
                return $this->error($uploads['msg']);
            }
        }
    }

    // 圖片上傳
    public function uploadOriginImg(){
        $request = Request::instance();
        $file = request()->file('imageName');
        $return = array();
        if($file){
            // 获取缩略图宽高
            $width  = config('Thumwidth');
            $height = config('Thumheight');
            // 调用上传方法 保存原图
            $uploads = img_create($file,'uploads/origin');
            // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
            if($uploads['code']==1){
                $return['code'] = 1;
                $return['msg']  = $uploads['msg'];
                return  $return;
            }else{
                return $this->error($uploads['msg']);
            }
        }else{
            return "确少参数";
        }
    }

    public function del(){
        if( Request::instance()->isPost() ) {
            $id = input('param.id');
            $contact_number = session('mob_user.contact_number');
            $res = DB::name('Goods')->where('id',$id)->where('contactNumber',$contact_number)->update(['isDelete'=>1]);
            if($res!==false){
                return $this->success('删除成功', Url::build('foods/index'));
            }else{
                return $this->success('删除失敗');
            }
        }

    }

    public function changeDisable(){
        if( Request::instance()->isPost() ) {
            $param = input('param.');
            $contactNumber = session('mob_user.contact_number');
            if(!empty($param['id'])&&isset($param['status'])){
                $food = DB::name('goods')->where('id',$param['id'])->where('disable',$param['status'])->where('contactNumber',$contactNumber)->find();
                if(!empty($food)){
                    if($param['status']==1){
                        $update['disable'] = 0;
                        $return = '菜式禁用';
                    }else{
                        $update['disable'] = 1;
                        $return = '菜式啟用';
                    }
                    $res = DB::name('goods')->where('id',$param['id'])->where('disable',$param['status'])->where('contactNumber',$contactNumber)->update($update);
                    if($res!==false){
                        $return .= '成功';
                        return $this->success($return);
                    }else{
                        $return .= '失敗';
                        return $this->error($return);
                    }
                }else{
                    return $this->error('菜式信息錯誤');
                }
            }
        }
    }

    //菜品排行
    public function topFood()
    {
        $number = session('mob_user.contact_number');
        if(Request::instance()->isPost()) {
            $order     = !empty(input('param.order')) && in_array(input('param.order'),
                ['desc', 'asc']) ? input('param.order') : 'desc';
            $Data      = Db::name('goods')
                           ->alias('g')
                           ->join('mos_wx_order_goods og ', 'og.goodsId= g.id', 'LEFT')
                           ->join('mos_wx_order o', 'o.orderSN= og.orderSN', 'LEFT')
                           ->field('og.goodsId,g.name,sum(og.num) as food_count')
                           ->where('og.contactNumber', $number)
                           ->where('o.orderStatus', 4)
                           ->where('o.payStatus', 1)
                           ->where('o.createTime', '>', (time() - 86400 * 30))
                           ->where('og.goodsType', '<>', 3)
                           ->order('food_count '.$order)
                           ->group('og.goodsId')
                           ->select();
            $max_count = 0;
            if(count($Data) > 0) {
                //提取数量列，取最大值
                foreach($Data as $k => $v) {
                    $temp[] = $v['food_count'];
                }
                $max_count = max($temp);
                //引用赋值一个百分比列
                foreach($Data as &$item) {
                    $item['percent'] = intval($item['food_count'] / $max_count * 100);
                }
                unset($item);
            }
            return json($Data);
        }else{
            //$order = $order=='desc'?'asc':'desc';
            //$this->assign('order',$order);
            //$this->assign('fooddata',$Data);
            return $this->fetch();
        }
    }

    //菜式管理
    public function foodManage(){
        return $this->fetch();
    }
}