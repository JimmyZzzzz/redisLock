# redisLock
PHP使用Redis实现分布式锁,结合lua实现Redis命令原子性

$redisLock = new redisLock(IP,PORT,AUTH);

返回redis实例
redisLock->redis_instance();

获取锁: $lock_name 锁名 , $acquire_time 重复请求次数 , $lock_timeout 请求超时时间 单位s
redisLock->acquire_lock($lock_name, $acquire_time = 3, $lock_timeout = 10)

释放锁: $lock_name 锁名 , $identifier 获取锁 返回的标识;
redisLock->release_lock($lock_name, $identifier);
