<!DOCTYPE html>
<html>
<head>
    <title>Teste Inserção AJAX</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>Teste do Endpoint de Inserção</h1>
    
    <button onclick="testarInserir()">Testar Inserção</button>
    <button onclick="verificarLog()">Ver Log</button>
    
    <div id="resultado" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
    function testarInserir() {
        const dados = {
            uf: 'TS',
            nome_autoridade_oficial_lai: 'Teste Autoridade',
            email_autoridade_lai: 'teste@example.com'
        };
        
        document.getElementById('resultado').innerHTML = 'Enviando...';
        
        fetch('/ajax_inserir_registro', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        })
        .then(response => {
            console.log('Status:', response.status);
            console.log('Content-Type:', response.headers.get('content-type'));
            
            return response.text().then(text => {
                console.log('Texto da resposta:', text);
                
                document.getElementById('resultado').innerHTML = `
                    <strong>Status:</strong> ${response.status}<br>
                    <strong>Content-Type:</strong> ${response.headers.get('content-type')}<br>
                    <strong>Resposta:</strong><br>
                    <pre>${text}</pre>
                `;
                
                try {
                    const json = JSON.parse(text);
                    console.log('JSON:', json);
                    return json;
                } catch (e) {
                    console.error('Erro JSON:', e);
                    throw new Error('JSON inválido');
                }
            });
        })
        .then(resp => {
            console.log('Sucesso:', resp);
        })
        .catch(err => {
            console.error('Erro:', err);
            document.getElementById('resultado').innerHTML += `<br><strong>Erro:</strong> ${err.message}`;
        });
    }
    
    function verificarLog() {
        fetch('/services/relatorios/debug_ajax.txt')
        .then(response => response.text())
        .then(text => {
            document.getElementById('resultado').innerHTML = `
                <strong>Log de Debug:</strong><br>
                <pre style="max-height: 300px; overflow-y: auto;">${text}</pre>
            `;
        })
        .catch(err => {
            document.getElementById('resultado').innerHTML = `Erro ao ler log: ${err.message}`;
        });
    }
    </script>
</body>
</html> 