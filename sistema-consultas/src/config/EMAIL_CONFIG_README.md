# Configuração de Email - Guia do Agente Cursor

## 📋 Visão Geral

Este arquivo (`email.config.php`) contém todas as configurações SMTP para envio de emails no sistema. O agente do Cursor pode editar este arquivo diretamente para alterar as configurações, sem precisar usar o terminal.

## 🔧 Como Editar as Configurações

### Localização do Arquivo
```
src/config/email.config.php
```

### Estrutura das Configurações

O arquivo retorna um array PHP com as seguintes seções:

#### 1. Configurações SMTP (`smtp`)
```php
'smtp' => [
    'host' => 'email-ssl.com.br',        // Servidor SMTP
    'port' => 465,                        // Porta (465 para SSL, 587 para TLS)
    'encryption' => 'ssl',                // 'ssl' ou 'tls'
    'auth' => true,                       // Habilitar autenticação
    'username' => 'sistema-consultas@cfo.org.br',  // Usuário SMTP
    'password' => 'cfo.1234.CFO',         // Senha SMTP
],
```

#### 2. Remetente (`from`)
```php
'from' => [
    'email' => 'sistema-consultas@cfo.org.br',  // Email remetente
    'name' => 'Sistema Consultas CFO',          // Nome do remetente
],
```

#### 3. Configurações Gerais
```php
'charset' => 'UTF-8',           // Charset para caracteres especiais
'encoding' => 'base64',         // Encoding do conteúdo
'timeout' => 10,                // Timeout da conexão (segundos)
'recovery_base_url' => 'https://consultas.cfo.org.br',  // URL base para links
```

#### 4. Debug
```php
'debug' => false,               // true para habilitar debug SMTP
'debug_level' => 0,             // 0=off, 1=cliente, 2=servidor, 3=conexão, 4=baixo nível
```

#### 5. Opções SSL/TLS
```php
'ssl_options' => [
    'verify_peer' => true,      // Verificar certificado do peer
    'verify_peer_name' => true, // Verificar nome do peer
    'allow_self_signed' => false, // Permitir certificados auto-assinados
],
```

## 📝 Exemplos de Edição

### Exemplo 1: Alterar Senha SMTP
```php
'smtp' => [
    'password' => 'nova_senha_aqui',  // Altere apenas esta linha
    // ... outras configurações permanecem iguais
],
```

### Exemplo 2: Alterar Servidor SMTP
```php
'smtp' => [
    'host' => 'novo-servidor.com.br',
    'port' => 587,
    'encryption' => 'tls',  // Mude de 'ssl' para 'tls' se necessário
    // ... outras configurações
],
```

### Exemplo 3: Habilitar Debug (para solução de problemas)
```php
'debug' => true,
'debug_level' => 2,  // Mostra mensagens detalhadas do servidor
```

### Exemplo 4: Alterar Remetente
```php
'from' => [
    'email' => 'novo-email@cfo.org.br',
    'name' => 'Novo Nome do Sistema',
],
```

## 🔒 Segurança

⚠️ **IMPORTANTE**: 
- Não commite este arquivo com credenciais reais em repositórios públicos
- Em produção, considere usar variáveis de ambiente
- Mantenha as senhas seguras e não compartilhe

## 🚀 Como Usar no Código

A classe `EmailHelper` carrega automaticamente essas configurações:

```php
use Cfo\SisConsultas\lib\EmailHelper;

$emailHelper = new EmailHelper();
$result = $emailHelper->sendPasswordRecovery($email, $token);
```

## ✅ Checklist de Configuração

- [ ] Servidor SMTP configurado corretamente
- [ ] Porta e criptografia corretas (SSL=465, TLS=587)
- [ ] Credenciais de autenticação válidas
- [ ] Email remetente configurado
- [ ] URL base para links de recuperação correta
- [ ] Debug desabilitado em produção
- [ ] Opções SSL configuradas adequadamente

## 🐛 Solução de Problemas

### Email não está sendo enviado
1. Verifique se as credenciais estão corretas
2. Habilite debug temporariamente: `'debug' => true, 'debug_level' => 2`
3. Verifique se a porta não está bloqueada pelo firewall

### Erro de autenticação
1. Confirme usuário e senha SMTP
2. Verifique se a autenticação está habilitada: `'auth' => true`

### Erro de conexão SSL/TLS
1. Para desenvolvimento/testes, pode desabilitar verificação:
   ```php
   'ssl_options' => [
       'verify_peer' => false,
       'verify_peer_name' => false,
       'allow_self_signed' => true,
   ],
   ```
2. ⚠️ **NÃO use isso em produção!**

## 📚 Arquivos Relacionados

- `src/lib/EmailHelper.php` - Classe que utiliza estas configurações
- `src/views/senhaRecuperar.php` - Exemplo de uso da classe
- `IMPLEMENTACAO_EMAIL.md` - Documentação completa do sistema de email

---

**Última atualização**: Dezembro 2024

