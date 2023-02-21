<?php

namespace laocc\douyin;


class Refund extends Base
{

    public function notify(array $post): array
    {
        /**
         * {"err_no":0,"err_tips":"success","refundInfo":
         * {"refund_no":"7202226296817125691","refund_amount":1,"refund_status":"SUCCESS","refunded_at":1676899286,"is_all_settled":true,"cp_extra":"","msg":""}}
         *
         */
        $state = ($post['refund_status'] ?? $post['status']);
        $params = [];
        $params['success'] = $state === 'SUCCESS';
        $params['waybill'] = $post['refund_no'];
        $params['number'] = $post['cp_refundno'];
        $params['time'] = ($post['refunded_at']);
        $params['state'] = strtolower(substr($state, -20));
        $params['amount'] = intval($post['refund_amount']);
        return $params;
    }

    public function query(array $params)
    {
        $post = [];
        $post['app_id'] = $this->appID;//小程序 AppID
        $post['out_refund_no'] = $params['number'];//商户订单号
        $resp = $this->request('/api/apps/ecpay/v1/query_refund', $post);
        if (is_string($resp)) return $resp;

        return [
            'success' => ($resp['refundInfo']['refund_status'] === 'SUCCESS'),
            'state' => $resp['refundInfo']['refund_status'],
            'amount' => $resp['refundInfo']['refund_amount'],
            'time' => $resp['refundInfo']['refunded_at'],
            'waybill' => $resp['refundInfo']['refund_no'],
            'number' => $params['number'],
        ];
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
            'number' => $params['number'],
            'amount' => $params['amount'],
        ];
    }

}