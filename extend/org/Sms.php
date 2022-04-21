<?php

namespace org;

use org\Lock;
use org\SmsTool;
use think\helper\Str;

/**
 * 验证码锁
 */
class Sms
{
    private $mobile;
    private $lock;
    private $ipLock;
    private $ip;
    private $scene = 'reg';
    private $key = 'ZvzO!39NC3ME';

    /**
     * 验证码锁
     *
     * @param string $mobile
     * @param integer $ttl
     */
    public function __construct(string $mobile, int $ttl = 120)
    {
        $this->ip = request()->ip(0, true);
        $this->mobile = $mobile;
        $this->lock = new Lock('verify_code_' . $mobile, $ttl);
        $this->ipLock = new Lock('sms_ip_' . $this->ip, 86400);
    }

    /**
     * 是否能发送验证码
     *
     * @return boolean
     */
    public function canSend(): bool
    {
        if ($this->lock->isLocked()) {
            return false;
        }
        if (!$this->ipLock->isLocked()) {
            $this->ipLock->lock(0);
        }
        if ($this->ipLock->inc() > 100) {
            trace($this->ip . '频繁发送短信');
            return false;
        }
        return true;
    }

    /**
     * 设置短信场景
     *
     * @param string $scene
     * @return Sms
     */
    public function scene(string $scene): Sms
    {
        $this->scene = $scene;
        return $this;
    }

    /**
     * 发送验证码
     *
     * @param string $code
     * @return boolean 返回true表示发送成功
     */
    public function send(string $code = ''): bool
    {
        if (empty($code)) {
            $code = $this->getCode();
        }
        $tool = new SmsTool();
        $result = $tool->send($this->mobile, $this->getSmsContent($code));
        if ($result === true) {
            //记住验证码与返回
            $this->setCode($code);
        }
        return $result;
    }

    /**
     * 获取一个验证码
     *
     * @param integer $length
     * @return string
     */
    public function getCode($length = 4)
    {
        return Str::random($length, 1);
    }

    /**
     * 保存验证码
     *
     * @param string $code
     * @return void
     */
    public function setCode($code)
    {
        $this->lock->lock(md5($this->key . $code));
    }

    /**
     * 校验验证码
     *
     * @param string $code
     * @return boolean
     */
    public function verifyCode($captcha): bool
    {
        $code = $this->lock->getLockValue();
        if ($code != md5($this->key . $captcha)) {
            return false;
        }
        $this->lock->release();
        return true;
    }

    /**
     * 获取短信内容
     *
     * @param string $code
     * @return string
     */
    private function getSmsContent(string $code): string
    {
        return $code;
    }
}
