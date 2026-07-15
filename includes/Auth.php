<?php
class Auth {
    private static ?array $currentUser = null;
    public static function login(string $u, string $p): bool { $db=Database::getInstance(); $user=$db->fetch("SELECT * FROM `users` WHERE `username`=? AND `is_active`=1 LIMIT 1",[$u]); if(!$user||!password_verify($p,$user['password_hash']))return false; $db->update('users',['last_login'=>date('Y-m-d H:i:s'),'last_ip'=>self::getClientIp()],'id=?',[$user['id']]); Session::regenerate(); Session::set('user_id',$user['id']); Session::set('username',$user['username']); Session::set('user_role',$user['role']); Session::set('logged_in',true); self::$currentUser=$user; return true; }
    public static function check(): bool { return Session::get('logged_in',false)===true; }
    public static function require(): void { if(!self::check()){header('Location: /admin/login.php?redirect='.urlencode($_SERVER['REQUEST_URI']??'/admin/'));exit;} }
    public static function requireAdmin(): void { self::require(); if(Session::get('user_role')!=='admin'){http_response_code(403);die('Access denied');} }
    public static function logout(): void { Session::destroy(); self::$currentUser=null; }
    public static function user(): ?array { if(self::$currentUser!==null)return self::$currentUser; if(!self::check())return null; self::$currentUser=Database::getInstance()->fetch("SELECT id,username,email,role,last_login,last_ip,created_at FROM `users` WHERE id=?",[Session::get('user_id')]); return self::$currentUser; }
    public static function id(): ?int { return Session::get('user_id'); }
    public static function isAdmin(): bool { return Session::get('user_role')==='admin'; }
    public static function changePassword(int $uid, string $cur, string $new): bool { $db=Database::getInstance(); $u=$db->fetch("SELECT password_hash FROM `users` WHERE id=?",[$uid]); if(!$u||!password_verify($cur,$u['password_hash']))return false; $db->update('users',['password_hash'=>password_hash($new,PASSWORD_BCRYPT,['cost'=>BCRYPT_COST])],'id=?',[$uid]); return true; }
    public static function createUser(string $u, string $p, ?string $e=null, string $r='admin'): ?int { try{return Database::getInstance()->insert('users',['username'=>$u,'password_hash'=>password_hash($p,PASSWORD_BCRYPT,['cost'=>BCRYPT_COST]),'email'=>$e,'role'=>$r]);}catch(Exception $ex){return null;} }
    public static function csrfToken(): string { if(!Session::has(CSRF_TOKEN_NAME))Session::set(CSRF_TOKEN_NAME,bin2hex(random_bytes(32))); return Session::get(CSRF_TOKEN_NAME); }
    public static function validateCsrf(?string $token=null): bool { if($token===null)$token=$_POST[CSRF_TOKEN_NAME]??$_SERVER['HTTP_X_CSRF_TOKEN']??null; if($token===null||!Session::has(CSRF_TOKEN_NAME))return false; return hash_equals(Session::get(CSRF_TOKEN_NAME),$token); }
    public static function getClientIp(): string { foreach(['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','HTTP_CLIENT_IP'] as $h){if(!empty($_SERVER[$h])){$ip=trim(explode(',',$_SERVER[$h])[0]); if(filter_var($ip,FILTER_VALIDATE_IP))return $ip;}} return $_SERVER['REMOTE_ADDR']??'127.0.0.1'; }
    public static function rateLimitCheck(string $a='default'): bool { $r=RedisClient::getInstance(); if(!$r->isConnected())return true; return $r->rateLimit('ratelimit:'.$a.':'.self::getClientIp(),RATE_LIMIT_MAX,RATE_LIMIT_WINDOW); }
}