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
class Spec extends AdminBase {
    
    public function index() {
        $param = input('get.');
        $where = array();
        $disable = ['禁用','启用','不可选'];
        $contact = DB::name('Contact')->field('id,number,name')->where(['isDelete'=>0])->select();
        // 系统管理员可以看到全部分类
        $spec = Loader::model('Spec')->alias('s')->join('mos_contact c','s.contactNumber = c.number','left')->field('*,s.id as id');
        if(isset($param['contact'])&&$param['contact']!==''){
            $spec->where('s.contactNumber',$param['contact']);
        }
        $list = $spec->where('s.isDelete',0)
            ->where('s.spec_pid',0)
            ->order('s.spec_order asc,s.id desc')
            ->paginate(10,false,['query'=>$param]);
        $this->assign('param',$param);
        $this->assign('disable',$disable);
        $this->assign('contact',$contact);
        $this->assign('list', $list);
        $this->assign('pages',$list->render());
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
            $save = [
                'spec_name'     => $params['name'],
                'spec_pid'      => $params['parent'],
                'spec_order'    => !empty($params['sort'])?$params['sort']:500,
                'spec_disable'  => $params['status'],
                'contactNumber' => $params['contact'],
            ];
            if (loader::validate('Spec')->scene('add')->check($save) === false) {
                return $this->error(loader::validate('Spec')->getError());
            }
            if (($id = Loader::model('Spec')->insertGetId($save)) === false) {
                return $this->error(Loader::model('Spec')->getError());
            }
            Loader::model('SystemLog')->record("添加规格,ID:[{$id}]");
            return $this->success('添加规格成功', Url::build('spec/index'));
        }else{
            $contact = DB::name('contact')->field('id,number,name')->where('disable',1)->where('isDelete',0)->select();
            $this->assign('contact',$contact);
            return $this->fetch();
        }
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
            $save = [
                'id'            => $params['id'],
                'spec_name'     => $params['name'],
                'spec_pid'      => $params['parent'],
                'spec_order'    => !empty($params['sort'])?$params['sort']:500,
                'spec_disable'  => $params['status'],
                'contactNumber' => $params['contact'],
            ];
            if (loader::validate('Spec')->scene('edit')->check($save) === false) {
                return $this->error(loader::validate('Spec')->getError());
            }
            if (($id = Loader::model('Spec')->update($save)) === false) {
                return $this->error(Loader::model('Spec')->getError());
            }
            Loader::model('SystemLog')->record("修改规格,ID:[{$id}]");
            return $this->success('修改规格成功', Url::build('spec/index'));
        }else{
            $specModel = DB::name('Spec');
            $spec = DB::name('Spec')->where('id',$id)->where('isDelete',0)->find();
            $list = DB::name('Spec')->where('contactNumber',$spec['contactNumber'])->where(array('isDelete' => 0,'spec_pid' => 0))->order('spec_order asc,id desc')->select();
            $contact = DB::name('contact')->field('id,number,name')->where('disable',1)->where('isDelete',0)->select();
            $this->assign('spec',$spec);
            $this->assign('list',$list);
            $this->assign('contact',$contact);
            return $this->fetch();
        }
    }

  /**
     * [destroy description]
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function del() {
        $request = Request::instance();
        $id = $request->param('id');
        $save['id'] = $id;
        $save['isDelete'] = 1;
        $del = DB::name('Spec')->update($save);
        Loader::model('SystemLog')->record("規格删除,ID:[{$id}]");
        return $this->success('規格删除成功', Url::build('spec/index'));
    }

    public function importspec(){
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
                        if($sheetKey!='temp-spec'){
                            $this->error('请选择上传正确的模板!');
                        }
                        $savelist = array();
                        for ($i=5; $i <= $highestRow; $i++) {
                            // 餐厅编号
                            $contactNumber = $params['contactNumber'];
                            // 规格名
                            $name = $objActSheet->getCell('A'.$i)->getValue();
                            $child = $objActSheet->getCell('B'.$i)->getValue();
                            if(!empty($child)){
                                // 把中文逗号转为英文逗号便于切割字符串
                                $child = str_replace('，',',', $child);
                                $child = explode(',', $child);
                                // 将父级先保存到数据库获取id作为子集id
                                $parent = array('spec_name'=>$name,'spec_pid'=>0,'spec_disable'=>1,'spec_order'=>10,'contactNumber'=>$contactNumber);
                                $parentId = DB::name('spec')->insertGetId($parent);
                                foreach ($child as $key => $val) {
                                    $savelist[] = array(
                                        'spec_name'=>!empty($val)?$val:'',
                                        'spec_pid'=>$parentId,
                                        'spec_disable'=>1,
                                        'spec_order'=>10,
                                        'contactNumber'=>!empty($contactNumber)?$contactNumber:'',
                                    );
                                }
                            }else{
                                $savelist[] = array(
                                    'spec_name'=>$name,
                                    'spec_pid'=>0,
                                    'spec_disable'=>1,
                                    'spec_order'=>10,
                                    'contactNumber'=>!empty($contactNumber)?$contactNumber:'',
                                );
                            }
                        }
                        $add = DB::name('spec')->insertAll($savelist);
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