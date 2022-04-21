<?php
namespace org;

use org\Lock;
use think\Response;
use Gregwar\Captcha\CaptchaBuilder;

/**
 * 验证码锁
 */
class Captcha
{
    private $sid;
    private $lock;
    private $builder;

    /**
     * 验证码锁
     *
     * @param integer $ttl
     */
    public function __construct($sid, $ttl = 600)
    {
        $this->sid = $sid;
        $this->lock = new Lock('verify_captcha_'.$sid,$ttl);
        $this->builder = new CaptchaBuilder();
    }

    /**
     * 显示图形验证码
     *
     * @param integer $width 宽
     * @param integer $height 高
     * @param integer $quality 质量
     * @return think\Response
     */
    public function output($width = 150, $height = 40, $quality = 80)
    {
        $this->builder->build($width, $height);
        $this->builder->output($quality);
        $this->lock->release();
        $this->lock->lock(my_md5($this->builder->getPhrase()));
        return Response::create()->contentType('image/jpeg');
    }

    /**
     * 校验验证码
     *
     * @param string $captcha
     * @return boolean
     */
    public function verify($captcha)
    {
        $phrase = $this->lock->getLockValue();
        if ($phrase != my_md5($captcha)) {
            return false;
        }
        $this->lock->release();
        return true;
    }
}
