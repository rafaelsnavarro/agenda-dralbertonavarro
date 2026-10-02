<?php
declare(strict_types=1);
$config = require __DIR__.'/config.php';
date_default_timezone_set($config['timezone']);
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
function db(bool $database=true): PDO { global $config; static $connections=[]; $key=(int)$database; if (!isset($connections[$key])) { $dsn='mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4'.($database?';dbname='.$config['database']:''); $connections[$key]=new PDO($dsn,$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); } return $connections[$key]; }
function query(string $sql,array $args=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($args); return $s; }
function e(mixed $s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function token(): string { return '<input type="hidden" name="csrf" value="'.csrf().'">'; }
function checkCsrf(): void { if (!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Sua sessão expirou. Atualize a página e tente novamente.'); }
function go(string $url): never { header('Location: '.$url); exit; }
function auth(): void { if (empty($_SESSION['admin'])) go('index.php?page=login'); }
function setting(string $key): string { return (string)query('SELECT value FROM settings WHERE name=?',[$key])->fetchColumn(); }
function validDate(string $date): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date); return $d && $d->format('Y-m-d')===$date; }
function slots(string $date,int $service): array {
 if (!validDate($date) || $date<date('Y-m-d') || $date>date('Y-m-d',strtotime('+90 days'))) return [];
 $duration=query('SELECT duration FROM services WHERE id=? AND active=1',[$service])->fetchColumn(); if (!$duration) return [];
 $day=(int)date('N',strtotime($date)); if (!in_array((string)$day,explode(',',setting('days')),true)) return [];
 $busy=query("SELECT starts,ends FROM appointments WHERE DATE(starts)=? AND status<>'cancelado' UNION ALL SELECT starts,ends FROM blocks WHERE starts < ? AND ends > ?",[$date,$date.' 23:59:59',$date.' 00:00:00'])->fetchAll();
 $out=[]; $close=strtotime($date.' '.setting('close')); $gap=(int)setting('gap');
 for ($t=strtotime($date.' '.setting('open')); $t+(int)$duration*60<=$close; $t+=1800) {
  if ($t<=time()) continue; $end=$t+(int)$duration*60; $available=true;
  foreach($busy as $b) if($t<strtotime($b['ends'])+$gap*60 && $end+$gap*60>strtotime($b['starts'])) {$available=false;break;}
  if($available) $out[]=date('H:i',$t);
 } return $out;
}
function locked(callable $fn): mixed { $pdo=db(); if (!(int)query("SELECT GET_LOCK('navarro_agenda_write',10)")->fetchColumn()) throw new RuntimeException('Agenda ocupada. Tente novamente.'); try { return $fn(); } finally { query("SELECT RELEASE_LOCK('navarro_agenda_write')"); } }
function book(array $data,?int $replace=null): int {
 $name=trim((string)($data['name']??'')); $phone=preg_replace('/\D/','',(string)($data['phone']??'')); $date=(string)($data['date']??''); $time=(string)($data['time']??''); $service=(int)($data['service']??0);
 if (mb_strlen($name)<3 || mb_strlen($name)>120 || !preg_match('/^\d{10,13}$/',$phone)) throw new RuntimeException('Informe nome completo e telefone válido com DDD.');
 return locked(function() use($name,$phone,$date,$time,$service,$replace) {
  db()->beginTransaction(); try {
   if($replace) query("UPDATE appointments SET status='cancelado' WHERE id=?",[$replace]);
   if(!in_array($time,slots($date,$service),true)) throw new RuntimeException('Esse horário não está disponível. Escolha outro.');
   $s=query('SELECT * FROM services WHERE id=? AND active=1',[$service])->fetch(); $start=$date.' '.$time.':00'; $end=date('Y-m-d H:i:s',strtotime($start)+(int)$s['duration']*60);
   if($replace) { query("UPDATE appointments SET name=?,phone=?,service_id=?,starts=?,ends=?,status='aguardando' WHERE id=?",[$name,$phone,$service,$start,$end,$replace]); $id=$replace; }
   else { query("INSERT INTO appointments(name,phone,service_id,starts,ends,status,manage_token) VALUES(?,?,?,?,?,'aguardando',?)",[$name,$phone,$service,$start,$end,bin2hex(random_bytes(24))]); $id=(int)db()->lastInsertId(); }
   db()->commit(); return $id;
  } catch(Throwable $ex) { if(db()->inTransaction()) db()->rollBack(); throw $ex; }
 });
}
function head(string $title): void { echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).' · Dr. Alberto Navarro</title><link rel="stylesheet" href="style.css"><body><header><a class="brand" href="index.php"><b class="mark">N</b><span>Dr. Alberto Navarro<small>Fisioterapia · Agendamento</small></span></a><nav><a href="index.php">Agendar</a><a href="index.php?page=admin">Área administrativa</a></nav></header><main>'; }
function foot(): void { echo '</main><footer>Dr. Alberto Navarro · Agenda online <span>Protótipo local — use dados fictícios nos testes.</span></footer></body></html>'; }
