<?php
class RedisClient {
    private static ?RedisClient $instance = null;
    private ?Redis $redis = null;
    private bool $connected = false;
    private function __construct() { if(!class_exists('Redis'))return; try{$this->redis=new Redis(); $this->connected=$this->redis->connect(REDIS_HOST,(int)REDIS_PORT,2.0); if($this->connected&&REDIS_PASS!=='')$this->connected=$this->redis->auth(REDIS_PASS); if($this->connected&&REDIS_DB!=='0')$this->redis->select((int)REDIS_DB); if($this->connected){$this->redis->setOption(Redis::OPT_PREFIX,REDIS_PREFIX); $this->redis->setOption(Redis::OPT_SERIALIZER,Redis::SERIALIZER_PHP);}}catch(Exception $e){$this->connected=false;} }
    public static function getInstance(): self { if(self::$instance===null)self::$instance=new self(); return self::$instance; }
    public function isConnected(): bool { return $this->connected; }
    public function get(string $key): mixed { if(!$this->connected)return null; try{return $this->redis->get($key);}catch(Exception $e){return null;} }
    public function set(string $key, mixed $value, int $ttl=300): bool { if(!$this->connected)return false; try{return $this->redis->setex($key,$ttl,$value);}catch(Exception $e){return false;} }
    public function delete(string $key): bool { if(!$this->connected)return false; try{return (bool)$this->redis->del($key);}catch(Exception $e){return false;} }
    public function hset(string $k, string $f, mixed $v): int { if(!$this->connected)return 0; try{return $this->redis->hSet($k,$f,$v);}catch(Exception $e){return 0;} }
    public function hget(string $k, string $f): mixed { if(!$this->connected)return null; try{return $this->redis->hGet($k,$f);}catch(Exception $e){return null;} }
    public function hgetall(string $k): array { if(!$this->connected)return[]; try{return $this->redis->hGetAll($k)?:[];}catch(Exception $e){return[];} }
    public function increment(string $k, int $by=1): int { if(!$this->connected)return 0; try{return $this->redis->incrBy($k,$by);}catch(Exception $e){return 0;} }
    public function expire(string $k, int $ttl): bool { if(!$this->connected)return false; try{return $this->redis->expire($k,$ttl);}catch(Exception $e){return false;} }
    public function exists(string $k): bool { if(!$this->connected)return false; try{return (bool)$this->redis->exists($k);}catch(Exception $e){return false;} }
    public function rateLimit(string $k, int $max, int $win): bool { if(!$this->connected)return true; $c=$this->increment($k); if($c===1)$this->expire($k,$win); return $c<=$max; }
    public function deleteByPattern(string $p): int { if(!$this->connected)return 0; try{$keys=$this->redis->keys($p); if(empty($keys))return 0; return $this->redis->del($keys);}catch(Exception $e){return 0;} }
    private function __clone() {}
}