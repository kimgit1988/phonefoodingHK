<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/4/9
 * Time: 10:03
 */

namespace app\service;


use think\Db;

class Foodlist
{
    function getFoodAndMeal($contactNo, $type = 1)
    {
        $categoryFoods = $this->categoryWithFoods($contactNo, $type);
        $categoryMeals = $this->categoryWithMeals($contactNo, $type);
        return ['meal'=>$categoryMeals,'category'=>$categoryFoods];
    }

    public function categoryWithMeals($contactNo, $type = 1){
        /**
         * @var $mealList array
         */
        $mealList = DB::name('SetMeal')
            ->field('id,name,name_en,name_other,totlePrice as price,categoryId,thumbnailUrl')
            ->where('contactNumber',$contactNo)
            ->where('status',1)
            ->where('isDelete',0)
            ->select();

        //引用设置套餐多语言
        foreach($mealList as &$mlist){
            $mlist['name'] = __($mlist['name'],$mlist['name_en'],$mlist['name_other']);
        }
        unset($mlist);
        $existCategoryIds = array_column($mealList,'categoryId');
        $categoryList = (array)$this->getCategoryList($contactNo, $type,$existCategoryIds);

        //引用设置套餐分类多语言台
        foreach($categoryList as &$clist){
            $clist['categoryName'] = __($clist['categoryName'],$clist['categoryName_en'],$clist['categoryName_other']);
        }
        unset($clist);
        $categoryListIds = array_column((array)$categoryList,'categoryId');
        $categoryDict = array_combine($categoryListIds,$categoryList);

        $mealListIds = array_column($mealList,'id');
        $mealDict = array_combine($mealListIds,$mealList);

        $mealListIds = empty($mealListIds)?'()':$mealListIds;

        $mealCategoryList = DB::name('SetMealCategory')
            ->field('id,mid,name,name_en,name_other,categoryMaxNumber,goodsMaxNumber,sort')
            ->where('mid','in',$mealListIds)
            ->where('isDelete',0)
            ->order('sort asc,id desc')
            ->select();

        $mealCategoryIds = array_column((array)$mealCategoryList,'id');
        $mealCategoryDict = array_combine($mealCategoryIds,(array)$mealCategoryList);

        $mealInfoList = DB::name('SetMealInfo')
            ->alias('s')
            ->join('mos_goods g','s.gid = g.id','left')
            ->field('s.id,s.mid,s.cid,s.gid,g.salePrice,g.name,g.payType,g.payUnit,g.number,g.remark,g.detail,g.imgUrl,g.thumbnailUrl,g.sort')
            ->where('s.mid','in',$mealListIds)
            ->where('s.isDelete',0)
            ->where('g.isDelete',0)
            ->order('s.sort asc,s.id desc')
            ->select();

        $this->addDomainToImageSrc($mealInfoList,$type);
        $categoryWithMeals = $this->organizeCategoryMealInfo($categoryDict,$mealDict,$mealCategoryDict,$mealInfoList);
        return $categoryWithMeals;
    }

    //整合meal套餐数据，合并为分类，套餐类，套餐分类，套餐商品；
    public function organizeCategoryMealInfo($categoryDict,$mealDict,$mealCategoryDict,$mealInfoList){

        if(empty($mealInfoList)){
            return [];
        }

        foreach($mealInfoList as $mealInfo){
            $meal = $mealDict[$mealInfo['mid']];
            $meal['_category'][$mealInfo['cid']] = !empty($mealCategoryDict[$mealInfo['cid']])?$mealCategoryDict[$mealInfo['cid']]:[];
            $meal['_category'][$mealInfo['cid']]['_food'][] = $mealInfo;
            $categoryDict[$meal['categoryId']]['_meal'][$meal['id']] = $meal;
        }
        return $categoryDict;
    }



    public function categoryWithFoods($contactNo, $type = 1)
    {
        /**
         * @var $goodList array;
         * @var $commenSpecs array;
         */
        $commenSpecs = DB::name('spec')
            ->where('isDelete', 0)
            ->where('contactNumber', $contactNo)
            ->select();

        $commenSpecIds = array_column($commenSpecs, 'id');
        $specsDict = array_combine($commenSpecIds, $commenSpecs);

        $goodsSpecs = DB::name('GoodsSpec')
            ->where('isDelete', 0)
            ->where('gs_disable', 1)
            ->where('contactNumber', $contactNo)
            ->select();

        $goodSpecList = $this->organizeGoodSpces($goodsSpecs, $specsDict);

        $goodList = DB::name('Goods')
            ->field('id,name,payType,payUnit,number,categoryId,categoryName,salePrice,remark,detail,imgUrl,thumbnailUrl,sort')
            ->where('contactNumber', $contactNo)
            ->where('disable', 1)
            ->where('isDelete', 0)
            ->select();

        $existCategoryIds = array_column($goodList,'categoryId');

        $this->addDomainToImageSrc($goodList, $type);
        $categoryList = (array)$this->getCategoryList($contactNo,$type,$existCategoryIds);
        $categoryListIds = array_column((array)$categoryList,'categoryId');
        $categoryDict = array_combine($categoryListIds,$categoryList);
        $categoryFoodList = $this->organizeCagegoryGoodSpec($categoryDict,$goodList,$goodSpecList);
        return $categoryFoodList;
    }

    public function organizeCagegoryGoodSpec($categoryDict,$goodList,$goodSpecList){

        if(empty($goodList)){
            return [];
        }

        foreach($goodList as $good){

            if(isset($goodSpecList[$good['id']])){
                $good['_spec'] = $goodSpecList[$good['id']];
            }else{
                $good['_spec'] = [];
            }

            if(isset($categoryDict[$good['categoryId']])){
                $categoryDict[$good['categoryId']]['_food'][] = $good;
            }
        }
        return $categoryDict;
    }

    public function getCategoryList($contactNo,$type=1,$existCategoryIds){
        $time = date('H:i:s');

        $existCategoryIds = empty($existCategoryIds)?'()':$existCategoryIds;

        $query = DB::name('Category')
            ->field('id as categoryId,name as categoryName,name_en as categoryName_en,name_other as categoryName_other,startTime,endTime')
            ->where('typeNumber','trade')
            ->where('contactNumber',$contactNo)
            ->where('isDelete',0)
            ->where('id','in',$existCategoryIds);

        if($type==1){
            $query->where('("'.$time.'" BETWEEN startTime AND endTime) OR startTime is NULL OR endTime iS NULL');
        }
        return $query->select();
    }

    /**
     * 给图片url添加domain路径
     * @param $goodList
     * @param $type
     */
    protected function addDomainToImageSrc(&$goodList, $type)
    {
        if ($type == 2) {
            foreach ($goodList as $key => $food) {
                $picArr = array();
                $goodList[$key]['imgUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . $food['imgUrl'];
                $goodList[$key]['thumbnailUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . $food['thumbnailUrl'];
                preg_match_all('/<img[^>]*src\s?=\s?[\'|"]([^\'|"]*)[\'|"]/is', $food['detail'], $picArr);
                $src = $picArr[1];
                foreach ($src as $k => $v) {
                    // 不带http和https,默认为本地图片 拼接域名
                    if (!strstr($v, 'http://') && !strstr($v, 'https://')) {
                        $sv = 'http://' . $_SERVER['HTTP_HOST'] . $v;
                        $foodList[$key]['detail'] = str_replace($v, $sv, $goodList[$key]['detail']);
                    }
                }
            }
        }
    }

    /**
     * 把商品属性表组装成三维数组，键值分别为 good_id,spec_pid,spec_id;
     * @param $goodsSpecs
     * @param $specsDict
     * @return array
     */
    protected function organizeGoodSpces($goodsSpecs, $specsDict)
    {
        $goodSpecList = [];
        foreach ($goodsSpecs as $goodsSpec) {
            if (isset($goodSpecList[$goodsSpec['gs_good_id']])) {
                if (isset($goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']])) {
                    $goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']]['_child'][$goodsSpec['gs_spec_id']] = [
                        'id' => $goodsSpec['gs_spec_id'],
                        'price' => $goodsSpec['gs_price'],
                        'name' => $specsDict[$goodsSpec['gs_spec_id']]['spec_name'],
                        'is_repeat' => $goodsSpec['is_repeat'],
                        'is_default' => $goodsSpec['is_default']
                    ];
                } else {
                    $goodSpecList[$goodsSpec['gs_good_id']][$goodsSpec['gs_spec_pid']] = [
                        'id' => $goodsSpec['gs_spec_pid'],
                        'fid' => $goodsSpec['gs_good_id'],
                        'name' => $specsDict[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min' => $specsDict[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max' => $specsDict[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order' => $goodsSpec['gs_spec_order'],
                        '_child' => [
                            $goodsSpec['gs_spec_id'] => [
                                'id' => $goodsSpec['gs_spec_id'],
                                'price' => $goodsSpec['gs_price'],
                                'name' => $specsDict[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat' => $goodsSpec['is_repeat'],
                                'is_default' => $goodsSpec['is_default']
                            ]
                        ]
                    ];
                }
            } else {
                $goodSpecList[$goodsSpec['gs_good_id']] = [
                    $goodsSpec['gs_spec_pid'] => [
                        'id' => $goodsSpec['gs_spec_pid'],
                        'fid' => $goodsSpec['gs_good_id'],
                        'name' => $specsDict[$goodsSpec['gs_spec_pid']]['spec_name'],
                        'min' => $specsDict[$goodsSpec['gs_spec_pid']]['minselect'],
                        'max' => $specsDict[$goodsSpec['gs_spec_pid']]['maxselect'],
                        'spec_order' => $goodsSpec['gs_spec_order'],
                        '_child' => [
                            $goodsSpec['gs_spec_id'] => [
                                'id' => $goodsSpec['gs_spec_id'],
                                'price' => $goodsSpec['gs_price'],
                                'name' => $specsDict[$goodsSpec['gs_spec_id']]['spec_name'],
                                'is_repeat' => $goodsSpec['is_repeat'],
                                'is_default' => $goodsSpec['is_default']
                            ]
                        ]
                    ]
                ];
            }
        }
        return $goodSpecList;
    }
}