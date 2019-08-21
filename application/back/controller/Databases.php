<?php
namespace app\back\controller;
use app\common\controller\AdminBase;
use\app\common\controller\DbManage;
use\think\Db;
use\think\Loader;
use\think\Request;
use\think\Config;
class Databases extends AdminBase {
    /**
     * 数据表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @return [type] [description]
     */
    public function index() {
        $databaseRows = array_map('array_change_key_case', Db::query('SHOW TABLE STATUS'));
        $this->assign('databaseRows', $databaseRows);
        return $this->fetch();
    }

    /**
     * 优化表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $table [description]
     * @return [type] [description]
     */
    public function optimize($table)
    {
        $optimize = Db::execute('OPTIMIZE TABLE `{$table}`');
        if ($optimize) {
        	Loader::model('SystemLog')->record("优化表[{$table}]");
            return $this->success("数据表【{$table}】优化成功");
        } else {
        	 
            return $this->error("数据表【{$table}】优化失败");
        }
    }
    /**
     * 修复表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $table [description]
     * @return [type] [description]
     */
    public function repair($table) {
        $optimize = Db::execute('REPAIR TABLE `{$table}`');
        if ($optimize) {
        	Loader::model('SystemLog')->record("修复表[{$table}]");
            return $this->success("数据表【{$table}】修复成功");
        } else {
            return $this->error("数据表【{$table}】修复失败");
        }
    }

    /**
     * 备份表
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $table [description]
     * @return [type] [description]
     */
    public function backup($table) {
        $backup = DbManage::backup($table);
        if ($backup) {
        	Loader::model('SystemLog')->record("备份表[{$table}]");
            return $this->success("数据表【{$table}】备份成功");
        } else {
            return $this->error("数据表【{$table}】备份失败");
        }
    }


/**
     * 备份整个数据库
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $table [description]
     * @return [type] [description]
     */
    public function backdata() {
        $host = Config::get('database.hostname'); //数据库名称
        $user = Config::get('database.username'); //数据库名称
        $password = Config::get('database.password'); //数据库名称
        $dbname = Config::get('database.database'); //数据库名称
        //php备份mysql数据库,导出sql文件
        // 这里的账号、密码、名称都是从页面传过来的
        $con = mysqli_connect($host, $user, $password, $dbname);
        if (!$con) // 连接mysql数据库
        {
            return $this->error("数据库连接失败，请核对后再试");
        }
        if (!mysqli_select_db($con, $dbname)) // 是否存在该数据库
        {
            return $this->error("不存在数据库:' . $dbname . ',请核对后再试");
        }
        mysqli_query($con, "set names 'utf8'");
        $mysql = "set charset utf8;\r\n";
        $q1 = mysqli_query($con, "show tables");
        while ($t = mysqli_fetch_array($q1)) {
            $table = $t[0];
            $q2 = mysqli_query($con, "show create table `$table`");
            $sql = mysqli_fetch_array($q2);
            $mysql .= $sql['Create Table'] . ";\r\n";
            $q3 = mysqli_query($con, "select * from `$table`");
            while ($data = mysqli_fetch_assoc($q3)) {
                $keys = array_keys($data);
                $keys = array_map('addslashes', $keys);
                $keys = join('`,`', $keys);
                $keys = "`".$keys."`";
                $vals = array_values($data);
                $vals = array_map('addslashes', $vals);
                $vals = join("','", $vals);
                $vals = "'".$vals."'\n\n";
                $mysql .= "insert into `$table`($keys) values($vals);\r\n\n";
                /*-------------------------------------------------------------------------------------------------------*/
            }
        }
        $dir = RUNTIME_PATH.'backDatabase'.DS.date("Ymd").DS;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $dbname.".sql";
        if (file_put_contents($dir.$filename, $mysql)) {
        	Loader::model('SystemLog')->record("备份数据库[{$dbname}]");
            return $this->success("数据库备份成功");
        }

    }

  /**
     * 导入sql文件
     * @author Zcc<2351976426@qq.com>
     * @dateTime 2016-10
     * @param  [type] $table [description]
     * @return [type] [description]
     */
    public function leaddata() {
        $host = Config::get('database.hostname');
        $user = Config::get('database.username');
        $password = Config::get('database.password');
        $dbname = Config::get('database.database');
        //php备份mysql数据库,导出sql文件
        // 这里的账号、密码、名称都是从页面传过来的
        $request = Request::instance();
        if ($request->isPost()) {
            $params = $request->param();
            $file = request()->file('file');
            if(!$file){
                return $this->error("未选择要导入的sql文件");
            }
            $dir = RUNTIME_PATH.'Data'.DS;
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $info = $file->move($dir, '');
            if ($info) {
                $con = mysqli_connect($host, $user, $password, $dbname);
                $filename = "./runtime/Data/".$info->getFilename();
                $sql = file_get_contents($filename);
                $arr = explode(';', $sql);
                foreach($arr as $value) {
                    $con->query($value.';');
                }
                $con->close();
                Loader::model('SystemLog')->record("导入sql文件");
                return $this->success("数据导入成功");
            } else {
                // 上传失败获取错误信息
                return $file->getError();
            }
        }
    }
}