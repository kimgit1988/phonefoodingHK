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
class Trade extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        if(isset($param['contact'])&&$param['contact']!==''){
            // $category = DB::name('Category')->where('isDelete',0)->where(['typeNumber'=>'trade'])->where('contactNumber',$param['contact'])->select();
            $where['t.contactNumber'] = $param['contact'];
        }
        // 系统管理员可以看到全部分类
        $cateModel = Loader::model('Category');
        $trade =$cateModel
        ->alias('t')
        ->field('*,t.id as id,t.name as name,c.name as cname')
        ->join('mos_contact c','t.contactNumber = c.number','left')
        ->where(array('t.isDelete' => 0,'t.parentId' => 0,'t.typeNumber'=>'trade'))
        ->where($where)
        ->order('t.ordnum asc')
        ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('contact',$contact);
        $this->assign('lists', $trade);
        $this->assign('pages',$trade->render());
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
            $params = $request->param();
            $params['parentId'] = 0;
            $params['level'] = 1;
            if(empty($params['contact'])){
                return $this->error('請選擇有效的餐廳');
            }
            if (loader::validate('Category')->scene('add')->check($params) === false) {
                return $this->error(loader::validate('Category')->getError());
            }
            if (($cateId = Loader::model('Category')->tradeAdd($params)) === false) {
                return $this->error(Loader::model('Category')->getError());
            }
            Loader::model('SystemLog')->record("添加餐桌分类,ID:[{$cateId}]");
            return $this->success('添加餐桌分类成功', Url::build('trade/index'));
        }else{
            if(session('ext_user.is_contact')==0){
                // 系统管理员可以看到全部分类
                $cateModel = Loader::model('Category');
                $trade =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'trade'))->order('ordnum asc')->select();
                
            }else{
                // 商家可以看到自己创建的分类(或管理员建立的)
                $contact_number = session('ext_user.contact_number');
                $cateModel = Loader::model('Category');
                $trade =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'trade'))->where('contactNumber',$contact_number)->order('ordnum asc')->select();
            }
            $this->assign('lists',$trade);
        }
        $contact = DB::name('contact')->field('id,number,name')->select();
        $this->assign('contact',$contact);
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
            $params = $request->param();
            $params['parentId'] = 0;
            $params['level'] = 1;
            if(empty($params['contact'])){
                return $this->error('請選擇有效的餐廳');
            }
            if (loader::validate('Category')->scene('edit')->check($params) === false) {
                return $this->error(loader::validate('Category')->getError());
            }
            if (($edit = Loader::model('Category')->tradeEdit($params)) === false) {
                return $this->error(Loader::model('Category')->getError());
            }
            Loader::model('SystemLog')->record("餐桌分类编辑,ID:[{$id}]");
            return $this->success('餐桌分类编辑成功', Url::build('trade/index'));
        }else{
            $cateModel = Loader::model('Category');
            if(session('ext_user.is_contact')==0){
                // 系统管理员修改全部分类
                $cateModel = Loader::model('Category');
                $trade = $cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'trade'))->find();
                $lists =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'trade'))->order('ordnum asc')->select();
            }else{
                // 商家可以修改自己创建的分类
                $contact_number = session('ext_user.contact_number');
                $cateModel = Loader::model('Category');
                $trade =$cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'trade'))->where('contactNumber',$contact_number)->find();
                $lists =$cateModel::where(array('isDelete' => 0,'parentId' => 0,'typeNumber'=>'trade'))->where('contactNumber',$contact_number)->order('ordnum asc')->select();
            }
            if(empty($trade)){
                return $this->error('该分类不存在或你无权修改！');
            }else{
                $this->assign('trade', $trade);
            }
            $this->assign('lists',$lists);
        }
        $contact = DB::name('contact')->field('id,number,name')->select();
        $this->assign('contact',$contact);
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
         if(session('ext_user.is_contact')!=0){
            $contact_number = session('ext_user.contact_number');
            $cateModel = Loader::model('Category');
            $trade =$cateModel::where(array('id'=>$id,'isDelete' => 0,'typeNumber'=>'trade'))->where('contactNumber',$contact_number)->find();
            if(empty($trade)){
                return $this->error('该分类不存在或你无权删除！');
            }
        }
        if (Loader::model('Category')->deleteAttr($id) === false) {
            return $this->error(Loader::model('Attr')->getError());
        }
        Loader::model('SystemLog')->record("菜品分类删除,ID:[{$id}]");
        return $this->success('菜品分类删除成功', Url::build('trade/index'));
    }

    public function importtrade(){
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
                        if($sheetKey!='temp-trade'){
                            $this->error('请选择上传正确的模板!');
                        }
                        $savelist = array();
                        for ($i=5; $i <= $highestRow; $i++) {
                            // 餐厅编号
                            $contactNumber = $params['contactNumber'];
                            // 分类名
                            $name = $objActSheet->getCell('A'.$i)->getValue();
                            // 分类名
                            $remark = $objActSheet->getCell('B'.$i)->getValue();
                            $savelist[] = array(
                                'name'=>!empty($name)?$name:'',
                                'parentId'=>0,
                                'level'=>1,
                                'ordnum'=>0,
                                'status'=>1,
                                'remark'=>!empty($remark)?$remark:'',
                                'typeNumber'=>'trade',
                                'contactNumber'=>!empty($contactNumber)?$contactNumber:'',
                            );
                        }
                        $add = DB::name('category')->insertAll($savelist);
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