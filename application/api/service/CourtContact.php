<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 20/2/2019
 * Time: 11:17 AM
 */

namespace app\api\service;


use think\Db;

class CourtContact
{
    public static function contactFoodList($contactNo,$type=1){
        // 规格列表
        $speclist = array();
        // 現在時間點
        $time = date('H:i:s');

        $goodsSpecs = Db::name('GoodsSpec')->where('gs_disable',1)->where('contactNumber',$contactNo)->select();

        $specs = Db::name('spec')->where('isDelete',0)->where('contactNumber',$contactNo)->select();

        foreach($specs as $spec){
            $speclist[$spec['id']] = $spec;
        }

        $goodSpecList = [];
        $mealWithFoods = [];
        $categoryWithFoods = [];

        foreach($goodsSpecs as $goodsSpec){

            if(isset($goodSpecList[$goodsSpec['gs_good_id']])){

                $parentIds = array_flip(array_column($goodSpecList[$goodsSpec['gs_good_id']],'id'));

                if(array_key_exists($goodsSpec['gs_spec_pid'],$parentIds)){

                    $goodSpecList[$goodsSpec['gs_good_id']][$parentIds[$goodsSpec['gs_spec_pid']]]['_child'][]=[
                        'id'=>$goodsSpec['gs_spec_id'],
                        'price'=>$goodsSpec['gs_price'],
                        'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                        'is_repeat'=>$goodsSpec['is_repeat'],
                        'is_default'=>$goodsSpec['is_default'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                    ];

                }else{
                    if(!isset($speclist[$goodsSpec['gs_spec_pid']])){
                        continue;
                    }
                    $goodSpecList[$goodsSpec['gs_good_id']][] = [
                        'id'=>$goodsSpec['gs_spec_pid'],
                        'fid'=>$goodsSpec['gs_good_id'],
                        'name'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order'=>$goodsSpec['gs_spec_order'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_disable'],
                        '_child'=>[
                            [
                                'id'=>$goodsSpec['gs_spec_id'],
                                'price'=>$goodsSpec['gs_price'],
                                'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat'=>$goodsSpec['is_repeat'],
                                'is_default'=>$goodsSpec['is_default'],
                                'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                            ]
                        ]
                    ];
                }
            }else{
                $goodSpecList[$goodsSpec['gs_good_id']] = [
                    [
                        'id'=>$goodsSpec['gs_spec_pid'],
                        'fid'=>$goodsSpec['gs_good_id'],
                        'name'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min'=>$speclist[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max'=>$speclist[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order'=>$goodsSpec['gs_spec_order'],
                        'spec_enable'=>$speclist[$goodsSpec['gs_spec_pid']]['spec_disable'],
                        '_child'=>[
                            [
                                'id'=>$goodsSpec['gs_spec_id'],
                                'price'=>$goodsSpec['gs_price'],
                                'name'=>$speclist[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat'=>$goodsSpec['is_repeat'],
                                'is_default'=>$goodsSpec['is_default'],
                                'spec_enable'=>$speclist[$goodsSpec['gs_spec_id']]['spec_disable']
                            ]
                        ]
                    ]
                ];
            }
        }

        $foodList = Db::name('Goods')
            ->field('id,name,payType,payUnit,number,categoryId,categoryName,salePrice,remark,detail,imgUrl,thumbnailUrl,sort')
            ->where('contactNumber',$contactNo)
            ->where('disable',1)
            ->where('isDelete',0)
            ->select();

        /*
        //这里的type为2代表美食广场下的商家
        if($type==2){
            //需要在图片前加域名
            foreach ($foodList as $key => $food) {
                $picArr = array();
                $foodList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['imgUrl'];
                $foodList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['thumbnailUrl'];
                preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $food['detail'], $picArr);
                $src = $picArr[1];
                foreach($src as $k => $v){
                    // 不带http和https,默认为本地图片 拼接域名
                    if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                        $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                        $foodList[$key]['detail'] = str_replace($v,$sv,$foodList[$key]['detail']);
                    }
                }
            }
        }*/

        // 分类
        $categoryWithFood = array();

        $categoryList = Db::name('Category')->field('id,name,startTime,endTime')->where('typeNumber','trade')->where('contactNumber',$contactNo)->where('isDelete',0)->select();

        foreach ($categoryList as $key => $categoryOne) {
            $categoryWithFood['category'.$categoryOne['id']] = ['categoryId' => $categoryOne['id'], 'categoryName' => $categoryOne['name'],'ismeal'=>0];
        }

        foreach ($foodList as $key => $food) {
            $foodList[$key]['salePrice'] = strval($foodList[$key]['salePrice']);
            if(isset($goodSpecList[$food['id']])){
                $food['_spec'] = $goodSpecList[$food['id']];
                $foodList[$key]['_spec'] = $goodSpecList[$food['id']];
            }else{
                $food['_spec'] = [];
                $foodList[$key]['_spec'] = [];
            }
            $idkey = 'category'.$food['categoryId'];
            if(isset($categoryWithFood[$idkey])){

                $categoryWithFood[$idkey]['_food'][] = $food;
            }
            /****这里的type为2代表美食广场下的商家****/
            if($type==2){
                $picArr = array();
                $foodList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['imgUrl'];
                $foodList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$food['thumbnailUrl'];
                preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $food['detail'], $picArr);
                $src = $picArr[1];
                foreach($src as $k => $v){
                    // 不带http和https,默认为本地图片 拼接域名
                    if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                        $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                        $foodList[$key]['detail'] = str_replace($v,$sv,$foodList[$key]['detail']);
                    }
                }
            }
        }

        $mealList = Db::name('SetMeal')
            ->field('id,name as mealName,totlePrice as price,categoryId,imgUrl,thumbnailUrl,remark,detail')
            ->where('contactNumber',$contactNo)
            ->where('status',1)
            ->where('isDelete',0)
            ->select();

        $mealWithFood = array();
        $mealCategory = array();
        $mealInfoList = array();

        if(!empty($mealList)){

            foreach($mealList as $key => $mealSingle){
                $mealWithFood[$mealSingle['id']] = $mealSingle;
                $mealWithFood[$mealSingle['id']]['ismeal'] = 1;
                $mealWithFood[$mealSingle['id']]['_category'] = array();
            }

            if($type==2){
                //需要在图片前加域名
                foreach ($mealList as $key => $value) {
                    $picArr = array();
                    $mealList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$value['imgUrl'];
                    $mealList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$value['thumbnailUrl'];
                    preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $value['detail'], $picArr);
                    $src = $picArr[1];
                    foreach($src as $k => $v){
                        // 不带http和https,默认为本地图片 拼接域名
                        if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                            $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                            $mealList[$key]['detail'] = str_replace($v,$sv,$mealList[$key]['detail']);
                        }
                    }
                }
            }

            $mealIdList = array_column((array)$mealList,'id');

            $mealCategory = Db::name('SetMealCategory')
                ->field('id,mid,name as mealCotegoryName,categoryMaxNumber,goodsMaxNumber,sort')
                ->where('mid','in',$mealIdList)
                ->where('isDelete',0)
                ->order('sort asc,id desc')
                ->select();

            foreach($mealCategory as $key => $mealCate){

                $mealWithFood[$mealCate['mid']]['_category'][$mealCate['id']] = $mealCate;
            }
            /*后面mos前缀省略，set_meal_info表关系mid->set_meal.id,cid->set_meal_category,gid->goods.id*/
            $mealInfoList = Db::name('SetMealInfo')
                ->alias('s')
                ->join('mos_goods g','s.gid = g.id','left')
                ->field('g.id,s.mid,s.cid,s.gid,g.name,g.payType,g.payUnit,g.number,g.categoryId,g.categoryName,g.salePrice,g.remark,g.detail,g.imgUrl,g.thumbnailUrl,g.sort')
                ->where('s.mid','in',$mealIdList)
                ->where('s.isDelete',0)
                ->where('g.isDelete',0)
                ->order('s.sort asc,s.id desc')
                ->select();

            foreach($mealInfoList as $key => $mealInfo){

                if ($type==2) {

                    $pictureList = array();
                    $mealInfoList[$key]['imgUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$mealInfo['imgUrl'];
                    $mealInfoList[$key]['thumbnailUrl'] = 'http://'.$_SERVER['HTTP_HOST'].$mealInfo['thumbnailUrl'];
                    preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $mealInfo['detail'], $pictureList);
                    $src = $pictureList[1];
                    foreach($src as $k => $v){
                        // 不带http和https,默认为本地图片 拼接域名
                        if(!strstr($v, 'http://')&&!strstr($v, 'https://')){
                            $sv = 'http://'.$_SERVER['HTTP_HOST'].$v;
                            $mealInfoList[$key]['detail'] = str_replace($v,$sv,$mealInfoList[$key]['detail']);
                        }
                    }
                }
                /*
                 * 单个mealInfo就是一个菜品信息，因为套餐的菜品都是在原来的菜品里面添加的，
                 * 所以如果这个菜品在规格表里有关联的话，就在这个菜品中把规格添加进来；
                */
                if(!empty($goodSpecList[$mealInfo['gid']])){
                    $mealInfo['_spec'] = $goodSpecList[$mealInfo['gid']];
                }

                //避免分類被刪除出錯
                if(isset($mealWithFood[$mealInfo['mid']]['_category'][$mealInfo['cid']])){
                    /*把已经添加规格的mealInfo添加到完整的$mealWithFood中*/
                    $mealWithFood[$mealInfo['mid']]['_category'][$mealInfo['cid']]['_food'][] = $mealInfo;
                }
            }

        }

        //过滤_category的键名和套餐food中的mid，cid，gid
        foreach ($mealWithFood as &$mealfood) {
            $mealfood['_category'] = array_values($mealfood['_category']);
                foreach($mealfood['_category'] as &$mealcategoryfood){
                    foreach($mealcategoryfood['_food'] as &$mealcategoryfooditem){
                        unset($mealcategoryfooditem['mid']);
                        unset($mealcategoryfooditem['cid']);
                        unset($mealcategoryfooditem['gid']);
                    }
                }
        }
        $categoryWithFoodList = $categoryWithFood;
        //组合套餐分类到菜品分类
        foreach ($categoryWithFoodList as $categoryfood) {
            foreach ($mealWithFood as $mealfooditem) {
                if(empty($mealfooditem['_category'])){continue;}
                if($mealfooditem['categoryId']==$categoryfood['categoryId']){
                    $categoryfood['ismeal'] = 1;
                    $categoryfood['meallist'][] = $mealfooditem;
                }
            }
            //过滤空的分类
            if(isset($categoryfood['_food'])||isset($categoryfood['meallist'])){
                $categoryWithFoods[] = $categoryfood;
            }

        }

        // 接口不需要輸出這兩個樹狀
        // if($type==1){
        //     $return['meal'] = $mealWithFood;
        //     $return['category'] = $categoryWithFood;
        // }
        $return['categorylist'] = $categoryWithFoods;
        // $return['foodlist'] = $foodList;
        // $return['categorylist'] = $categoryList;
        // $return['meallist'] = $mealList;
        // $return['mealinfo'] = $mealInfoList;
        // $return['mealcategory'] = $mealCategory;
        return $return;
    }

    public static function foodFromOrder($originFood,$contactNo){

        $foodList = array_filter($originFood,function($origin){
            return $origin['type']==1;
        });

        if(empty($foodList)){
            return false;
        }

        $foodIds = array_column($foodList,'id');
        $specCountsList = array_column($foodList,'specCounts','id');
        $specIds = [];
        array_walk($specCountsList,function($value,$key) use(&$specIds) {
            if($value!==''){
                $temps = explode(',',$value);
                foreach($temps as $temp){
                    list($a,$b) = explode('_',$temp);
                    !in_array($a,$specIds)&&$specIds[] = $a;
                }
            }
        });

        $goodsList = DB::name('goods')->field('id,name,payType,payUnit,number,thumbnailUrl,salePrice,remark,printerId,departmentId')
            ->where('id','in',$foodIds)
            ->where('contactNumber',$contactNo)
            ->where('disable',1)
            ->where('isDelete',0)
            ->select();

        $goodsSpec = array();
        if(!empty($specIds)){
            $goodsSpec = DB::name('GoodsSpec')
                ->alias('g')
                ->field('g.*,s.spec_name,s.spec_disable')
                ->join('mos_spec s','g.gs_spec_id = s.id','left')
                ->where('g.gs_good_id','in',$foodIds)
                ->where('g.gs_spec_id','in',$specIds)
                ->where('g.gs_disable',1)
                ->where('g.isDelete',0)
                ->where('s.isDelete',0)
                ->select();
        }
        $goodsSpecPrice = [];
        array_walk($goodsSpec,function($goodspec) use(&$goodsSpecPrice) {
            $goodsSpecPrice[$goodspec['gs_good_id'].'_'.$goodspec['gs_spec_id']] = $goodspec;
        });

        $foodList = array_combine($foodIds,$foodList);
        $goodsList = array_map(function($good) use($foodList,$goodsSpecPrice){

            $good['counter'] = $foodList[$good['id']]['counter'];
            $good['type'] = $foodList[$good['id']]['type'];

            $specCountArr = explode(',',$foodList[$good['id']]['specCounts']);
            if(!empty($specCountArr)){
                $tempName = '';
                foreach($specCountArr as $specCountStr){
                    if($specCountStr !== ''){
                        $temp = explode('_',$specCountStr);
                        $good['salePrice']+= $goodsSpecPrice[$good['id'].'_'.$temp[0]]['gs_price']*$temp[1];
                        $tempName.= $goodsSpecPrice[$good['id'].'_'.$temp[0]]['spec_name'].($temp[1]>1?'*'.$temp[1].',':',');
                    }
                }
                if($tempName!==''){
                    $good['name'] .= '['.trim($tempName,',').']';
                }
            }
            return $good;
        },$goodsList);

        return $goodsList;
    }
}