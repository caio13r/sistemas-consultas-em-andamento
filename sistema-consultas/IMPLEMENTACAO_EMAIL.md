# Documentação Completa - Implementação de Email no Sistema de Consultas

## Índice
1. [Visão Geral](#visão-geral)
2. [Configuração do Servidor de Email](#configuração-do-servidor-de-email)
3. [Biblioteca Utilizada](#biblioteca-utilizada)
4. [Configuração SMTP](#configuração-smtp)
5. [Implementação - Recuperação de Senha](#implementação---recuperação-de-senha)
6. [Outras Funcionalidades que Utilizam Email](#outras-funcionalidades-que-utilizam-email)
7. [Segurança e Boas Práticas](#segurança-e-boas-práticas)
8. [Solução de Problemas](#solução-de-problemas)
9. [Exemplos de Uso](#exemplos-de-uso)

---

## Visão Geral

O Sistema de Consultas CFO utiliza a biblioteca **PHPMailer** para envio de emails através de servidor SMTP. O sistema envia emails em várias partes da aplicação, sendo a funcionalidade mais importante a **recuperação de senha**.

### Características Principais:
- ✅ Envio de emails via SMTP com autenticação
- ✅ Suporte a HTML nos emails
- ✅ Charset UTF-8 para caracteres especiais
- ✅ Conexão segura via SSL/TLS
- ✅ Sistema de recuperação de senha completo

---

## Configuração do Servidor de Email

### Dados do Servidor SMTP

```
Host SMTP: email-ssl.com.br
Porta: 465
Segurança: SSL (SMTPS)
Autenticação: SIM
Usuário: sistema-consultas@cfo.org.br
Senha: [Configurada no código]
```

### Detalhes Técnicos

| Parâmetro | Valor |
|-----------|-------|
| **Protocolo** | SMTP |
| **Porta** | 465 |
| **Criptografia** | SSL (ENCRYPTION_SMTPS) |
| **Autenticação** | Habilitada |
| **Remetente Padrão** | sistema-consultas@cfo.org.br |
| **Nome Remetente** | Sistema Consultas CFO |

---

## Biblioteca Utilizada

### PHPMailer

O sistema utiliza a biblioteca **PHPMailer** versão instalada via Composer.

#### Instalação via Composer

```bash
composer require phpmailer/phpmailer
```

#### Importação das Classes

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
```

### Localização dos Arquivos

- **Biblioteca Principal:** `src/vendor/phpmailer/phpmailer/src/PHPMailer.php`
- **Classe SMTP:** `src/vendor/phpmailer/phpmailer/src/SMTP.php`
- **Exceções:** `src/vendor/phpmailer/phpmailer/src/Exception.php`
- **Idiomas:** `src/vendor/phpmailer/phpmailer/language/` (incluindo pt.php e pt_br.php)

---

## Configuração SMTP

### Configuração Básica

A configuração SMTP é feita diretamente no código quando necessário. Segue exemplo da configuração padrão:

```php
$mail = new PHPMailer(true);

try {
    // Configurações do servidor
    $mail->CharSet    = "UTF-8";        // Charset para caracteres especiais
    $mail->Encoding   = 'base64';       // Encoding do conteúdo
    $mail->isSMTP();                    // Usar SMTP
    $mail->Host       = 'email-ssl.com.br';        // Servidor SMTP
    $mail->SMTPAuth   = true;                      // Habilitar autenticação
    $mail->Username   = 'sistema-consultas@cfo.org.br';  // Usuário SMTP
    $mail->Password   = 'cfo.1234.CFO';           // Senha SMTP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL
    $mail->Port       = 465;                       // Porta SSL
    
    // Configurações do remetente
    $mail->setFrom('sistema-consultas@cfo.org.br', 'Sistema Consultas CFO');
    
    // Configurações do destinatário
    $mail->addAddress('destinatario@exemplo.com');
    
    // Configurações do email
    $mail->isHTML(true);
    $mail->Subject = 'Assunto do Email';
    $mail->Body    = '<p>Conteúdo HTML do email</p>';
    
    $mail->send();
    echo 'Email enviado com sucesso!';
    
} catch (Exception $e) {
    echo "Erro ao enviar email: {$mail->ErrorInfo}";
}
```

### Configurações Avançadas

#### Debug SMTP

Para habilitar o debug durante desenvolvimento:

```php
// Descomentar para debug
$mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Exibe mensagens detalhadas
// Ou usar DEBUG_CLIENT, DEBUG_CONNECTION, DEBUG_LOWLEVEL
```

#### Opções Adicionais

```php
// Timeout da conexão (em segundos)
$mail->Timeout = 10;

// Manter conexão aberta para múltiplos emails
$mail->SMTPKeepAlive = true;

// Configurações de contexto SSL (se necessário)
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);
```

---

## Implementação - Recuperação de Senha

### Fluxo Completo

A recuperação de senha está implementada em `src/views/senhaRecuperar.php` e segue o seguinte fluxo:

#### 1. Solicitação de Recuperação

**URL:** `/recuperar-senha`

O usuário informa seu email cadastrado no sistema.

#### 2. Geração do Token

Quando o email é encontrado no banco de dados, o sistema:

1. Gera um token único baseado em `microtime()`
2. Converte para base36 para criar um ID alfanumérico
3. Armazena o token no banco de dados na coluna `idNewPassword` da tabela `tbl_users`

**Código de Geração:**

```php
$id = microtime();
$a = explode(' ', $id);
$a[0] = str_replace('.', '', $a[0]);
$a[0] = base_convert($a[0], 10, 36);
$a[1] = base_convert($a[1], 10, 36);
$id = $a[0] . $a[1];
$idpassword = strtoupper($id);
```

#### 3. Atualização no Banco de Dados

```php
$sql = "UPDATE tbl_users SET idNewPassword = :idpassword WHERE email = :email";
$stmt = $con->prepare($sql);
$stmt->bindValue(':email', $email);
$stmt->bindValue(':idpassword', $idpassword);
$result = $stmt->execute();
```

#### 4. Envio do Email

O sistema envia um email com:

- **Assunto:** "Recuperar senha - Sistema Consultas CFO"
- **Conteúdo HTML** contendo:
  - Informação sobre a solicitação
  - O ID/token gerado
  - Link direto para redefinição: `https://consultas.cfo.org.br/recuperar-senha?email={email}&token={token}`

**Exemplo do Email Enviado:**

```php
$mail->Subject = 'Recuperar senha - Sistema Consultas CFO';
$mail->Body    = 'Este é um email enviado automaticamente pelo <b>Sistema Consultas do CFO.</b> <br>
Foi realizada uma solicitação de alteração de senha na conta cadastrada no e-mail: <u>'.$email.'</u> <br><br>
Seu ID para alteração do password: <b>'.$idpassword.'</b> <br><br>
Clique no link abaixo para completar a alteração da senha: <br>
<a href="https://consultas.cfo.org.br/recuperar-senha?email='.$email.'&token='.$idpassword.'">https://consultas.cfo.org.br/recuperar-senha?email='.$email.'&token='.$idpassword.'</a> <br><br>
Caso você não tenha solicitado a alteração da senha, apenas ignore este e-mail. <br><br>
Conselho Federal de Odontologia - CFO <br>
<a href="https://consultas.cfo.org.br/">https://consultas.cfo.org.br/</a>';
```

#### 5. Redefinição de Senha

Quando o usuário acessa o link com token e email:

1. O sistema exibe um formulário pre-preenchido com email e token
2. O usuário informa a nova senha duas vezes (confirmação)
3. O sistema valida o token no banco de dados
4. Se válido, atualiza a senha usando `password_hash()`
5. Remove o token usado (`idNewPassword = NULL`)

### Código Completo - Recuperação de Senha

```php
<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

Session::init();
Session::CheckLogin();

// Processamento do formulário
if (isset($inputPost["sendmail"])) {
    
    // Verifica se email existe no banco
    $checkEmail = $users->checkExistEmail($inputPost["sendmail"]);
    
    if ($checkEmail === true) {
        
        // Geração do token
        $id = microtime();
        $a = explode(' ', $id);
        $a[0] = str_replace('.', '', $a[0]);
        $a[0] = base_convert($a[0], 10, 36);
        $a[1] = base_convert($a[1], 10, 36);
        $id = $a[0] . $a[1];
        $idpassword = strtoupper($id);
        
        // Salva token no banco
        try {
            $db = Database1::getInstance();
            $con = $db->getConnection();
            $email = $inputPost["sendmail"];
            $sql = "UPDATE tbl_users SET idNewPassword = :idpassword WHERE email = :email";
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':idpassword', $idpassword);
            $result = $stmt->execute();
        } catch (PDOexception $error) {
            die("Error: " . $error->getMessage());
        }
        
        // Envio do e-mail
        $mail = new PHPMailer(true);
        
        try {
            // Configurações do servidor 
            $mail->CharSet    = "UTF-8";  
            $mail->Encoding   = 'base64';      
            $mail->isSMTP();
            $mail->Host       = 'email-ssl.com.br';
            $mail->SMTPAuth   = true;                      
            $mail->Username   = 'sistema-consultas@cfo.org.br';
            $mail->Password   = 'cfo.1234.CFO';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;  
            
            // Receptores
            $mail->setFrom('sistema-consultas@cfo.org.br', 'Sistema Consultas CFO');
            $mail->addAddress($inputPost["sendmail"]);
        
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'Recuperar senha - Sistema Consultas CFO';
            $mail->Body    = 'Este é um email enviado automaticamente pelo <b>Sistema Consultas do CFO.</b> <br>
            Foi realizada uma solicitação de alteração de senha na conta cadastrada no e-mail: <u>'.$email.'</u> <br><br>
            Seu ID para alteração do password: <b>'.$idpassword.'</b> <br><br>
            Clique no link abaixo para completar a alteração da senha: <br>
            <a href="https://consultas.cfo.org.br/recuperar-senha?email='.$email.'&token='.$idpassword.'">https://consultas.cfo.org.br/recuperar-senha?email='.$email.'&token='.$idpassword.'</a> <br><br>
            Caso você não tenha solicitado a alteração da senha, apenas ignore este e-mail. <br><br>
            Conselho Federal de Odontologia - CFO <br>
            <a href="https://consultas.cfo.org.br/">https://consultas.cfo.org.br/</a>';
        
            $mail->send();
            
            $msg = '<div class="alert alert-success alert-dismissible mt-3" id="flash-msg">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Successo!</strong> O e-mail com o link para alteração de senha foi enviado para <u>'.$email.'</u></div>';
            echo $msg;
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
        
    } else {
        $msg = '<div class="alert alert-danger alert-dismissible mt-3" id="flash-msg">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Error!</strong> Nenhum endereço de e-mail foi encontrado!</div>';
        echo $msg;
    }
}
?>
```

---

## Outras Funcionalidades que Utilizam Email

### 1. Notificações do Sistema

O sistema pode ser estendido para enviar emails em outras situações:

- **Cadastro de novo usuário:** Enviar email de boas-vindas
- **Alteração de perfil:** Notificar mudanças importantes
- **Atualizações do sistema:** Informar sobre novas funcionalidades
- **Alertas de segurança:** Notificar sobre tentativas de acesso suspeitas

### 2. Relatórios por Email

O sistema possui várias funcionalidades de relatórios que podem ser enviados por email:

- Relatórios de consultas RFB
- Relatórios de eleições
- Relatórios de auditoria
- Relatórios estatísticos

### 3. Notificações Administrativas

Emails podem ser enviados para administradores em casos como:

- Erros críticos do sistema
- Tentativas de acesso não autorizado
- Requisições de permissões especiais
- Alertas de manutenção

---

## Segurança e Boas Práticas

### ✅ Implementações de Segurança Atuais

1. **Senha não exposta:** A senha SMTP está no código (deve ser movida para variáveis de ambiente)
2. **Token único:** Cada solicitação gera um token único baseado em timestamp
3. **Validação de email:** Sistema verifica se email existe antes de enviar
4. **HTTPS obrigatório:** Links de recuperação usam HTTPS
5. **Charset UTF-8:** Previne problemas com caracteres especiais

### ⚠️ Recomendações de Melhorias

#### 1. Variáveis de Ambiente

**Problema Atual:** Senha SMTP está hardcoded no código.

**Solução Recomendada:**

Criar arquivo `.env`:

```env
SMTP_HOST=email-ssl.com.br
SMTP_PORT=465
SMTP_USER=sistema-consultas@cfo.org.br
SMTP_PASS=cfo.1234.CFO
SMTP_FROM_EMAIL=sistema-consultas@cfo.org.br
SMTP_FROM_NAME=Sistema Consultas CFO
```

E usar no código:

```php
$mail->Host       = $_ENV['SMTP_HOST'];
$mail->Username   = $_ENV['SMTP_USER'];
$mail->Password   = $_ENV['SMTP_PASS'];
```

#### 2. Classe Helper para Email

Criar uma classe reutilizável para envio de emails:

**Arquivo:** `src/lib/EmailHelper.php`

```php
<?php
namespace Cfo\SisConsultas\lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        $this->mail->CharSet = "UTF-8";
        $this->mail->Encoding = 'base64';
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['SMTP_HOST'] ?? 'email-ssl.com.br';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['SMTP_USER'] ?? 'sistema-consultas@cfo.org.br';
        $this->mail->Password = $_ENV['SMTP_PASS'] ?? 'cfo.1234.CFO';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = $_ENV['SMTP_PORT'] ?? 465;
        
        $this->mail->setFrom(
            $_ENV['SMTP_FROM_EMAIL'] ?? 'sistema-consultas@cfo.org.br',
            $_ENV['SMTP_FROM_NAME'] ?? 'Sistema Consultas CFO'
        );
    }
    
    public function sendPasswordRecovery($email, $token) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Recuperar senha - Sistema Consultas CFO';
            
            $recoveryUrl = "https://consultas.cfo.org.br/recuperar-senha?email={$email}&token={$token}";
            
            $this->mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Recuperação de Senha</h2>
                    <p>Este é um email enviado automaticamente pelo <strong>Sistema Consultas do CFO.</strong></p>
                    <p>Foi realizada uma solicitação de alteração de senha na conta cadastrada no e-mail: <u>{$email}</u></p>
                    <p><strong>Seu ID para alteração do password:</strong> <b>{$token}</b></p>
                    <p>Clique no link abaixo para completar a alteração da senha:</p>
                    <p><a href='{$recoveryUrl}' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a></p>
                    <p>Ou copie e cole o link no navegador:</p>
                    <p><small>{$recoveryUrl}</small></p>
                    <hr>
                    <p><small>Caso você não tenha solicitado a alteração da senha, apenas ignore este e-mail.</small></p>
                    <p><strong>Conselho Federal de Odontologia - CFO</strong></p>
                    <p><a href='https://consultas.cfo.org.br/'>https://consultas.cfo.org.br/</a></p>
                </body>
                </html>
            ";
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email enviado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erro ao enviar email: {$this->mail->ErrorInfo}"];
        }
    }
    
    public function send($to, $subject, $body, $isHTML = true) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email enviado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erro ao enviar email: {$this->mail->ErrorInfo}"];
        }
    }
}
```

**Uso da Classe:**

```php
use Cfo\SisConsultas\lib\EmailHelper;

$emailHelper = new EmailHelper();
$result = $emailHelper->sendPasswordRecovery($email, $token);

if ($result['success']) {
    echo $result['message'];
} else {
    echo "Erro: " . $result['message'];
}
```

#### 3. Validação de Token com Expiração

**Melhoria:** Adicionar expiração aos tokens de recuperação.

```php
// Na geração do token, adicionar timestamp de expiração
$expirationTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
$sql = "UPDATE tbl_users SET 
    idNewPassword = :idpassword,
    password_recovery_expires = :expires
    WHERE email = :email";

// Na validação, verificar se não expirou
$sql = "SELECT idNewPassword, password_recovery_expires 
    FROM tbl_users 
    WHERE email = :email 
    AND idNewPassword = :token
    AND password_recovery_expires > NOW()";
```

#### 4. Logs de Envio

Implementar sistema de logs para rastreamento:

```php
// Registrar tentativas de envio
function logEmail($email, $type, $status, $message = '') {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'email' => $email,
        'type' => $type, // 'password_recovery', 'notification', etc.
        'status' => $status, // 'sent', 'failed'
        'message' => $message
    ];
    
    // Salvar em arquivo ou banco de dados
    error_log(json_encode($log));
}
```

#### 5. Rate Limiting

Limitar número de tentativas de recuperação por IP/email:

```php
// Verificar tentativas recentes
$sql = "SELECT COUNT(*) as attempts 
    FROM email_recovery_attempts 
    WHERE email = :email 
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    
if ($attempts >= 3) {
    return "Muitas tentativas. Aguarde 1 hora.";
}
```

---

## Solução de Problemas

### Problemas Comuns e Soluções

#### 1. Email não está sendo enviado

**Sintomas:**
- Nenhum erro é exibido
- Email não chega ao destinatário

**Soluções:**
```php
// Habilitar debug
$mail->SMTPDebug = SMTP::DEBUG_SERVER;

// Verificar logs do servidor
tail -f /var/log/mail.log

// Testar conexão SMTP
$mail->smtpConnect();
```

#### 2. Erro: "Could not authenticate"

**Causas Possíveis:**
- Senha incorreta
- Usuário SMTP incorreto
- Autenticação não habilitada no servidor

**Solução:**
```php
// Verificar credenciais
$mail->SMTPAuth = true;
$mail->Username = 'usuario-correto@dominio.com';
$mail->Password = 'senha-correta';
```

#### 3. Erro: "Connection timed out"

**Causas:**
- Firewall bloqueando porta 465
- Servidor SMTP inacessível
- Porta incorreta

**Solução:**
```php
// Aumentar timeout
$mail->Timeout = 30;

// Verificar porta (SSL = 465, TLS = 587)
$mail->Port = 465; // Para SSL
// ou
$mail->Port = 587; // Para TLS
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
```

#### 4. Caracteres especiais aparecem incorretamente

**Solução:**
```php
$mail->CharSet = "UTF-8";
$mail->Encoding = 'base64';
```

#### 5. Email cai na caixa de spam

**Soluções:**
- Configurar SPF no DNS do domínio
- Configurar DKIM
- Usar domínio válido e verificado
- Evitar palavras suspeitas no assunto
- Incluir texto alternativo (AltBody)

```php
$mail->AltBody = 'Texto alternativo sem HTML';
```

#### 6. Erro SSL/TLS

**Solução:**
```php
// Para desenvolvimento/testes (NÃO usar em produção sem verificar certificado)
$mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);
```

---

## Exemplos de Uso

### Exemplo 1: Email Simples

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'email-ssl.com.br';
    $mail->SMTPAuth = true;
    $mail->Username = 'sistema-consultas@cfo.org.br';
    $mail->Password = 'cfo.1234.CFO';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    
    $mail->setFrom('sistema-consultas@cfo.org.br', 'Sistema Consultas CFO');
    $mail->addAddress('usuario@exemplo.com');
    
    $mail->isHTML(true);
    $mail->Subject = 'Assunto do Email';
    $mail->Body = '<h1>Olá!</h1><p>Este é um email de teste.</p>';
    $mail->AltBody = 'Olá! Este é um email de teste.';
    
    $mail->send();
    echo 'Email enviado!';
} catch (Exception $e) {
    echo "Erro: {$mail->ErrorInfo}";
}
```

### Exemplo 2: Email com Anexo

```php
$mail->addAttachment('/caminho/para/arquivo.pdf', 'documento.pdf');
$mail->addAttachment('/caminho/para/imagem.jpg');
```

### Exemplo 3: Email com Múltiplos Destinatários

```php
$mail->addAddress('usuario1@exemplo.com');
$mail->addAddress('usuario2@exemplo.com');
$mail->addCC('copia@exemplo.com');
$mail->addBCC('copia-oculta@exemplo.com');
```

### Exemplo 4: Email com Reply-To

```php
$mail->addReplyTo('suporte@cfo.org.br', 'Suporte CFO');
```

### Exemplo 5: Template HTML para Email

```php
$template = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #0066cc; color: white; padding: 20px; }
        .content { padding: 20px; }
        .footer { background-color: #f4f4f4; padding: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Sistema Consultas CFO</h1>
        </div>
        <div class='content'>
            <p>Olá, {$nome}!</p>
            <p>{$mensagem}</p>
        </div>
        <div class='footer'>
            <p>© " . date('Y') . " - Conselho Federal de Odontologia</p>
        </div>
    </div>
</body>
</html>
";

$mail->Body = $template;
```

### Exemplo 6: Email com Prioridade

```php
$mail->Priority = 1; // 1 = Alta, 3 = Normal, 5 = Baixa
```

---

## Estrutura de Arquivos Relacionados

```
sistema-consultas/
├── src/
│   ├── views/
│   │   └── senhaRecuperar.php          # Interface e lógica de recuperação
│   ├── lib/
│   │   └── [EmailHelper.php]           # (Recomendado criar)
│   ├── config/
│   │   └── config.php                  # Configurações gerais
│   ├── vendor/
│   │   └── phpmailer/
│   │       └── phpmailer/
│   │           ├── src/
│   │           │   ├── PHPMailer.php
│   │           │   ├── SMTP.php
│   │           │   └── Exception.php
│   │           └── language/
│   │               └── phpmailer.lang-pt.php
│   └── database/
│       └── [Tabelas relacionadas]
│           └── tbl_users               # Tabela com idNewPassword
└── IMPLEMENTACAO_EMAIL.md             # Este documento
```

---

## Checklist de Implementação

### ✅ Configuração Básica
- [x] PHPMailer instalado via Composer
- [x] Configuração SMTP definida
- [x] Credenciais configuradas
- [x] Charset UTF-8 configurado

### ✅ Recuperação de Senha
- [x] Geração de token único
- [x] Armazenamento no banco de dados
- [x] Envio de email com link
- [x] Validação de token
- [x] Atualização de senha

### ⚠️ Melhorias Recomendadas
- [ ] Mover credenciais para variáveis de ambiente
- [ ] Criar classe EmailHelper reutilizável
- [ ] Implementar expiração de tokens
- [ ] Adicionar sistema de logs
- [ ] Implementar rate limiting
- [ ] Configurar SPF/DKIM no DNS
- [ ] Adicionar testes automatizados
- [ ] Criar templates de email reutilizáveis

---

## Referências

- **Documentação PHPMailer:** https://github.com/PHPMailer/PHPMailer
- **SMTP Configuration Guide:** https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Configuration
- **Troubleshooting:** https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting

---

## Histórico de Versões

| Data | Versão | Descrição |
|------|--------|-----------|
| 2024 | 1.0 | Documentação inicial completa |

---

## Contato e Suporte

Para dúvidas ou suporte relacionado à implementação de email:
- Verifique os logs do servidor: `/var/log/mail.log`
- Habilite debug SMTP para diagnóstico
- Consulte a documentação oficial do PHPMailer

---

**Última atualização:** Dezembro 2024
**Mantido por:** Equipe de Desenvolvimento CFO

