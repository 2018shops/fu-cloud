<?php
/**
 * Created by PhpStorm.
 * User: wangjh
 * Date: 2018/1/25
 * Time: 08:49
 */

namespace App\Modules\Transaction\Middleware;

use App\Common\Contracts\Middleware;

use App\Modules\Finance\Repository\AcctUserTariffRateRepository;
use App\Modules\Transaction\Repository\CommUserInfoRepository;
use App\Modules\Transaction\Repository\TranTransOrderRepo;
use Closure;
use Illuminate\Support\Facades\Log;


class DetailMiddleware extends Middleware
{
    public $repo;
    public function __construct( TranTransOrderRepo $repo)
    {
        $this->repo = $repo;
    }

    public function handle($request, Closure $next)
    {
        Log::info('下单成功，明细流水参数设置');
        $code = $request['business_code'];

        $detailParams = array(
            'id' => $request['detailId'],
            'business_code' => $code,
            'trans_amt' => $request['trans_amt'],
            'trans_time' => date("Y-m-d H:i:s", $request['time']),
            'receive_amt' => $request['receive_amt']['receiveAmt'],
            'agent_id' => $request['userInfo']['agent_id'],// 用户表查询
            'merc_id' => $request['channelInfo']->id,//=============================

            'merc_name' => $request['channelInfo']->merc_name, // 商户名======================

            'merc_order_id' => $request['summaryId'],// 汇总流水ID
            'status' => '1',
            'receive_time' => $request['receive_time'],
            'cash_type' =>  $request['cash_type'],
            'channel_id' => $request['channelInfo']->channel_id, // 通道ID
            'channel_merc_id' => $request['channelInfo']->merc_id, // 通道商户编号
            'channel_rate' => $request['rateInfo']['rate'], // 通道费率

//            'outer_order_id'=>'170464402575392', // 第三方平台流水号
//            'pay_id'=>  '1462498182943716784',  //  平台代付流水号
//            'notify_status'=>'1',   //回调通知状态

            'acct_req_code' => config('interface.FINANCE.'.$code), //财务记账请求码===========

            'create_time' => date("Y-m-d H:i:s", $request['time']),
            'create_by' => 'system',
            'update_time' => date("Y-m-d H:i:s", $request['time']),
            'update_by' => 'system',
            'user_id' => $request['user_id'],
            'user_name' => '', //用户ID
            'type' => $request['type'], //10邀请码升级   20线下缴费 PMS发起升级
            'invite_code' => isset($request['invite_code']) ? $request['invite_code'] : ''  //邀请码
        );

        $request['detailParams'] = $detailParams;
        $this->repo->insert($detailParams);
        return $next($request);
    }
}