<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();

try {
    $db = Database1::getInstance();
    $con = $db->getConnection();
    
    echo "<h2>Teste da Tabela tbl_dados_lgpd_cros</h2>";
    
    // Verificar se a tabela existe
    $queryCheck = "SELECT COUNT(*) as total FROM information_schema.tables 
                   WHERE table_schema = 'db_sistema_consultas' 
                   AND table_name = 'tbl_dados_lgpd_cros'";
    $stmtCheck = $con->prepare($queryCheck);
    $stmtCheck->execute();
    $tableExists = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Tabela existe:</strong> " . ($tableExists['total'] > 0 ? 'SIM' : 'NÃO') . "</p>";
    
    if ($tableExists['total'] > 0) {
        // Verificar estrutura da tabela
        $queryStructure = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
                          FROM information_schema.columns 
                          WHERE table_schema = 'db_sistema_consultas' 
                          AND table_name = 'tbl_dados_lgpd_cros'
                          ORDER BY ORDINAL_POSITION";
        $stmtStructure = $con->prepare($queryStructure);
        $stmtStructure->execute();
        $columns = $stmtStructure->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Estrutura da Tabela:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Coluna</th><th>Tipo</th><th>Permite NULL</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['COLUMN_NAME'] . "</td>";
            echo "<td>" . $column['DATA_TYPE'] . "</td>";
            echo "<td>" . $column['IS_NULLABLE'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar quantidade de registros
        $queryCount = "SELECT COUNT(*) as total FROM db_sistema_consultas.tbl_dados_lgpd_cros";
        $stmtCount = $con->prepare($queryCount);
        $stmtCount->execute();
        $count = $stmtCount->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total de registros:</strong> " . $count['total'] . "</p>";
        
        // Verificar alguns registros de exemplo
        $querySample = "SELECT * FROM db_sistema_consultas.tbl_dados_lgpd_cros LIMIT 3";
        $stmtSample = $con->prepare($querySample);
        $stmtSample->execute();
        $samples = $stmtSample->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($samples)) {
            echo "<h3>Exemplos de Registros:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            
            // Cabeçalhos
            echo "<tr>";
            foreach (array_keys($samples[0]) as $header) {
                echo "<th>" . $header . "</th>";
            }
            echo "</tr>";
            
            // Dados
            foreach ($samples as $sample) {
                echo "<tr>";
                foreach ($sample as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Testar busca por ID específico
        $testId = 1;
        $queryTest = "SELECT * FROM db_sistema_consultas.tbl_dados_lgpd_cros WHERE id = :id";
        $stmtTest = $con->prepare($queryTest);
        $stmtTest->bindValue(':id', $testId, PDO::PARAM_INT);
        $stmtTest->execute();
        $testResult = $stmtTest->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Teste de Busca por ID = $testId:</h3>";
        if ($testResult) {
            echo "<p><strong>Registro encontrado:</strong> SIM</p>";
            echo "<pre>" . print_r($testResult, true) . "</pre>";
        } else {
            echo "<p><strong>Registro encontrado:</strong> NÃO</p>";
        }
        
    } else {
        echo "<p style='color: red;'><strong>ERRO:</strong> A tabela tbl_dados_lgpd_cros não existe!</p>";
    }
    
} catch (PDOException $error) {
    echo "<p style='color: red;'><strong>Erro PDO:</strong> " . $error->getMessage() . "</p>";
} catch (Exception $error) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $error->getMessage() . "</p>";
}
?> 