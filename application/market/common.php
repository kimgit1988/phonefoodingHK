<?php
	use think\Db;
	function uploadPic($file,$path){
        $return = array();
		if($file){
			// 读取根路径配置
			$root_path = config('Rootpath');
			// 保持文件名字TP自动添加名字
			$info = $file->move(ROOT_PATH.'public'.DS.$path);
            if ($info) {
                $return['code'] = 1;
                // 获取文件路径及名称返回
                $return['msg'] = $root_path.'/'.$path.'/'.date("Ymd")."/".$info->getFilename();
                return $return;
            } else {
                // 上传失败获取错误信息
                $return['code'] = 0;
                $return['msg']  = $this->getError();
                return $return;
            }
		}else{
			$return['code'] = 0;
			$return['msg']  = '未找到图片';
			return $return;
		}
	}
	
	function curl($url,$data=null){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if(!empty($data)){
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl,CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        if (curl_errno($curl)) {
            return false;
        }else{
            return $output;
        	curl_close($curl);
        }
    }
?>