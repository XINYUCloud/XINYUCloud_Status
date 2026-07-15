<?php
function e(mixed $v): string { return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function json_response(mixed $data, int $code=200): void { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }
function redirect(string $url, int $code=302): void { header('Location: '.$url,true,$code); exit; }