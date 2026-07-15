<?php
class Session {
    private static bool $started = false;
    public static function start(): void { if(self::$started||php_sapi_name()==='cli'){self::$started=true;return;} $r=RedisClient::getInstance(); if($r->isConnected()){ini_set('session.save_handler','redis'); ini_set('session.save_path',sprintf('tcp://%s:%s?auth=%s&prefix=%ssession:',REDIS_HOST,REDIS_PORT,REDIS_PASS,REDIS_PREFIX));} ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1'); ini_set('session.cookie_httponly','1'); ini_set('session.cookie_samesite','Lax'); ini_set('session.gc_maxlifetime',(string)SESSION_LIFETIME); session_name(SESSION_NAME); if(session_status()===PHP_SESSION_NONE)session_start(); self::$started=true; if(!isset($_SESSION['_last_regenerated']))self::regenerate(); elseif(time()-$_SESSION['_last_regenerated']>1800)self::regenerate(); }
    public static function regenerate(): void { if(session_status()===PHP_SESSION_ACTIVE){session_regenerate_id(true);$_SESSION['_last_regenerated']=time();} }
    public static function set(string $k, mixed $v): void { $_SESSION[$k]=$v; }
    public static function get(string $k, mixed $d=null): mixed { return $_SESSION[$k]??$d; }
    public static function has(string $k): bool { return isset($_SESSION[$k]); }
    public static function remove(string $k): void { unset($_SESSION[$k]); }
    public static function destroy(): void { $_SESSION=[]; if(ini_get('session.use_cookies')){$p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);} session_destroy(); self::$started=false; }
    public static function setFlash(string $t, string $m): void { $_SESSION['_flash'][]=['type'=>$t,'message'=>$m]; }
    public static function getFlash(): array { $f=$_SESSION['_flash']??[]; unset($_SESSION['_flash']); return $f; }
}