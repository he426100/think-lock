<?php
namespace org;

use think\Cache;
use think\Config;

/**
 * 以Cache为基础实现的锁
 */
class Lock
{
    private $lockKey;
    private $initialTtl;

    /**
     * 锁
     *
     * @param string $lockKey
     * @param integer $initialTtl
     */
    public function __construct($lockKey, $initialTtl = 0)
    {
        $this->lockKey = $lockKey;
        $this->initialTtl = $initialTtl;
    }

    /**
     * 锁定
     *
     * @param mixed $lockValue 如不传则默认为当前时间戳
     * @param integer $ttl 如不传则使用初始值
     * @return boolean true表示锁定成功，false表示加锁失败
     */
    public function lock($lockValue = null, $ttl = null)
    {
        if ($ttl === null) {
            $ttl = $this->initialTtl;
        }
        //不存在才锁定
        $expire = ['nx'];
        //
        if (is_int($ttl) && $ttl > 0) {
            $expire['ex'] = $ttl;
        }
        return $this->set($this->lockKey, $lockValue === null ? time() : $lockValue, $expire);
    }

    /**
     * 不阻塞锁
     * 这里指的是锁存在的情况下可以更新锁
     *
     * @param mixed $lockValue
     * @param integer $ttl
     * @return boolean true表示锁定成功，false表示加锁失败
     */
    public function unblockLock($lockValue = null, $ttl = null)
    {
        if ($ttl === null) {
            $ttl = $this->initialTtl;
        }
        return Cache::set($this->lockKey, $lockValue === null ? time() : $lockValue, $ttl);
    }

    /**
     * 锁值自增长
     *
     * @return integer
     */
    public function inc()
    {
        return Cache::inc($this->lockKey);
    }

    /**
     * 是否已锁
     *
     * @return boolean
     */
    public function isLocked()
    {
        return Cache::has($this->lockKey);
    }

    /**
     * 获取锁值
     *
     * @return mixed
     */
    public function getLockValue()
    {
        return Cache::get($this->lockKey);
    }

    /**
     * 释放锁
     *
     */
    public function release()
    {
        return Cache::rm($this->lockKey);
    }

    /**
     * 设置Cache值，代替think\Cache::set
     * 
     * @param string  $name 缓存变量名
     * @param mixed   $value  存储数据
     * @param array $expire  有效时间（秒）  Array('nx', 'ex'=>10)
     * @return boolean
     */
    protected function set($name, $value, $expire = ['nx'])
    {
        $cache = Cache::init();
        $options = Config::get('cache');
        $key   = $options['prefix'] . $name;;
        $value = is_scalar($value) ? $value : 'think_serialize:' . serialize($value);
        return $cache->handler()->set($key, $value, $expire);
    }
}
