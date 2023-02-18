<?php

namespace laocc\douyin\library;

interface PayFace
{
    public function app(array $params);

    public function jsapi(array $params);

    public function h5(array $params);

    public function query(array $params);

    public function refund(array $params);
}