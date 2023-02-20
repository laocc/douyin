<?php

namespace laocc\douyin\pay;

use laocc\douyin\DyBase;

class Refund extends DyBase
{

    public function query(array $params)
    {

    }

    /**
     * @param array $params
     * @return array|string
     */
    public function send(array $params)
    {
        $post = [];
        $post['app_id'] = $this->appID;//小程序 AppID
        $post['out_order_no'] = $params['number'];//商户订单号
        $post['out_refund_no'] = $params['refund'];//商户退款订单号
        $post['refund_amount'] = $params['amount'];//
        $post['reason'] = $params['reason'];//
        $post['notify_url'] = $params['notify'];//
        $resp = $this->request('/api/apps/ecpay/v1/create_refund', $post);
        if (is_string($resp)) return $resp;

        return [
            'waybill' => $resp['refund_no'],
            'number' => $order['number'],
            'amount' => $order['amount'],
        ];
    }

}