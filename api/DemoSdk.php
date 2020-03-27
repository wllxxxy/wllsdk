<?php


namespace wllxxxy\wllsdk\api;

use wllxxxy\wllsdk\CommonSdk;

class DemoSdk extends CommonSdk
{

    public function demo($params)
    {
        return $this->send($params, __METHOD__);
    }

}
