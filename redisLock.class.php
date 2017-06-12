<?php

/**
 * Created by PhpStorm.
 * User: Jimmy
 * Date: 2016/7/12
 * Time: 13:38
 */
class redisLock
{
    //静态链接
    static public $instance;
    //当前链接
    private $use_instance;

    function __construct($host = '127.0.0.1', $post = '6379', $auth = NULL)
    {
        $this->use_instance = $this->connect($host, $post, $auth);
    }

    public function &redis_instance()
    {
        return $this->use_instance;
    }

    public function connect($host, $post, $auth)
    {

        $unique_str = substr(md5($host . $post), 0, 15);

        if (!isset(self::$instance[$unique_str])) {

            $redis = new Redis();
            $redis->connect($host, $post);
            $redis->auth($auth);
            self::$instance[$unique_str] = $redis;

        }

        return self::$instance[$unique_str];

    }

    public function acquire_lock($lock_name, $acquire_time = 3, $lock_timeout = 10)
    {

        $identifier = md5($_SERVER['REQUEST_TIME'] . mt_rand(1, 10000000));
        $lock_name = 'lock:' . $lock_name;
        $lock_timeout = intval(ceil($lock_timeout));
        $end_time = time() + $acquire_time;
        while (time() < $end_time) {
            $script = <<<luascript
                 local result = redis.call('setnx',KEYS[1],ARGV[1]);
                    if result == 1 then
                        redis.call('expire',KEYS[1],ARGV[2])
                        return 1
                    elseif redis.call('ttl',KEYS[1]) == -1 then
                       redis.call('expire',KEYS[1],ARGV[2])
                       return 0
                    end
                    return 0
luascript;
            $result = $this->use_instance->evaluate($script, array($lock_name, $identifier, $lock_timeout), 1);
            if ($result == '1') {
                return $identifier;
            }
            usleep(100000);
        }
        return false;
    }

    public function release_lock($lock_name, $identifier)
    {
        $lock_name = 'lock:' . $lock_name;
        while (true) {
            $script = <<<luascript
                local result = redis.call('get',KEYS[1]);
                if result == ARGV[1] then
                    if redis.call('del',KEYS[1]) == 1 then
                        return 1;
                    end
                end
                return 0
luascript;
            $result = $this->use_instance->evaluate($script, array($lock_name, $identifier), 1);
            if ($result == 1) {
                return true;
            }
            break;
        }
        //进程已经失去了锁
        return false;
    }

}