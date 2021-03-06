<?php
namespace App\Modules\Finance\Cashier;

use Illuminate\Support\Facades\Log;
use App\Modules\Finance\Repository\CommUserInfoRepository;
/**
 * 区代加盟
 */

 class K0140 extends K0000{

    /**
     * 分润比例设定
     */
    public $shareProfit = array(
        'direct'    => 40,//直推分润,
        'indirect'  => 6,//间接分润,
        'top'       => 4,//顶级分润,

        'P1201'     => 0,//VIP
        'P1301'     => 0, //总代理
        'P1311'     => 0, //合伙人
        'P1401'     => 0,//区代
        'P1501'     => 0,//市代
        'P1601'     => 0, //省代

        'P2101'     => 0, //招商经理
        'P2201'     => 0, //销售经理
        'P2301'     => 0, //市场总监
    );

    public $repository;
    public $code = "K0140";

    /**
     * 注入Repository
     */
    public function  __construct(CommUserInfoRepository $Repository){
         $this->repository = $Repository;
         //$this->getShareProfit($this->code);
    }


    
    /**
     * 
     */
    public function handle($markBookingOrder,$request)
    {
        Log::debug("Cashier::K0140.handle...区代加盟");
        $BookingOrder = [];
        $transAmount = $request['transAmount'];
        $userinfo = $request['userinfo'];
        $userId = $userinfo['user_id'];
        $teamRelation = $this->repository->getTeamRelation($userId);
        Log::debug(json_encode($teamRelation));
        //------------------------------三级分润---------------------------------------
        $UserInfoDepthList = $this->repository->getUserByTeam($userId);
        $UserInfo['depth'] = 0;
        $UserInfo['rangePercentage'] = $this->shareProfit['direct'];
        $UserInfo = $this->getDepthBookingOrder($UserInfoDepthList,$UserInfo,$transAmount,$markBookingOrder);
        if($UserInfo&&isset($UserInfo['BookingOrder'])){
            if(isset($UserInfo['BookingOrder']))
                $BookingOrder['3.1.1'] = $UserInfo['BookingOrder'];
            $UserInfo['rangePercentage'] = $this->shareProfit['indirect'];
            $UserInfo = $this->getDepthBookingOrder($UserInfoDepthList,$UserInfo,$transAmount,$markBookingOrder);
        }
        if($UserInfo){
            if(isset($UserInfo['BookingOrder']))
                $BookingOrder['3.1.2'] = $UserInfo['BookingOrder'];
            $UserInfo['rangePercentage'] = $this->shareProfit['top'];
            $UserInfo = $this->getDepthBookingOrder($UserInfoDepthList,$UserInfo,$transAmount,$markBookingOrder);
        }
        if($UserInfo){
            if(isset($UserInfo['BookingOrder']))
                $BookingOrder['3.1.3'] = $UserInfo['BookingOrder'];
            //$UserInfo = $this->getDepthBookingOrder($UserInfoDepthList,$UserInfo,$transAmount,$markBookingOrder);
        }
        Log::info("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
        //-----------------------------------------------------------------------------
        return $BookingOrder;
    }

     /**
      * 级差深度分润明细
      * @param $UserInfoDepthList
      * @param $UserInfo
      * @param $transAmount
      * @return null
      */
     protected function getDepthBookingOrder($UserInfoDepthList,$UserInfo,$transAmount,$markBookingOrder){

         $depth = $UserInfo['depth'];
         $rangePercentage = $UserInfo['rangePercentage'];//分润系数
         $UserInfo = $this->getNextLevelUserInfo($UserInfoDepthList,'P1401',$depth);//..
         Log::info("级差深度分润明细.3.1.".($depth+1)."级差收益:".json_encode($UserInfo));
         if($UserInfo){
             $param =[];
             $creditPercentage = $rangePercentage;
             if($creditPercentage>0){
                 $credit_amount = Money()->getRate($transAmount,$creditPercentage);
                 $param['credit_amount'] = $credit_amount;
                 $param['percentage'] = $creditPercentage;
                 $param['remark'] = '招商加盟业绩提成';
                 $param['process_id'] = $UserInfo['user_id'];
                 $param['batch_detail_id'] = '3.1.'.$depth;
                 $UserInfo['rangePercentage'] = $creditPercentage;
                 Log::info($param['remark']."::".json_encode($param));
                 $UserInfo['BookingOrder']= $this->getBranchBookingOrder($markBookingOrder,$param);
             }else{
                 Log::debug("下级高于自己的级别 -无法分润-  级差收益:".$creditPercentage);
                 $UserInfo['rangePercentage'] = $rangePercentage;
                 Log::debug("未来这里要刺激用户升级!!");
             }

         }
         return $UserInfo;
     }

     /**
      * 获取分润参数,通过数据字典设置
      */
     protected function getShareProfit($code){

        foreach($this->shareProfit as $k => $v ){
            $ret = getConfigure($code,$k);
            //Log::debug(json_encode($ret));
            $this->shareProfit[$k]=$ret['property2'];
        }
        Log::debug(json_encode($this->shareProfit));
     }


    /**
     * 获取指定级别合作商
     */
    protected function getLevelUserInfo($retUserInfo,$level)
    {
        foreach ($retUserInfo as $key => $userInfo ){
            $user_tariff_code = $userInfo['user_tariff_code'];
            if($user_tariff_code == $level){
                return $userInfo;
            }
        }
        return array('user_id'=>'0','user_tariff_code'=>$level);
    }
 }