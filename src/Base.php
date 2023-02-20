<?php

namespace laocc\douyin;

use esp\core\Library;
use esp\http\Http;

abstract class Base extends Library
{
    private string $api = 'https://developer.toutiao.com';
    protected string $mchID;
    protected string $appID;
    protected string $token;
    protected string $salt;

    public function _init(array $conf)
    {
        $this->mchID = $conf['mchID'];
        $this->appID = $conf['appID'];
        $this->token = $conf['token'];
        $this->salt = $conf['salt'];
    }

    public function notifySignCheck(array $post): bool
    {
        $rList = [];
        $rList[] = $this->token;
        $rList[] = $post['timestamp'];
        $rList[] = $post['nonce'];
        $rList[] = $post['msg'];
        sort($rList, SORT_STRING);
        $sign = sha1(implode('', $rList));
        return $sign === $post['msg_signature'];
    }

    public function request(string $api, array $post, array $option = [])
    {
        if (!isset($option['type'])) $option['type'] = 'post';
        if (!isset($option['encode'])) $option['encode'] = 'json';
        if (!isset($option['decode'])) $option['decode'] = 'json';
        $option['agent'] = 'laocc/esp HttpClient/cURL';
        $option['header'] = true;
        $option['allow'] = [200, 204];
        $option['headers']['Accept'] = "application/json";
        $option['headers']['Accept-Language'] = 'zh-CN';
        if ($option['type'] === 'upload') {
            $option['type'] = 'post';
        } else {
            $option['headers']['Content-Type'] = "application/json";
        }

        $post['sign'] = $this->pay_sign($post);

        $http = new Http($option);
        $request = $http->data($post)->request($this->api . $api);
        $this->debug($request);
        if ($err = $request->error()) return "Error:{$err}";

        //只要求返回对方响应状态码
        if ($option['returnCode'] ?? 0) return $request->info('code');

//        $header = $request->header();
//        $json = $request->html();
        $data = $request->data();
        if ($data['err_no'] > 0) return "{$data['err_no']}:{$data['err_tips']}";

        return $data;
    }

    private function pay_sign(array $map): string
    {
        $rList = [];
        foreach ($map as $k => $v) {
            if (in_array($k, ['other_settle_params', 'app_id', 'sign', 'thirdparty_id'])) continue;
            $value = trim(strval($v));
            if (is_array($v)) $value = $this->arrayToStr($v);
            $len = strlen($value);
            if ($len > 1 && substr($value, 0, 1) == '"' && substr($value, $len, $len - 1) == '"') $value = substr($value, 1, $len - 1);
            $value = trim($value);
            if ($value == '' || $value == "null") continue;
            $rList[] = $value;
        }
        $rList[] = $this->salt;
        sort($rList, SORT_STRING);
        return md5(implode('&', $rList));
    }

    private function arrayToStr($map): string
    {
        $isMap = $this->isArrMap($map);
        $result = $isMap ? 'map[' : "";
        $keyArr = array_keys($map);
        if ($isMap) sort($keyArr);

        $paramsArr = array();
        foreach ($keyArr as $k) {
            $v = $map[$k];
            if ($isMap) {
                if (is_array($v)) {
                    $paramsArr[] = sprintf("%s:%s", $k, $this->arrayToStr($v));
                } else {
                    $paramsArr[] = sprintf("%s:%s", $k, trim(strval($v)));
                }
            } else {
                if (is_array($v)) {
                    $paramsArr[] = $this->arrayToStr($v);
                } else {
                    $paramsArr[] = trim(strval($v));
                }
            }
        }

//        $result = sprintf("%s%s", $result, join(' ', $paramsArr));
        return sprintf("[%s%s]", $result, join(' ', $paramsArr));
    }

    private function isArrMap($map): bool
    {
        foreach ($map as $k => $v) {
            if (is_string($k)) return true;
        }
        return false;
    }

}