<?php

class ReceitaFederalAPI {
    public function verificarCPF($cpf) {
        // Simulação de chamada à API da Receita Federal
        // No mundo real, aqui seria a requisição via curl ou similar para a API
        // Este exemplo apenas retorna dados fictícios.
        return [
            'nome' => 'Nome Exemplo',
            'situacao' => 'Regular',
            'data_nascimento' => '1980-01-01'
        ];
    }
}

function verificarLimiteConsultas($cro_id) {
    // Conexão com o banco de dados
    $db = Database3::getInstance();
    $con = $db->getConnection();

    // Verificar o número de consultas realizadas pelo CRO
    $query = "SELECT COUNT(*) as total FROM logs_consultas WHERE cro_id = :cro_id";
    $stmt = $con->prepare($query);
    $stmt->bindParam(':cro_id', $cro_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar se atingiu o limite de 300 consultas para CRO
    if ($result['total'] >= 300) {
        return false;
    }

    return true;
}

function registrarConsulta($cro_id, $cpf) {
    // Conexão com o banco de dados
    $db = Database3::getInstance();
    $con = $db->getConnection();

    // Registrar a consulta
    $query = "INSERT INTO logs_consultas (cro_id, cpf, data_consulta) VALUES (:cro_id, :cpf, NOW())";
    $stmt = $con->prepare($query);
    $stmt->bindParam(':cro_id', $cro_id);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
}

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database3::getInstance();
    $con = $db->getConnection();
    $api = new ReceitaFederalAPI();

    if (isset($_POST['nomeCpf'])) {
        // Verificação individual
        $nomeCpf = trim($_POST['nomeCpf']);
        $cro_id = $_POST['cro_id']; // Pegar o CRO atual (usuário logado)

        // Verificar se o CRO tem permissão (300 consultas)
        if (verificarLimiteConsultas($cro_id)) {
            // Verificar se o CPF ou nome está na base do CFO
            $query = "SELECT * FROM base_cfo WHERE cpf = :cpf OR nome = :nome";
            $stmt = $con->prepare($query);
            $stmt->bindParam(':cpf', $nomeCpf);
            $stmt->bindParam(':nome', $nomeCpf);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Consultar API da Receita Federal
                $dadosReceita = $api->verificarCPF($result['cpf']);
                
                if ($dadosReceita) {
                    registrarConsulta($cro_id, $result['cpf']);
                    echo json_encode($dadosReceita);
                } else {
                    echo "Erro ao consultar a Receita Federal.";
                }
            } else {
                echo "CPF ou Nome não encontrado na base do CFO.";
            }
        } else {
            echo "Limite de consultas atingido para o CRO.";
        }

    } elseif (isset($_FILES['arquivoCSV'])) {
        // Verificação em lote
        $file = $_FILES['arquivoCSV']['tmp_name'];
        $csv = array_map('str_getcsv', file($file));
        $cro_id = $_POST['cro_id']; // Pegar o CRO atual

        foreach ($csv as $linha) {
            $cpfOuNome = $linha[0]; // Supondo que o CPF ou nome está na primeira coluna
            
            if (verificarLimiteConsultas($cro_id)) {
                // Verificar se o CPF ou nome está na base do CFO
                $query = "SELECT * FROM base_cfo WHERE cpf = :cpf OR nome = :nome";
                $stmt = $con->prepare($query);
                $stmt->bindParam(':cpf', $cpfOuNome);
                $stmt->bindParam(':nome', $cpfOuNome);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    // Consultar a API da Receita Federal
                    $dadosReceita = $api->verificarCPF($result['cpf']);
                    
                    if ($dadosReceita) {
                        registrarConsulta($cro_id, $result['cpf']);
                        // Processar o resultado (exibir ou salvar)
                        echo "Dados consultados: " . json_encode($dadosReceita) . "<br>";
                    } else {
                        echo "Erro ao consultar a Receita Federal para o CPF: $cpfOuNome<br>";
                    }
                } else {
                    echo "CPF ou Nome $cpfOuNome não encontrado na base do CFO.<br>";
                }
            } else {
                echo "Limite de consultas atingido para o CRO.<br>";
                break;
            }
        }
    }
}
?>

<!-- HTML Formulário -->
<h5>Verificação Individual</h5>
<form method="POST" action="">
    <input type="hidden" name="cro_id" value="12345"> <!-- ID do CRO logado -->
    <label for="nomeCpf">Nome ou CPF:</label>
    <input type="text" id="nomeCpf" name="nomeCpf" required>
    <button type="submit">Verificar</button>
</form>

<hr>

<h5>Verificação em Lote</h5>
<form method="POST" enctype="multipart/form-data" action="">
    <input type="hidden" name="cro_id" value="12345"> <!-- ID do CRO logado -->
    <label for="arquivoCSV">Arquivo CSV:</label>
    <input type="file" id="arquivoCSV" name="arquivoCSV" required>
    <button type="submit">Verificar Lote</button>
</form>
