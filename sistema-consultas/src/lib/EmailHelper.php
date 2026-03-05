<?php
namespace Cfo\SisConsultas\lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe Helper para envio de emails
 * 
 * Utiliza as configurações do arquivo email.config.php
 * Permite envio de emails de forma centralizada e configurável
 */
class EmailHelper {
    
    private $mail;
    private $config;
    
    /**
     * Construtor - Carrega configurações e inicializa PHPMailer
     */
    public function __construct() {
        // Carrega configurações do arquivo
        $configPath = realpath(dirname(__FILE__, 2)) . '/config/email.config.php';
        $this->config = require $configPath;
        
        // Inicializa PHPMailer
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * Configura o PHPMailer com as configurações do arquivo
     */
    private function configureSMTP() {
        $smtp = $this->config['smtp'];
        $from = $this->config['from'];
        
        // Configurações básicas
        $this->mail->CharSet = $this->config['charset'];
        $this->mail->Encoding = $this->config['encoding'];
        $this->mail->isSMTP();
        
        // Configurações do servidor SMTP
        $this->mail->Host = $smtp['host'];
        $this->mail->SMTPAuth = $smtp['auth'];
        $this->mail->Username = $smtp['username'];
        $this->mail->Password = $smtp['password'];
        
        // Configuração de criptografia
        if ($smtp['encryption'] === 'ssl') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['encryption'] === 'tls') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $this->mail->Port = $smtp['port'];
        
        // Configurações do remetente
        $this->mail->setFrom($from['email'], $from['name']);
        
        // Configurações de debug
        if ($this->config['debug']) {
            $this->mail->SMTPDebug = $this->config['debug_level'];
        }
        
        // Timeout
        $this->mail->Timeout = $this->config['timeout'];
        
        // Opções SSL (se configuradas)
        if (isset($this->config['ssl_options'])) {
            $this->mail->SMTPOptions = [
                'ssl' => $this->config['ssl_options']
            ];
        }
    }
    
    /**
     * Envia email de recuperação de senha
     * 
     * @param string $email Email do destinatário
     * @param string $token Token de recuperação
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendPasswordRecovery($email, $token) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Recuperar senha - Sistema Consultas CFO';
            
            $baseUrl = $this->config['recovery_base_url'];
            $recoveryUrl = "{$baseUrl}/recuperar-senha?email={$email}&token={$token}";
            
            $this->mail->Body = $this->getPasswordRecoveryTemplate($email, $token, $recoveryUrl);
            
            $this->mail->send();
            return [
                'success' => true, 
                'message' => "Email de recuperação enviado com sucesso para {$email}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => "Erro ao enviar email: {$this->mail->ErrorInfo}"
            ];
        }
    }
    
    /**
     * Template HTML para email de recuperação de senha
     */
    private function getPasswordRecoveryTemplate($email, $token, $recoveryUrl) {
        $baseUrl = $this->config['recovery_base_url'];
        return '
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #0066cc;">Recuperação de Senha</h2>
                <p>Este é um email enviado automaticamente pelo <strong>Sistema Consultas do CFO.</strong></p>
                <p>Foi realizada uma solicitação de alteração de senha na conta cadastrada no e-mail: <u>' . htmlspecialchars($email) . '</u></p>
                <p><strong>Seu ID para alteração do password:</strong> <b style="font-size: 18px; color: #0066cc;">' . htmlspecialchars($token) . '</b></p>
                <p>Clique no link abaixo para completar a alteração da senha:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="' . htmlspecialchars($recoveryUrl) . '" 
                       style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        Redefinir Senha
                    </a>
                </p>
                <p style="font-size: 12px; color: #666;">Ou copie e cole o link no navegador:</p>
                <p style="font-size: 12px; color: #666; word-break: break-all;">' . htmlspecialchars($recoveryUrl) . '</p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="font-size: 12px; color: #999;">
                    Caso você não tenha solicitado a alteração da senha, apenas ignore este e-mail.
                </p>
                <p><strong>Conselho Federal de Odontologia - CFO</strong></p>
                <p><a href="' . htmlspecialchars($baseUrl) . '" style="color: #0066cc;">' . htmlspecialchars($baseUrl) . '</a></p>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Envia email genérico
     * 
     * @param string $to Email do destinatário
     * @param string $subject Assunto do email
     * @param string $body Corpo do email (HTML ou texto)
     * @param bool $isHTML Se o corpo é HTML (padrão: true)
     * @return array ['success' => bool, 'message' => string]
     */
    public function send($to, $subject, $body, $isHTML = true) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            // Se for HTML, adiciona texto alternativo
            if ($isHTML) {
                $this->mail->AltBody = strip_tags($body);
            }
            
            $this->mail->send();
            return [
                'success' => true, 
                'message' => "Email enviado com sucesso para {$to}"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => "Erro ao enviar email: {$this->mail->ErrorInfo}"
            ];
        }
    }
    
    /**
     * Adiciona anexo ao email
     * 
     * @param string $path Caminho do arquivo
     * @param string $name Nome do arquivo (opcional)
     * @return $this
     */
    public function addAttachment($path, $name = '') {
        $this->mail->addAttachment($path, $name);
        return $this;
    }
    
    /**
     * Adiciona destinatário em cópia
     * 
     * @param string $email Email do destinatário
     * @param string $name Nome do destinatário (opcional)
     * @return $this
     */
    public function addCC($email, $name = '') {
        $this->mail->addCC($email, $name);
        return $this;
    }
    
    /**
     * Adiciona destinatário em cópia oculta
     * 
     * @param string $email Email do destinatário
     * @param string $name Nome do destinatário (opcional)
     * @return $this
     */
    public function addBCC($email, $name = '') {
        $this->mail->addBCC($email, $name);
        return $this;
    }
    
    /**
     * Retorna a instância do PHPMailer para configurações avançadas
     * 
     * @return PHPMailer
     */
    public function getMailer() {
        return $this->mail;
    }
}

