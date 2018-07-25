# think-lock
基于thinkphp的redis cache实现的简易锁及相关应用

在项目开发过程中经常会碰到需要防止同一个用户并发请求的情况，比如财务结算时需要保证结算过程不受干扰，除了数据库锁之外，还可以使用Cache来实现锁功能，即在过程结束前不允许有新的执行。

示例一：
```php
$lock = new Lock('login_'.$param['name'], 30);
if (!$lock->lock()) {
    $this->restError('请30s后在尝试');
}
```
Lock类第一个参数是锁名，第二个是锁定时间（默认永久）

示例二：
```php
$mobile = input('param.mobile');
$sms = new SmsLock($mobile);
if (!$sms->canSend()) {
    return $this->restResponse(10046);
}
```
