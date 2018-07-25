<?php
namespace org;

use org\SmsApi;
use org\Lock;
use org\Random;

/**
 * 验证码锁
 */
class Sms
{
    private $mobile;
    private $lock;
    private $ipLock;
    private $result;
    private $ip;

    /**
     * 验证码锁
     *
     * @param integer $ttl
     */
    public function __construct($mobile, $ttl = 120)
    {
        $this->ip = request()->ip(0, true);
        $this->mobile = $mobile;
        $this->lock = new Lock('verify_code_'.$mobile,$ttl);
        $this->ipLock = new Lock('sms_ip_'.$this->ip, 86400);
    }

    /**
     * 是否能发送验证码
     *
     * @return boolean
     */
    public function canSend()
    {
        if ($this->lock->isLocked()) {
            return false;
        }
        if (!$this->ipLock->isLocked()) {
            $this->ipLock->lock(0);
        }
        if ($this->ipLock->inc() > 100) {
            trace($this->ip.'频繁发送短信');
            return false;
        }
        return true;
    }

    /**
     * 发送验证码
     *
     * @param string $code
     * @return boolean|string 返回true表示发送成功，其他表示错误原因
     */
    public function send($code = null)
    {
        if ($code === null) {
            $code = $this->getCode();
        }
        $sms = new SmsApi();
        $this->result = $sms->sendSms($this->mobile, '您的验证码为：'.$code);
        if ($this->result === '1') {
            //记住验证码与返回
            $this->setCode($code);
            return true;
        }
        return $this->result;
    }

    /**
     * 短信发送结果
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result === '1' ? '验证码发送成功，请注意查收' : $this->result;
    }

    /**
     * 获取一个验证码
     *
     * @param integer $length
     * @return string
     */
    public function getCode($length = 4)
    {
        return Random::numeric($length);
    }

    /**
     * 保存验证码
     *
     * @param string $code
     * @return void
     */
    public function setCode($code)
    {
        $this->lock->lock(my_md5($code));
    }

    /**
     * 校验验证码
     *
     * @param string $code
     * @return void
     */
    public function verifyCode($captcha)
    {
        $code = $this->lock->getLockValue();
        if ($code != my_md5($captcha)) {
            return false;
        }
        $this->lock->release();
        return true;
    }
}
