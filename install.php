<?php
require __DIR__.'/core.php';
$error=''; $installed=false;
try { $installed=(bool)query('SELECT COUNT(*) FROM admins')->fetchColumn(); } catch(Throwable $e) {}
if($installed) { head('Sistema instalado'); echo '<section class="card narrow"><h1>Já está tudo pronto.</h1><p>O instalador está bloqueado porque já existe um administrador.</p><a class="button" href="index.php?page=login">Entrar no sistema</a></section>'; foot(); exit; }
if($_SERVER['REQUEST_METHOD']==='POST') { try {
 checkCsrf(); $email=trim($_POST['email']??''); $password=$_POST['password']??'';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10) throw new RuntimeException('Informe um e-mail válido e uma senha com pelo menos 10 caracteres.');
 if(!preg_match('/^[a-zA-Z0-9_]+$/',$config['database'])) throw new RuntimeException('Nome de banco inválido.');
 db(false)->exec('CREATE DATABASE IF NOT EXISTS `'.$config['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 db()->exec('CREATE TABLE IF NOT EXISTS admins (id INT PRIMARY KEY AUTO_INCREMENT,email VARCHAR(190) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL) ENGINE=InnoDB;
 CREATE TABLE IF NOT EXISTS settings (name VARCHAR(40) PRIMARY KEY,value VARCHAR(255) NOT NULL) ENGINE=InnoDB;
 CREATE TABLE IF NOT EXISTS services (id INT PRIMARY KEY AUTO_INCREMENT,name VARCHAR(120) NOT NULL,duration INT NOT NULL,active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB;
 CREATE TABLE IF NOT EXISTS appointments (id INT PRIMARY KEY AUTO_INCREMENT,name VARCHAR(120) NOT NULL,phone VARCHAR(20) NOT NULL,service_id INT NOT NULL,starts DATETIME NOT NULL,ends DATETIME NOT NULL,status VARCHAR(20) NOT NULL,manage_token VARCHAR(64) UNIQUE NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(starts),FOREIGN KEY(service_id) REFERENCES services(id)) ENGINE=InnoDB;
 CREATE TABLE IF NOT EXISTS blocks (id INT PRIMARY KEY AUTO_INCREMENT,starts DATETIME NOT NULL,ends DATETIME NOT NULL,reason VARCHAR(120) NOT NULL,INDEX(starts)) ENGINE=InnoDB;
 CREATE TABLE IF NOT EXISTS login_attempts (ip VARCHAR(64) PRIMARY KEY,attempts INT NOT NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB');
 locked(function() use($email,$password) { if(query('SELECT COUNT(*) FROM admins')->fetchColumn()) throw new RuntimeException('Sistema já instalado.'); db()->beginTransaction(); try {
 query('INSERT INTO admins(email,password) VALUES(?,?)',[$email,password_hash($password,PASSWORD_DEFAULT)]);
 foreach(['open'=>'08:00','close'=>'18:00','days'=>'1,2,3,4,5','gap'=>'0'] as $key=>$value) query('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$key,$value]);
 query("INSERT INTO services(name,duration) VALUES('Avaliação inicial',60),('Sessão de fisioterapia',60)"); db()->commit(); } catch(Throwable $e) { db()->rollBack(); throw $e; } });
 go('index.php?page=login&installed=1');
 } catch(Throwable $ex) { $error=$ex instanceof PDOException?'Não foi possível preparar o banco. Ligue o MySQL no XAMPP e confira config.php.':$ex->getMessage(); }
}
head('Instalação'); ?>
<section class="card narrow"><span class="eyebrow">PRIMEIRO ACESSO</span><h1>Vamos preparar sua agenda.</h1><p>Crie o acesso do administrador. Os serviços e horários iniciais poderão ser alterados no painel.</p>
<?php if($error): ?><p class="alert"><?=e($error)?></p><?php endif ?>
<form method="post"><?=token()?><label>E-mail do administrador<input type="email" name="email" required autocomplete="username"></label><label>Senha (mínimo de 10 caracteres)<input type="password" minlength="10" name="password" required autocomplete="new-password"></label><button>Instalar e criar acesso</button></form><p class="muted">Apache e MySQL precisam estar iniciados no XAMPP.</p></section>
<?php foot(); ?>
