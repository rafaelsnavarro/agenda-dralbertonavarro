# Agenda de fisioterapia

Versão inicial para testes locais de agendamento do Dr. Alberto Navarro.
Desenvolvida em PHP, MySQL/MariaDB, JavaScript e CSS, sem dependências externas.

## Funcionalidades

- Agendamento com duração por serviço e prevenção de sobreposição.
- Painel administrativo com agenda diária e pesquisa de pacientes.
- Confirmação, cancelamento, conclusão e registro de faltas.
- Link privado para consulta, cancelamento e reagendamento pelo paciente.
- Cadastro de serviços, disponibilidade e bloqueios.
- Login administrativo e alteração de senha.

## Executar no XAMPP

1. Coloque estes arquivos em `C:\xampp\htdocs\agendamento`.
2. Copie `config.example.php` para `config.php` e ajuste a conexão se necessário.
3. Inicie Apache e MySQL no painel do XAMPP.
4. Abra `http://localhost/agendamento/install.php`.
5. Crie seu acesso administrativo. O instalador prepara o banco automaticamente.
6. Acesse `http://localhost/agendamento/`.

Requisitos: PHP 8.1+, extensões `pdo_mysql` e `mbstring`, MySQL/MariaDB.
Testado originalmente com PHP 8.2.12 e MariaDB 10.4.32.

## Arquivos e dados

`config.php` é local e está ignorado pelo Git. Este repositório contém apenas
`config.example.php`, com valores de exemplo. O instalador requer `config.php`.
Pacientes, reservas e contas ficam no banco, não nos arquivos do repositório.
Não envie exports SQL, senhas ou dados reais de pacientes ao GitHub.
O `.gitignore` não protege uploads feitos manualmente pelo navegador:
confira sempre os arquivos selecionados.

## Atualizações

Alterações no GitHub não atualizam automaticamente XAMPP ou hospedagem.
Preserve o `config.php` local e faça backup do banco antes de atualizar.
Não reinstale nem sobrescreva o banco para atualizar apenas o código.

## Escopo atual

Uma agenda para um profissional. Não inclui WhatsApp/e-mail automático,
pagamentos, prontuário, séries recorrentes automáticas ou múltiplos profissionais.
É uma versão para testes locais. Antes da publicação, preparar HTTPS, backups,
credenciais próprias, proteção contra abuso e regras de privacidade da clínica.
GitHub Pages não executa este sistema PHP/MySQL.

Consulte `LEIA-ME.txt` para regras da agenda e orientações de backup.
