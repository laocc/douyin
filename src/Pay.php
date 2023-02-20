<?php

namespace laocc\douyin;

use laocc\douyin\library\PayFace;

class Pay extends Base implements PayFace
{
    public function notify(array $post): array
    {
        $wName = [1 => 'weixin', 2 => 'alipay', 10 => 'douyin'];//支付渠道， 1-微信支付，2-支付宝支付，10-抖音支付

        $params = [];
        $params['success'] = ($post['status'] === 'SUCCESS');
        $params['number'] = $post['cp_orderno'];//本平台订单号
        $params['waybill'] = $post['order_id'];//抖音的订单号
        $params['platform'] = 'douyin.' . ($wName[$post['way']] ?? '');//支付渠道， 1-微信支付，2-支付宝支付，10-抖音支付
//        $params['waybill'] = $postData['channel_no'];//支付渠道侧单号(抖音平台请求下游渠道微信或支付宝时传入的单号)
//        $params['waybill'] = $postData['payment_order_no'];//支付渠道侧PC单号，支付页面可见(微信支付宝侧的订单号)
        $params['time'] = intval($post['paid_at']);
        $params['state'] = strtolower(substr($post['status'], -20));
        $params['amount'] = intval($post['total_amount']);
        return $params;
    }


    public function jsapi(array $params)
    {
        $post = [];
        $post['app_id'] = $this->appID;
        $post['out_order_no'] = $params['number'];
        $post['total_amount'] = $params['fee'];
        $post['subject'] = mb_substr($params['subject'], 0, 42);
        $post['body'] = $post['subject'];
        $post['valid_time'] = $params['ttl'] ?? 3600;//订单过期时间(秒)。最小5分钟，最大2天，小于5分钟会被置为5分钟，大于2天会被置为2天
        $post['cp_extra'] = $params['attach'];
        $post['notify_url'] = $params['notify'];
//        $post['thirdparty_id'] = $params[''];//第三方平台服务商 id，非服务商模式留空
        $post['disable_msg'] = 0;//是否屏蔽支付完成后推送用户抖音消息 1-屏蔽 0-非屏蔽，默认为0。
        $post['msg_page'] = $params['page'] ?? '/pages/index/index';//支付后跳转
        /**
         * 屏蔽指定支付方式，屏蔽多个支付方式，请使用逗号","分割，枚举值：
         * 屏蔽微信支付：LIMIT_WX
         * 屏蔽支付宝支付：LIMIT_ALI
         * 屏蔽抖音支付：LIMIT_DYZF
         */
        $post['limit_pay_way'] = $params['limit'] ?? '';
//        $post['expand_order_info'] = [];//订单拓展信息

        $data = $this->request('/api/apps/ecpay/v1/create_order', $post);
        if (is_string($data)) return $data;

        return $data['data'];
    }

    public function h5(array $params)
    {
    }

    public function app(array $params)
    {
    }

    public function query(array $params)
    {
        $post = [];
        $post['app_id'] = $this->appID;
        $post['out_order_no'] = $params['number'];
//        $post['thirdparty_id'] = $params[''];//第三方平台服务商 id，非服务商模式留空

        $data = $this->request('/api/apps/ecpay/v1/query_order', $post);
        if (is_string($data)) return $data;

        return [
            'waybill' => $data['order_id'],
            'number' => $data['out_order_no'],
            'way' => $data['payment_info']['way'],
            'state' => $data['payment_info']['order_status'],
            'amount' => $data['payment_info']['total_fee'],
            'time' => strtotime($data['payment_info']['pay_time']),
            'data' => $data['payment_info'],
        ];
    }

}