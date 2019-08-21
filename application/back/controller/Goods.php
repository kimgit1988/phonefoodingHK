<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use app\common\model\Category;
use think\File;
use think\Request;
use think\Controller;
use think\Loader;
use think\Url;
use think\Db;
class Goods extends AdminBase {

    public function index() {
        $param = input('get.');
        $where = array();
        $category = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$param['contact'])->select();
            $where['g.contactNumber'] = $param['contact'];
        }
        if(isset($param['category'])&&$param['category']!==''){
            $where['g.categoryId'] = $param['category'];
        }
        if(isset($param['search'])&&$param['search']!==''){
            $where['g.name|g.number'] = ['like','%'.$param['search'].'%'];
        }
        // 正常来说该权限分配给商家需要判断商家
        $goods = Loader::model('Goods')
            ->alias('g')
            ->join('mos_contact c','g.contactNumber = c.number','left')
            ->field('*,g.id as gid,g.number as gnumber,g.disable as gdisable,g.name as gname')
            ->where($where)
            ->where(['g.isDelete'=>0])
            ->order('g.id desc')
            ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('lists', $goods);
        $this->assign('contact',$contact);
        $this->assign('category', $category);
        $this->assign('pages',$goods->render());
        return $this->fetch();
    }

    /**
     * [add description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function add() {
        $request = Request::instance();
        if ($request->isPost()) {
            $params   = $request->param();
            if(!empty($params['contactNumber'])){
                $contact_number = $params['contactNumber'];
                $foodcount = DB::name('goods')->where('contactNumber',$params['contactNumber'])->count();
                $foodcount=sprintf("%06d", $foodcount);
                $params['number']        = $contact_number.$foodcount;
                $params['contactNumber'] = $contact_number;
            }else{
                return $this->error('請選擇餐廳');
            }
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('id',$params['cCategory'])->find();
            if(empty($category)){
                return $this->error('請選擇正確的分類');
            }else{
                $params['categoryId']     = $category['id'];
                $params['categoryName']   = $category['name'];
            }
            $file = request()->file('image');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                // 保存缩略图
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $params['thumbnailUrl'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
                // 调用上传方法 保存原图
                $uploads = uploadPic($file,'foods');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($uploads['code']==1){
                    $params['imgUrl'] = $uploads['msg'];
                }else{
                    return $this->error($uploads['msg']);
                }
            }
            if (loader::validate('Goods')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Goods')->getError());
            }
            if (($id = Loader::model('Goods')->goodsAdd($params)) === false) {
                return $this->error(Loader::model('Goods')->getError());
            }
            if(!empty($params['spec'])){
                $spec = array();
                foreach ($params['spec'] as $k => $v) {
                    $spec[] = ['gs_good_id'=>$id,'gs_spec_pid'=>$v['pid'],'gs_spec_id'=>$v['id'],'gs_price'=>$v['price'],'contactNumber'=>$contact_number];
                }
                if (($data = Loader::model('GoodsSpec')->gsAdd($spec)) === false) {
                    return $this->error(Loader::model('GoodsSpec')->getError());
                }
            }
            Loader::model('SystemLog')->record("添加菜品,ID:[{$id}]");
            return $this->success('添加菜品成功', Url::build('Goods/index'));
        }else{
            $contactType =DB::name('contact')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
            $this->assign('type',$contactType);
        }
        return $this->fetch();
    }


    /**
     * [edit description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */

    public function edit() {
        $request = Request::instance();
        $id = $request->param('id');
        if ($request->isPost()) {
            $params   = $request->param();
            if(!empty($params['contactNumber'])){
                $contact_number = $params['contactNumber'];
                $foodcount = DB::name('goods')->where('contactNumber',$params['contactNumber'])->count();
                $foodcount=sprintf("%06d", $foodcount);
                $params['number']        = $contact_number.$foodcount;
                $params['contactNumber'] = $contact_number;
            }else{
                return $this->error('請選擇餐廳');
            }
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('id',$params['cCategory'])->find();
            if(empty($category)){
                return $this->error('請選擇正確的分類');
            }else{
                $params['categoryId']     = $category['id'];
                $params['categoryName']   = $category['name'];
            }
            $file = request()->file('image');
            if($file){
                // 调用上传方法
                $width  = config('Thumwidth');
                $height = config('Thumheight');
                // 保存缩略图
                $upload = img_create_small($file,$width,$height,"uploads/Thumbnail");
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($upload['code']==1){
                    $params['thumbnailUrl'] = $upload['msg'];
                }else{
                    return $this->error($upload['msg']);
                }
                // 调用上传方法 保存原图
                $uploads = uploadPic($file,'foods');
                // 1为上传成功 msg为返回地址 其他为失败 msg为错误信息
                if($uploads['code']==1){
                    $params['imgUrl'] = $uploads['msg'];
                }else{
                    return $this->error($uploads['msg']);
                }
            }
            if (loader::validate('Goods')->scene('set')->check($params) === false) {
                return $this->error(loader::validate('Goods')->getError());
            }
            if (($cateId = Loader::model('Goods')->goodsEdit($params)) === false) {
                return $this->error(Loader::model('Goods')->getError());
            }
            if(!empty($params['spec'])){
                $spec = array();
                // 循環規格傳參
                foreach ($params['spec'] as $k => $v) {
                    // 有id的為已有規格直接修改
                    if(!empty($v['num'])){
                        $spec[] = ['id'=>$v['num'],'gs_good_id'=>$id,'gs_spec_pid'=>$v['pid'],'gs_spec_id'=>$v['id'],'gs_price'=>$v['price'],'contactNumber'=>$contact_number];
                        // 沒有規格的為新增
                    }else{
                        $spec[] = ['gs_good_id'=>$id,'gs_spec_pid'=>$v['pid'],'gs_spec_id'=>$v['id'],'gs_price'=>$v['price'],'contactNumber'=>$contact_number];
                    }
                }
                if (($data = Loader::model('GoodsSpec')->gsEdit($spec)) === false) {
                    return $this->error(Loader::model('GoodsSpec')->getError());
                }
            }else{
                //沒有規格清空已有規格
                if (($data = Loader::model('GoodsSpec')->gsClear($id)) === false) {
                    return $this->error(Loader::model('GoodsSpec')->getError());
                }
            }

            Loader::model('SystemLog')->record("菜品编辑,ID:[{$id}]");
            return $this->success('菜品编辑成功', Url::build('goods/index'));
        }else{
            $showid = array();
            $goods = DB::name('Goods')->where('isDelete',0)->where('id',$id)->find();
            $contactType =DB::name('contact')->where('isDelete',0)->where('disable',1)->order('id desc')->select();
            $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$goods['contactNumber'])->select();
            $speclist = array();
            $spec = DB::name('Spec')->where('isDelete',0)->where('spec_pid',0)->where('contactNumber',$goods['contactNumber'])->select();
            $gs = DB::name('GoodsSpec')->where('isDelete',0)->where('gs_good_id',$id)->where('contactNumber',$goods['contactNumber'])->select();
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
            $department = DB::name('ContactDepartment')->field('id,name')->where('contactNumber',$goods['contactNumber'])->where('isDelete',0)->order('id desc')->select();
            $this->assign('department',$department);
            $this->assign('category',$category);
            $this->assign('type',$contactType);
            $this->assign('spec',$speclist);
            $this->assign('Goods',$goods);
            $this->assign('gs',$gs);
        }
        return $this->fetch();
    }

    /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function destroy() {
        $request = Request::instance();
        $id = $request->param('id');
        if (Loader::model('Goods')->deleteGoods($id) === false) {
            return $this->error(Loader::model('Goods')->getError());
        }
        Loader::model('SystemLog')->record("菜品删除,ID:[{$id}]");
        return $this->success('菜品删除成功', Url::build('Goods/index'));
    }

    public function importfood(){
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('excel');
            if(empty($params['contactNumber'])){
                $this->error('请选择餐厅');
            }
            if($file){
                $path = 'uploads/xls';
                // 保持文件名字TP自动添加名字
                $info = $file->validate(['ext'=>'xls'])->move(ROOT_PATH.'public'.'/'.$path);
                if ($info) {
                    // 获取文件路径及名称
                    $save = ROOT_PATH.'public'.'/'.$path.'/'.date("Ymd")."/".$info->getFilename();
                    //读取文件
                    if (file_exists($save)) {
                        $category = DB::name('Category')
                            ->field('id,name')
                            ->where('contactNumber',$params['contactNumber'])
                            ->where('isDelete', 0)
                            ->where('parentId' , 0)
                            ->where('typeNumber','trade')
                            ->order('ordnum asc')
                            ->select();
                        $foodcount  = DB::name('goods')->where('contactNumber',$params['contactNumber'])->count();
                        $now = array();
                        foreach ($category as $key => $val) {
                            $now[$val['name']] = $val['id'];
                        }
                        vendor("PHPExcel.PHPExcel");
                        // $objPHPExcel = new \PHPExcel();
                        $objReader = \PHPExcel_IOFactory::createReader ('Excel5');
                        $objPHPExcel = $objReader->load ($save);
                        $objActSheet = $objPHPExcel->getActiveSheet();
                        $highestRow = $objActSheet->getHighestRow(); // 取得总行数
                        if($highestRow<5){
                            $this->error('该模板无法获取数据!');
                        }
                        $sheetKey = $objActSheet->getCell('B2')->getValue(); // 获取表识别码避免选择错误分类
                        if($sheetKey!='temp-food'){
                            $this->error('请选择上传正确的模板!');
                        }
                        $savelist = array();
                        for ($i=5; $i <= $highestRow; $i++) {
                            // 餐厅编号
                            $contactNumber = $params['contactNumber'];
                            $foodnumber  = sprintf("%06d", $foodcount);
                            // 菜品编号
                            $number = $contactNumber.$foodnumber;
                            $foodcount = $foodcount+1;
                            // 菜品名
                            $name = $objActSheet->getCell('A'.$i)->getValue();
                            // 价格
                            $salePrice = $objActSheet->getCell('B'.$i)->getValue();
                            $category = $objActSheet->getCell('C'.$i)->getValue();
                            if(empty($category)){
                                $category="默认";
                            }
                            // 备注
                            $remark = $objActSheet->getCell('D'.$i)->getValue();
                            if(isset($now[$category])){
                                // 分类id
                                $categoryId = $now[$category];
                                // 分类名
                                $categoryName = $category;
                            }else{
                                $newcategory = array('name'=>$category,'parentId'=>0,'level'=>1,'ordnum'=>0,'status'=>1,'typeNumber'=>'trade','contactNumber'=>$contactNumber);
                                $new = DB::name('category')->insertGetId($newcategory);
                                $now[$category] = $new;
                                // 分类id
                                $categoryId = $new;
                                // 分类名
                                $categoryName = $category;
                            }
                            $savelist[] = array(
                                'name' => !empty($name)?$name:'',
                                'number' => !empty($number)?$number:'',
                                'categoryId'=> !empty($categoryId)?$categoryId:'',
                                'categoryName'=> !empty($categoryName)?$categoryName:'',
                                'contactNumber'=> !empty($contactNumber)?$contactNumber:'',
                                'salePrice'=> !empty($salePrice)?$salePrice:'',
                                'remark'=> !empty($remark)?$remark:'',
                            );
                        }
                        $add = DB::name('Goods')->insertAll($savelist);
                        if($add){
                            $this->success('导入成功');
                        }else{
                            $this->error('导入失败');
                        }
                    }else{
                        $this->error('找不到该文件!');
                    }
                }else{
                    $this->error('上传文件无法读取,请使用正确模板!');
                }
            }else{
                $this->error('上传文件失败!');
            }
        }else{
            $contact =DB::name('contact')->where('isDelete',0)->where('disable',1)->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }


}