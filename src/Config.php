<?php

namespace wllxxxy\wllsdk;


class Config
{

    /** 系统配置
     * @var array
     */
    private static $config = [];
    /** 用户的登录令牌
     * @var string
     */
    private static $memberLoginToken = '';
    /**
     * @var string
     */
    private static $authorizationFailureEvent = '';

    /**
     * 设置系统配置
     * @param $key
     * @return mixed|string
     */
    public static function getConfig($key)
    {
        return static::$config[$key] ?? '';
    }

    /**
     * 设置系统配置
     * @param $config
     */
    public static function setConfig($config)
    {
        static::$config = $config;
    }

    /**
     * 设置用户token
     * @param $memberLoginToken
     */
    public static function setMemberLoginToken($memberLoginToken)
    {
        static::$memberLoginToken = $memberLoginToken;
    }

    /**
     * 获取用户的token
     * @return string
     */
    public static function getMemberLoginToken()
    {
        return static::$memberLoginToken;
    }

    /**
     * 设置用户授权失败时的触发的方法
     * @param $event
     * @return string
     */
    public static function setAuthorizationFailureEvent($event)
    {
        self::$authorizationFailureEvent = $event;
    }

    /**
     * 吊起hook代码
     */
    public static function callAuthorizationFailureEvent()
    {
        if (is_callable(static::$authorizationFailureEvent)) {
            call_user_func_array(static::$authorizationFailureEvent, []);

        }
    }

}
