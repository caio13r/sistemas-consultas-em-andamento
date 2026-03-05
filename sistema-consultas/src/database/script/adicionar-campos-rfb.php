<?php
/**
 * Script PHP para adicionar campos faltantes na tabela tbl_rfb_auditoria
 * Execute via navegador: http://seusite.com/adicionar-campos-rfb.php
 * OU via linha de comando: php adicionar-campos-rfb.php
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/database/Database1.php';

use Cfo\SisConsultas\database\Database1;
use PDO;
use PDOException;

$db = Database1::getInstance();
$con = $db->getConnection();

// Verificar se está sendo executado via web ou CLI
$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Adicionar Campos RFB</title></head><body>";
    echo "<h2>Adicionando campos à tabela tbl_rfb_auditoria</h2>";
}

function modificarColuna($con, $nomeCampo, $novaDefinicao, $isWeb) {
    try {
        // Verificar tamanho atual da coluna
        $query = "SELECT COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH 
                  FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'tbl_rfb_auditoria' 
                  AND COLUMN_NAME = :campo";
        $stmt = $con->prepare($query);
        $stmt->execute([':campo' => $nomeCampo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $msg = "Campo $nomeCampo não existe. Pulando modificação...";
            if ($isWeb) {
                echo "<p style='color: orange;'>$msg</p>";
            } else {
                echo "[SKIP] $msg\n";
            }
            return false;
        }
        
        // Verificar se já tem o tamanho correto
        $tamanhoAtual = $result['CHARACTER_MAXIMUM_LENGTH'] ?? null;
        if ($tamanhoAtual == 14) {
            $msg = "Campo $nomeCampo já tem tamanho correto (VARCHAR(14)). Pulando...";
            if ($isWeb) {
                echo "<p style='color: blue;'>$msg</p>";
            } else {
                echo "[SKIP] $msg\n";
            }
            return true;
        }
        
        $query = "ALTER TABLE tbl_rfb_auditoria MODIFY COLUMN $nomeCampo $novaDefinicao";
        $con->exec($query);
        $msg = "✓ Campo $nomeCampo modificado para $novaDefinicao com sucesso!";
        if ($isWeb) {
            echo "<p style='color: green;'>$msg</p>";
        } else {
            echo "[OK] $msg\n";
        }
        return true;
    } catch (PDOException $e) {
        $msg = "✗ Erro ao modificar campo $nomeCampo: " . $e->getMessage();
        if ($isWeb) {
            echo "<p style='color: red;'>$msg</p>";
        } else {
            echo "[ERRO] $msg\n";
        }
        return false;
    }
}

function campoExiste($con, $nomeCampo) {
    try {
        $query = "SELECT COUNT(*) as total 
                  FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'tbl_rfb_auditoria' 
                  AND COLUMN_NAME = :campo";
        $stmt = $con->prepare($query);
        $stmt->execute([':campo' => $nomeCampo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function adicionarCampo($con, $nomeCampo, $definicao, $posicao, $isWeb) {
    if (campoExiste($con, $nomeCampo)) {
        $msg = "Campo $nomeCampo já existe. Pulando...";
        if ($isWeb) {
            echo "<p style='color: orange;'>$msg</p>";
        } else {
            echo "[SKIP] $msg\n";
        }
        return true;
    }
    
    try {
        $query = "ALTER TABLE tbl_rfb_auditoria ADD COLUMN $nomeCampo $definicao $posicao";
        $con->exec($query);
        $msg = "✓ Campo $nomeCampo adicionado com sucesso!";
        if ($isWeb) {
            echo "<p style='color: green;'>$msg</p>";
        } else {
            echo "[OK] $msg\n";
        }
        return true;
    } catch (PDOException $e) {
        $msg = "✗ Erro ao adicionar campo $nomeCampo: " . $e->getMessage();
        if ($isWeb) {
            echo "<p style='color: red;'>$msg</p>";
        } else {
            echo "[ERRO] $msg\n";
        }
        return false;
    }
}

function criarIndice($con, $nomeIndice, $coluna, $isWeb) {
    try {
        $query = "SELECT COUNT(*) as total 
                  FROM information_schema.STATISTICS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'tbl_rfb_auditoria' 
                  AND INDEX_NAME = :indice";
        $stmt = $con->prepare($query);
        $stmt->execute([':indice' => $nomeIndice]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $msg = "Índice $nomeIndice já existe. Pulando...";
            if ($isWeb) {
                echo "<p style='color: orange;'>$msg</p>";
            } else {
                echo "[SKIP] $msg\n";
            }
            return true;
        }
        
        $query = "CREATE INDEX $nomeIndice ON tbl_rfb_auditoria($coluna)";
        $con->exec($query);
        $msg = "✓ Índice $nomeIndice criado com sucesso!";
        if ($isWeb) {
            echo "<p style='color: green;'>$msg</p>";
        } else {
            echo "[OK] $msg\n";
        }
        return true;
    } catch (PDOException $e) {
        $msg = "✗ Erro ao criar índice $nomeIndice: " . $e->getMessage();
        if ($isWeb) {
            echo "<p style='color: red;'>$msg</p>";
        } else {
            echo "[ERRO] $msg\n";
        }
        return false;
    }
}

// IMPORTANTE: Modificar tamanho de cpf_consultado primeiro
if ($isWeb) echo "<h3>MODIFICAÇÕES DE COLUNAS EXISTENTES</h3>";
modificarColuna($con, 'cpf_consultado', 'VARCHAR(14) NOT NULL', $isWeb);

// Campos comuns
if ($isWeb) echo "<h3>CAMPOS COMUNS</h3>";
adicionarCampo($con, 'documento_consultado', 'VARCHAR(14)', 'AFTER tipo_consulta', $isWeb);

// Campos CPF
if ($isWeb) echo "<h3>CAMPOS ESPECÍFICOS CPF</h3>";
adicionarCampo($con, 'cpf_nome', 'VARCHAR(255)', 'AFTER nome_consultado', $isWeb);
adicionarCampo($con, 'cpf_data_nascimento', 'DATE', 'AFTER cpf_nome', $isWeb);
adicionarCampo($con, 'cpf_logradouro', 'VARCHAR(255)', 'AFTER cpf_data_nascimento', $isWeb);
adicionarCampo($con, 'cpf_numero_logradouro', 'VARCHAR(20)', 'AFTER cpf_logradouro', $isWeb);
adicionarCampo($con, 'cpf_bairro', 'VARCHAR(100)', 'AFTER cpf_numero_logradouro', $isWeb);
adicionarCampo($con, 'cpf_cep', 'VARCHAR(10)', 'AFTER cpf_bairro', $isWeb);
adicionarCampo($con, 'cpf_uf', 'VARCHAR(2)', 'AFTER cpf_cep', $isWeb);
adicionarCampo($con, 'cpf_municipio', 'VARCHAR(100)', 'AFTER cpf_uf', $isWeb);
adicionarCampo($con, 'cpf_telefone', 'VARCHAR(20)', 'AFTER cpf_municipio', $isWeb);

// Campos CNPJ
if ($isWeb) echo "<h3>CAMPOS ESPECÍFICOS CNPJ</h3>";
adicionarCampo($con, 'cnpj_razao_social', 'VARCHAR(255)', 'AFTER cpf_telefone', $isWeb);
adicionarCampo($con, 'cnpj_nome_fantasia', 'VARCHAR(255)', 'AFTER cnpj_razao_social', $isWeb);
adicionarCampo($con, 'cnpj_logradouro', 'VARCHAR(255)', 'AFTER cnpj_nome_fantasia', $isWeb);
adicionarCampo($con, 'cnpj_numero_logradouro', 'VARCHAR(20)', 'AFTER cnpj_logradouro', $isWeb);
adicionarCampo($con, 'cnpj_bairro', 'VARCHAR(100)', 'AFTER cnpj_numero_logradouro', $isWeb);
adicionarCampo($con, 'cnpj_cep', 'VARCHAR(10)', 'AFTER cnpj_bairro', $isWeb);
adicionarCampo($con, 'cnpj_uf', 'VARCHAR(2)', 'AFTER cnpj_cep', $isWeb);
adicionarCampo($con, 'cnpj_municipio', 'VARCHAR(100)', 'AFTER cnpj_uf', $isWeb);
adicionarCampo($con, 'cnpj_cnae_fiscal', 'VARCHAR(10)', 'AFTER cnpj_municipio', $isWeb);
adicionarCampo($con, 'cnpj_capital_social', 'DECIMAL(15,2)', 'AFTER cnpj_cnae_fiscal', $isWeb);

// Índices
if ($isWeb) echo "<h3>ÍNDICES</h3>";
criarIndice($con, 'idx_documento_consultado', 'documento_consultado', $isWeb);
criarIndice($con, 'idx_tipo_consulta', 'tipo_consulta', $isWeb);

if ($isWeb) {
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ Processo concluído!</h3>";
    echo "<p>Execute <code>DESC tbl_rfb_auditoria;</code> no MySQL para verificar a estrutura final.</p>";
    echo "</body></html>";
} else {
    echo "\n[CONCLUÍDO] Processo finalizado!\n";
}

