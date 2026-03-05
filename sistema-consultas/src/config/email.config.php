<?php
/**
 * Configurações de Email - Sistema Consultas CFO
 * 
 * Este arquivo contém todas as configurações SMTP para envio de emails.
 * O agente do Cursor pode editar este arquivo diretamente para alterar as configurações.
 * 
 * IMPORTANTE: Não commitar este arquivo com credenciais reais em repositórios públicos.
 * Considere usar variáveis de ambiente em produção.
 */

return [
    // Configurações do Servidor SMTP
    'smtp' => [
        'host' => 'email-ssl.com.br',
        'port' => 465,
        'encryption' => 'ssl', // 'ssl' ou 'tls'
        'auth' => true,
        'username' => 'sistema-consultas@cfo.org.br',
        'password' => 'cfo.1234.CFO',
    ],
    
    // Configurações do Remetente
    'from' => [
        'email' => 'sistema-consultas@cfo.org.br',
        'name' => 'Sistema Consultas CFO',
    ],
    
    // Configurações Gerais
    'charset' => 'UTF-8',
    'encoding' => 'base64',
    
    // Configurações de Debug (desabilitar em produção)
    'debug' => false, // true para habilitar debug SMTP
    'debug_level' => 0, // 0 = desabilitado, 1 = cliente, 2 = servidor, 3 = conexão, 4 = baixo nível
    
    // Timeout da conexão (em segundos)
    'timeout' => 10,
    
    // URL base para links de recuperação de senha
    'recovery_base_url' => 'https://consultas.cfo.org.br',
    
    // Configurações SSL/TLS (apenas para desenvolvimento/testes)
    'ssl_options' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ],
];

