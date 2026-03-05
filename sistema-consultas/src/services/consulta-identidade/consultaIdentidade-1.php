<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Cfo\SisConsultas\lib\Session;
Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CI1acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tipoConsulta = $_GET['tipoConsulta'] ?? '';
$searchType = $_GET['searchType'] ?? 'name';
$searchValue = $_GET['searchValue'] ?? '';
$page_number = $_GET['page_number'] ?? 1;
$page_amount = $_GET['page_amount'] ?? 100;

$token = $_ENV['API_TOKEN'] ?? null;
if (!$token) {
    die("Token da API não configurado.");
}

// Obter URL base da API a partir de variável de ambiente
$API_BASE_URL = $_ENV['API_IDENTITY_URL'] ?? 'http://192.168.161.165:8082';

// Configurações do servidor de foto (para buscar data_criacao quando não vier do servidor 165)
$FOTO_SERVER_USUARIO = $_ENV['FOTO_SERVER_USUARIO'] ?? 'CFO';
$FOTO_SERVER_CHAVE = $_ENV['FOTO_SERVER_CHAVE'] ?? 'f04fc70d6769cab53e799947ba7b11890883ba26d0a217880ecc5972a77f3f51';

// Mapeamento de CRO para servidor de foto
// Baseado no documento fornecido: http://[IP_DO_ESTADO]/service/v1/consulta/solicitacao/cpf
$FOTO_SERVERS = [
    'MG' => 'http://192.168.161.128',
    'SP' => 'http://192.168.161.128', // Ajustar conforme necessário
    // Adicionar outros estados conforme necessário
    // Ver lista em: /home/gerti/servidores-por-estado.txt (no servidor)
];

// Cache simples para evitar múltiplas requisições para o mesmo CPF na mesma execução
$dataCriacaoCache = [];

// Função para buscar data de criação da foto no servidor de foto
// Usa o endpoint: POST http://[IP]/service/v1/consulta/solicitacao/cpf
function buscarDataCriacaoFoto($cpf, $cro, $fotoServers, $usuario, $chave, &$cache) {
    if (empty($cpf)) {
        return '';
    }
    
    // Remover formatação do CPF (apenas números)
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    
    if (empty($cpfLimpo)) {
        return '';
    }
    
    // Verificar cache
    $cacheKey = $cpfLimpo . '_' . $cro;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    // Determinar servidor de foto baseado no CRO
    $fotoServerUrl = $fotoServers[$cro] ?? $fotoServers['MG'] ?? 'http://192.168.161.128';
    
    $url = $fotoServerUrl . '/service/v1/consulta/solicitacao/cpf';
    
    $payload = json_encode([
        'cpf' => $cpfLimpo,
        'credencial' => [
            'usuario' => $usuario,
            'chave' => $chave
        ]
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError || $httpCode !== 200) {
        // Não logar erro para não poluir logs - apenas retornar vazio
        $cache[$cacheKey] = '';
        return '';
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['resultado']) && is_array($data['resultado']) && !empty($data['resultado'])) {
        // Pegar a foto mais recente (última do array, já ordenada por data_criacao)
        $fotoMaisRecente = end($data['resultado']);
        if (isset($fotoMaisRecente['data_criacao']) && !empty($fotoMaisRecente['data_criacao'])) {
            $dataCriacao = $fotoMaisRecente['data_criacao'];
            // Armazenar no cache
            $cache[$cacheKey] = $dataCriacao;
            return $dataCriacao;
        }
    }
    
    $cache[$cacheKey] = '';
    return '';
}

$endpoints = [
    'name' => $API_BASE_URL . '/api/consulta/identidade/nome',
    'cpf'  => $API_BASE_URL . '/api/consulta/identidade/cpf',
    'ar'   => $API_BASE_URL . '/api/consulta/identidade/ar'
];

if (!array_key_exists($searchType, $endpoints)) {
    die("Tipo de pesquisa inválido.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Identidade</title>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body { font-family: Arial, sans-serif; }
        .card { margin-top: 20px; }
        .table-responsive { margin-top: 20px; }
        img { max-width: 80px; border-radius: 5px; }
        
        /* Estilos para impressão */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-content, .print-content * {
                visibility: visible;
            }
            .print-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .print-header {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }
            .print-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .print-table th,
            .print-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            .print-table th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            .print-photo {
                max-width: 150px;
                max-height: 150px;
            }
        }
    </style>

    <script>
        function consultarRastreamento(id) {
            let token = "<?= $token ?>";
            let url = `https://id.cfo.org.br/api/consulta/rastreamento/identidade?token=${token}&id=${id}`;

            Swal.fire({
                title: 'Consultando rastreamento...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.get(url, function (response) {
                if (response && response.rastreamento && response.rastreamento.length > 0) {
                    let html = `
                        <div style="overflow-x:auto;">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Unidade</th>
                                    <th>Cidade</th>
                                    <th>Estado</th>
                                    <th>Descrição</th>
                                    <th>Data do Evento</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    response.rastreamento.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.unidade}</td>
                                <td>${item.cidade}</td>
                                <td>${item.estado}</td>
                                <td>${item.descricao}</td>
                                <td>${item.data_evento}</td>
                            </tr>`;
                    });
                    html += '</tbody></table></div>';

                    Swal.fire({
                        title: 'Rastreamento',
                        html: html,
                        width: '80%',
                        confirmButtonText: 'Fechar'
                    });
                } else {
                    Swal.fire('Sem dados', 'Nenhum evento de rastreamento encontrado.', 'warning');
                }
            }).fail(function () {
                Swal.fire('Erro', 'Erro ao consultar a API.', 'error');
            });
        }

        // Função para obter foto do elemento da tabela
        function getFotoFromTable(buttonElement) {
            // Encontrar a linha da tabela (tr) que contém o botão
            let row = buttonElement.closest('tr');
            if (row) {
                let img = row.querySelector('img.foto-identidade');
                if (img && img.dataset.foto) {
                    return img.dataset.foto;
                }
            }
            return '';
        }

        // Função para obter dados da linha da tabela como fallback
        function getDataFromTableRow(buttonElement) {
            let row = buttonElement.closest('tr');
            if (!row) {
                console.log('getDataFromTableRow: Linha não encontrada');
                return {};
            }
            
            let cells = row.querySelectorAll('td');
            console.log('getDataFromTableRow: Número de células encontradas:', cells.length);
            
            if (cells.length < 8) {
                console.log('getDataFromTableRow: Número insuficiente de células');
                return {};
            }
            
            // Extrair dados das células da tabela (baseado na ordem das colunas)
            // ID, Nome, CPF, AR, CRO, Categoria, Inscricao, QR Code, Foto, Ações
            let data = {
                id: cells[0] ? cells[0].textContent.trim() : '',
                nome: cells[1] ? cells[1].textContent.trim() : '',
                cpf: cells[2] ? cells[2].textContent.trim() : '',
                ar: cells[3] ? cells[3].textContent.trim() : '',
                cro: cells[4] ? cells[4].textContent.trim() : '',
                categoria: cells[5] ? cells[5].textContent.trim() : '',
                inscricao: cells[6] ? cells[6].textContent.trim() : '',
                qr_code: cells[7] ? (cells[7].querySelector('a') ? cells[7].querySelector('a').href : cells[7].textContent.trim()) : ''
            };
            
            console.log('getDataFromTableRow: Dados extraídos:', data);
            return data;
        }

        // Função para escapar HTML e prevenir XSS
        function escapeHtml(text) {
            if (!text) return '';
            let map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function imprimirIdentidade(buttonElement) {
            let token = "<?= $token ?>";
            
            // Usar dataset para obter dados (mais confiável que getAttribute)
            // dataset converte data-nome para nome, data-cpf para cpf, etc.
            let id = buttonElement.dataset.id || buttonElement.getAttribute('data-id') || '';
            let itemData = {
                id: id,
                nome: buttonElement.dataset.nome || buttonElement.getAttribute('data-nome') || '',
                cpf: buttonElement.dataset.cpf || buttonElement.getAttribute('data-cpf') || '',
                ar: buttonElement.dataset.ar || buttonElement.getAttribute('data-ar') || '',
                cro: buttonElement.dataset.cro || buttonElement.getAttribute('data-cro') || '',
                categoria: buttonElement.dataset.categoria || buttonElement.getAttribute('data-categoria') || '',
                inscricao: buttonElement.dataset.inscricao || buttonElement.getAttribute('data-inscricao') || '',
                qr_code: buttonElement.dataset.qrcode || buttonElement.getAttribute('data-qrcode') || '',
                foto: getFotoFromTable(buttonElement),
                data_criacao: buttonElement.dataset.criacao || buttonElement.getAttribute('data-criacao') || ''
            };
            
            // Se os dados estiverem vazios, tentar obter da linha da tabela como fallback
            if (!itemData.id || !itemData.nome || !itemData.cpf || !itemData.ar) {
                console.log('Dados vazios detectados, tentando obter da tabela...');
                let tableData = getDataFromTableRow(buttonElement);
                console.log('Dados obtidos da tabela:', tableData);
                
                // Usar dados da tabela se os dados do botão estiverem vazios
                itemData.id = itemData.id || tableData.id || '';
                itemData.nome = itemData.nome || tableData.nome || '';
                itemData.cpf = itemData.cpf || tableData.cpf || '';
                itemData.ar = itemData.ar || tableData.ar || '';
                itemData.cro = itemData.cro || tableData.cro || '';
                itemData.categoria = itemData.categoria || tableData.categoria || '';
                itemData.inscricao = itemData.inscricao || tableData.inscricao || '';
                itemData.qr_code = itemData.qr_code || tableData.qr_code || '';
                
                console.log('Dados após fallback:', itemData);
            }
            
            // Debug: Log dos dados coletados
            console.log('=== DEBUG: Dados coletados ===');
            console.log('Botão elemento:', buttonElement);
            console.log('Dataset completo:', buttonElement.dataset);
            console.log('Dados coletados do botão:', itemData);
            console.log('ID:', itemData.id);
            console.log('Nome:', itemData.nome);
            console.log('CPF:', itemData.cpf);
            console.log('AR:', itemData.ar);
            console.log('CRO:', itemData.cro);
            console.log('Categoria:', itemData.categoria);
            console.log('Inscrição:', itemData.inscricao);
            
            // Verificar se os dados essenciais estão presentes
            if (!itemData.id || (!itemData.nome && !itemData.cpf && !itemData.ar)) {
                console.error('Dados insuficientes para gerar PDF:', itemData);
                console.error('HTML do botão:', buttonElement.outerHTML);
                Swal.fire({
                    title: 'Erro',
                    html: 'Dados insuficientes para gerar o PDF.<br><br>' +
                          '<strong>Dados coletados:</strong><br>' +
                          'ID: ' + (itemData.id || 'VAZIO') + '<br>' +
                          'Nome: ' + (itemData.nome || 'VAZIO') + '<br>' +
                          'CPF: ' + (itemData.cpf || 'VAZIO') + '<br>' +
                          'AR: ' + (itemData.ar || 'VAZIO') + '<br><br>' +
                          'Por favor, verifique o console (F12) para mais detalhes.',
                    icon: 'error',
                    width: '600px'
                });
                return;
            }
            
            let url = 'https://id.cfo.org.br/api/consulta/rastreamento/identidade?token=' + encodeURIComponent(token) + '&id=' + encodeURIComponent(id);

            // Mostrar loading
            Swal.fire({
                title: 'Gerando PDF...',
                text: 'Carregando dados e gerando PDF',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Buscar dados de rastreamento
            $.get(url).done(function (rastreamentoResponse) {
                // Debug: Verificar dados antes de criar HTML
                console.log('=== DEBUG: Antes de criar HTML do PDF ===');
                console.log('itemData completo:', itemData);
                console.log('itemData.id:', itemData.id, 'Tipo:', typeof itemData.id);
                console.log('itemData.nome:', itemData.nome, 'Tipo:', typeof itemData.nome);
                console.log('itemData.cpf:', itemData.cpf, 'Tipo:', typeof itemData.cpf);
                
                // Escapar dados para evitar problemas com caracteres especiais
                let dataConsulta = new Date().toLocaleString('pt-BR');
                let idEscapado = escapeHtml(String(itemData.id || ''));
                let nomeEscapado = escapeHtml(String(itemData.nome || ''));
                let cpfEscapado = escapeHtml(String(itemData.cpf || ''));
                let arEscapado = escapeHtml(String(itemData.ar || ''));
                let croEscapado = escapeHtml(String(itemData.cro || ''));
                let categoriaEscapada = escapeHtml(String(itemData.categoria || ''));
                let inscricaoEscapada = escapeHtml(String(itemData.inscricao || ''));
                let qrCodeEscapado = escapeHtml(String(itemData.qr_code || ''));
                
                console.log('Dados escapados:');
                console.log('idEscapado:', idEscapado);
                console.log('nomeEscapado:', nomeEscapado);
                console.log('cpfEscapado:', cpfEscapado);
                
                // Criar conteúdo HTML para PDF usando concatenação segura
                let htmlContent = '<div style="font-family: Arial, sans-serif; padding: 20px;">' +
                    '<div style="border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px;">' +
                    '<h2 style="text-align: center; margin-bottom: 10px; font-size: 18px;">CONSULTA DE IDENTIDADE</h2>' +
                    '<p style="text-align: center; margin: 0; font-size: 12px;">Data da Consulta: ' + dataConsulta + '</p>' +
                    '</div>' +
                    '<div style="margin-bottom: 30px;">' +
                    '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">DADOS DA IDENTIDADE</h3>' +
                    '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">' +
                    '<tr>' +
                    '<th style="width: 30%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">ID</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (idEscapado || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Nome</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (nomeEscapado || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">CPF</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (cpfEscapado || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">AR (Código de Rastreamento)</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;"><strong>' + (arEscapado || 'N/A') + '</strong></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">CRO</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (croEscapado || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Categoria</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (categoriaEscapada || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Inscrição</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (inscricaoEscapada || 'N/A') + '</td>' +
                    '</tr>';
                
                if (itemData.qr_code) {
                    htmlContent += '<tr>' +
                        '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">QR Code</th>' +
                        '<td style="border: 1px solid #000; padding: 8px;">' + qrCodeEscapado + '</td>' +
                        '</tr>';
                }
                
                htmlContent += '</table></div>';

                if (itemData.foto) {
                    htmlContent += '<div style="margin-bottom: 30px;">' +
                        '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">FOTO</h3>' +
                        '<div style="text-align: center;">' +
                        '<img src="data:image/jpeg;base64,' + itemData.foto + '" alt="Foto" style="max-width: 150px; max-height: 150px; border: 1px solid #000;" />';
                    
                    if (itemData.data_criacao) {
                        htmlContent += '<p style="margin-top: 10px; font-size: 11px; color: #666;">Data de Criação: ' + escapeHtml(itemData.data_criacao) + '</p>';
                    }
                    
                    htmlContent += '</div></div>';
                }

                // Adicionar seção de rastreamento
                if (rastreamentoResponse && rastreamentoResponse.rastreamento && rastreamentoResponse.rastreamento.length > 0) {
                    htmlContent += '<div style="margin-bottom: 30px;">' +
                        '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">RASTREAMENTO DOS CORREIOS</h3>' +
                        '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">' +
                        '<thead>' +
                        '<tr>' +
                        '<th style="width: 20%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Data/Hora</th>' +
                        '<th style="width: 20%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Unidade</th>' +
                        '<th style="width: 15%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Cidade</th>' +
                        '<th style="width: 10%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Estado</th>' +
                        '<th style="width: 35%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Descrição</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tbody>';
                    
                    rastreamentoResponse.rastreamento.forEach(function(item) {
                        let dataEvento = escapeHtml(item.data_evento || 'N/A');
                        let unidade = escapeHtml(item.unidade || 'N/A');
                        let cidade = escapeHtml(item.cidade || 'N/A');
                        let estado = escapeHtml(item.estado || 'N/A');
                        let descricao = escapeHtml(item.descricao || 'N/A');
                        
                        htmlContent += '<tr>' +
                            '<td style="border: 1px solid #000; padding: 8px;">' + dataEvento + '</td>' +
                            '<td style="border: 1px solid #000; padding: 8px;">' + unidade + '</td>' +
                            '<td style="border: 1px solid #000; padding: 8px;">' + cidade + '</td>' +
                            '<td style="border: 1px solid #000; padding: 8px;">' + estado + '</td>' +
                            '<td style="border: 1px solid #000; padding: 8px;"><strong>' + descricao + '</strong></td>' +
                            '</tr>';
                    });
                    
                    htmlContent += '</tbody></table></div>';
                } else {
                    htmlContent += '<div style="margin-bottom: 30px;">' +
                        '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">RASTREAMENTO DOS CORREIOS</h3>' +
                        '<p style="font-style: italic;">Nenhum evento de rastreamento encontrado.</p>' +
                        '</div>';
                }

                htmlContent += '<div style="margin-top: 40px; text-align: center; font-size: 12px; color: #666;">' +
                    '<p>Documento gerado em: ' + dataConsulta + '</p>' +
                    '</div></div>';

                Swal.close();
                
                // Sanitizar nome do arquivo (remover caracteres especiais)
                let nomeArquivo = (itemData.nome || 'sem_nome')
                    .replace(/[^a-zA-Z0-9\s]/g, '') // Remove caracteres especiais
                    .replace(/\s+/g, '_') // Substitui espaços por underscore
                    .substring(0, 50); // Limita tamanho
                
                // Criar elemento temporário para gerar PDF
                let element = document.createElement('div');
                element.id = 'pdf-content-temp-' + Date.now();
                element.innerHTML = htmlContent;
                
                // Estilos para garantir renderização
                element.style.cssText = 'width: 210mm; min-height: 297mm; padding: 20px; background-color: #ffffff; position: fixed; top: 0; left: 0; z-index: -9999; opacity: 0; pointer-events: none; overflow: visible;';
                
                document.body.appendChild(element);
                
                // Forçar layout e aguardar um pouco para renderização
                let forceLayout = element.offsetHeight;
                let forceLayout2 = element.scrollHeight;
                
                // Aguardar um pouco para garantir que o DOM foi atualizado
                setTimeout(function() {
                    forceLayout = element.offsetHeight;
                }, 50);
                
                // Função para aguardar carregamento de imagens
                function waitForImages(element, callback) {
                    let images = element.querySelectorAll('img');
                    let loaded = 0;
                    let total = images.length;
                    
                    if (total === 0) {
                        setTimeout(callback, 100);
                        return;
                    }
                    
                    let timeout = setTimeout(function() {
                        callback(); // Timeout de segurança
                    }, 5000);
                    
                    images.forEach(function(img) {
                        if (img.complete && img.naturalWidth > 0) {
                            loaded++;
                            if (loaded === total) {
                                clearTimeout(timeout);
                                callback();
                            }
                        } else {
                            img.onload = function() {
                                loaded++;
                                if (loaded === total) {
                                    clearTimeout(timeout);
                                    callback();
                                }
                            };
                            img.onerror = function() {
                                loaded++;
                                if (loaded === total) {
                                    clearTimeout(timeout);
                                    callback();
                                }
                            };
                        }
                    });
                }
                
                // Aguardar carregamento das imagens antes de gerar PDF
                waitForImages(element, function() {
                    // Aguardar um pouco mais para garantir renderização completa
                    setTimeout(function() {
                        // Verificar se o elemento tem conteúdo
                        if (!element.innerHTML || element.innerHTML.trim() === '') {
                            document.body.removeChild(element);
                            Swal.fire({
                                title: 'Erro',
                                text: 'Conteúdo vazio para gerar PDF',
                                icon: 'error'
                            });
                            return;
                        }
                        
                        // Configurações do PDF
                        let opt = {
                            margin: [10, 10, 10, 10],
                            filename: 'Identidade_' + itemData.id + '_' + nomeArquivo + '.pdf',
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { 
                                scale: 2,
                                useCORS: true,
                                logging: true, // Ativar para debug
                                letterRendering: true,
                                allowTaint: false,
                                backgroundColor: '#ffffff',
                                windowWidth: element.scrollWidth,
                                windowHeight: element.scrollHeight
                            },
                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                        };

                        // Gerar PDF
                        html2pdf().set(opt).from(element).save().then(function() {
                            if (element.parentNode) {
                                document.body.removeChild(element);
                            }
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'PDF gerado com sucesso!',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }).catch(function(error) {
                            if (element.parentNode) {
                                document.body.removeChild(element);
                            }
                            console.error('Erro ao gerar PDF:', error);
                            console.error('Element HTML:', element.innerHTML.substring(0, 500));
                            Swal.fire({
                                title: 'Erro',
                                text: 'Erro ao gerar PDF: ' + (error.message || error),
                                icon: 'error',
                                footer: 'Verifique o console para mais detalhes'
                            });
                        });
                    }, 200); // Aguardar 200ms para garantir renderização
                });

            }).fail(function () {
                // Se falhar ao buscar rastreamento, gerar PDF apenas com dados da consulta
                Swal.close();
                
                // Escapar dados
                let dataConsulta2 = new Date().toLocaleString('pt-BR');
                let idEscapado2 = escapeHtml(itemData.id);
                let nomeEscapado2 = escapeHtml(itemData.nome);
                let cpfEscapado2 = escapeHtml(itemData.cpf);
                let arEscapado2 = escapeHtml(itemData.ar);
                let croEscapado2 = escapeHtml(itemData.cro);
                let categoriaEscapada2 = escapeHtml(itemData.categoria);
                let inscricaoEscapada2 = escapeHtml(itemData.inscricao);
                let qrCodeEscapado2 = escapeHtml(itemData.qr_code);
                
                let htmlContent = '<div style="font-family: Arial, sans-serif; padding: 20px;">' +
                    '<div style="border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 10px;">' +
                    '<h2 style="text-align: center; margin-bottom: 10px; font-size: 18px;">CONSULTA DE IDENTIDADE</h2>' +
                    '<p style="text-align: center; margin: 0; font-size: 12px;">Data da Consulta: ' + dataConsulta2 + '</p>' +
                    '</div>' +
                    '<div style="margin-bottom: 30px;">' +
                    '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">DADOS DA IDENTIDADE</h3>' +
                    '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">' +
                    '<tr>' +
                    '<th style="width: 30%; border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">ID</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (idEscapado2 || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Nome</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (nomeEscapado2 || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">CPF</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (cpfEscapado2 || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">AR (Código de Rastreamento)</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;"><strong>' + (arEscapado2 || 'N/A') + '</strong></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">CRO</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (croEscapado2 || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Categoria</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (categoriaEscapada2 || 'N/A') + '</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">Inscrição</th>' +
                    '<td style="border: 1px solid #000; padding: 8px;">' + (inscricaoEscapada2 || 'N/A') + '</td>' +
                    '</tr>';
                
                if (itemData.qr_code) {
                    htmlContent += '<tr>' +
                        '<th style="border: 1px solid #000; padding: 8px; background-color: #f0f0f0; text-align: left;">QR Code</th>' +
                        '<td style="border: 1px solid #000; padding: 8px;">' + qrCodeEscapado2 + '</td>' +
                        '</tr>';
                }
                
                htmlContent += '</table></div>';
                
                if (itemData.foto) {
                    htmlContent += '<div style="margin-bottom: 30px;">' +
                        '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">FOTO</h3>' +
                        '<div style="text-align: center;">' +
                        '<img src="data:image/jpeg;base64,' + itemData.foto + '" alt="Foto" style="max-width: 150px; max-height: 150px; border: 1px solid #000;" />';
                    
                    if (itemData.data_criacao) {
                        htmlContent += '<p style="margin-top: 10px; font-size: 11px; color: #666;">Data de Criação: ' + escapeHtml(itemData.data_criacao) + '</p>';
                    }
                    
                    htmlContent += '</div></div>';
                }
                
                htmlContent += '<div style="margin-bottom: 30px;">' +
                    '<h3 style="border-bottom: 1px solid #000; padding-bottom: 5px; font-size: 14px; margin-bottom: 10px;">RASTREAMENTO DOS CORREIOS</h3>' +
                    '<p style="font-style: italic; color: #999;">Não foi possível carregar os dados de rastreamento.</p>' +
                    '</div></div>';

                // Sanitizar nome do arquivo (remover caracteres especiais)
                let nomeArquivo2 = (itemData.nome || 'sem_nome')
                    .replace(/[^a-zA-Z0-9\s]/g, '') // Remove caracteres especiais
                    .replace(/\s+/g, '_') // Substitui espaços por underscore
                    .substring(0, 50); // Limita tamanho
                
                // Criar elemento temporário para gerar PDF
                let element = document.createElement('div');
                element.id = 'pdf-content-temp-2-' + Date.now();
                element.innerHTML = htmlContent;
                
                // Estilos para garantir renderização
                element.style.cssText = 'width: 210mm; min-height: 297mm; padding: 20px; background-color: #ffffff; position: fixed; top: 0; left: 0; z-index: -9999; opacity: 0; pointer-events: none; overflow: visible;';
                
                document.body.appendChild(element);
                
                // Forçar layout
                let forceLayout = element.offsetHeight;
                let forceLayout2 = element.scrollHeight;
                
                setTimeout(function() {
                    forceLayout = element.offsetHeight;
                }, 50);
                
                // Função para aguardar carregamento de imagens
                function waitForImages2(element, callback) {
                    let images = element.querySelectorAll('img');
                    let loaded = 0;
                    let total = images.length;
                    
                    if (total === 0) {
                        setTimeout(callback, 100);
                        return;
                    }
                    
                    let timeout = setTimeout(function() {
                        callback(); // Timeout de segurança
                    }, 5000);
                    
                    images.forEach(function(img) {
                        if (img.complete && img.naturalWidth > 0) {
                            loaded++;
                            if (loaded === total) {
                                clearTimeout(timeout);
                                callback();
                            }
                        } else {
                            img.onload = function() {
                                loaded++;
                                if (loaded === total) {
                                    clearTimeout(timeout);
                                    callback();
                                }
                            };
                            img.onerror = function() {
                                loaded++;
                                if (loaded === total) {
                                    clearTimeout(timeout);
                                    callback();
                                }
                            };
                        }
                    });
                }
                
                // Aguardar carregamento das imagens antes de gerar PDF
                waitForImages2(element, function() {
                    setTimeout(function() {
                        // Verificar se o elemento tem conteúdo
                        if (!element.innerHTML || element.innerHTML.trim() === '') {
                            if (element.parentNode) {
                                document.body.removeChild(element);
                            }
                            Swal.fire({
                                title: 'Erro',
                                text: 'Conteúdo vazio para gerar PDF',
                                icon: 'error'
                            });
                            return;
                        }
                        
                        let opt = {
                            margin: [10, 10, 10, 10],
                            filename: 'Identidade_' + itemData.id + '_' + nomeArquivo2 + '.pdf',
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { 
                                scale: 2,
                                useCORS: true,
                                logging: true, // Ativar para debug
                                letterRendering: true,
                                allowTaint: false,
                                backgroundColor: '#ffffff',
                                windowWidth: element.scrollWidth,
                                windowHeight: element.scrollHeight
                            },
                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                        };

                        html2pdf().set(opt).from(element).save().then(function() {
                            if (element.parentNode) {
                                document.body.removeChild(element);
                            }
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'PDF gerado com sucesso!',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }).catch(function(error) {
                            if (element.parentNode) {
                                document.body.removeChild(element);
                            }
                            console.error('Erro ao gerar PDF:', error);
                            console.error('Element HTML:', element.innerHTML.substring(0, 500));
                            Swal.fire({
                                title: 'Erro',
                                text: 'Erro ao gerar PDF: ' + (error.message || error),
                                icon: 'error',
                                footer: 'Verifique o console para mais detalhes'
                            });
                        });
                    }, 200); // Aguardar 200ms para garantir renderização
                });
            });
        }

        // Adicionar event listeners aos botões de imprimir quando a página carregar
        $(document).ready(function() {
            $(document).on('click', '.btn-imprimir', function(e) {
                e.preventDefault();
                imprimirIdentidade(this);
            });
        });
    </script>
</head>
<body>

<div class="container-fluid">
    <h1 class="mt-4">Consulta de Identidade</h1>
    <div class="card">
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="tipoConsulta" value="<?= htmlspecialchars($tipoConsulta) ?>">

                <div class="row">
                    <div class="form-group col-md-4">
                        <label for="searchType">Tipo de Pesquisa:</label>
                        <select id="searchType" name="searchType" class="form-control" required>
                            <option value="name" <?= ($searchType === 'name') ? 'selected' : '' ?>>Nome</option>
                            <option value="cpf" <?= ($searchType === 'cpf') ? 'selected' : '' ?>>CPF</option>
                            <option value="ar" <?= ($searchType === 'ar') ? 'selected' : '' ?>>AR</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="searchValue">Valor da Pesquisa:</label>
                        <input type="text" name="searchValue" id="searchValue" class="form-control"
                               value="<?= htmlspecialchars($searchValue) ?>" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Pesquisar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (!empty($searchValue)) {
        $apiUrl = $endpoints[$searchType] . "?token=" . urlencode($token) . "&" . $searchType . "=" . urlencode($searchValue);

        // Paginação apenas para nome
        if ($searchType === 'name') {
            $apiUrl .= "&page_number=" . urlencode($page_number) . "&page_amount=" . urlencode($page_amount);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_USERAGENT => 'SistemaConsultas/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        curl_close($ch);
        
        // Tratamento de erros melhorado
        if ($curlErrno) {
            $errorMessage = "Erro CURL: " . $curlError;
            
            // Mensagens mais amigáveis para erros comuns
            if (strpos($curlError, 'Connection refused') !== false) {
                $errorMessage = "Erro: Não foi possível conectar ao servidor da API ($API_BASE_URL). ";
                $errorMessage .= "Verifique se o servidor está em execução e acessível na rede.";
            } elseif (strpos($curlError, 'Connection timed out') !== false) {
                $errorMessage = "Erro: Tempo de conexão esgotado. O servidor pode estar sobrecarregado ou inacessível.";
            } elseif (strpos($curlError, 'Failed to resolve') !== false) {
                $errorMessage = "Erro: Não foi possível resolver o endereço do servidor. Verifique a configuração da URL da API.";
            }
            
            echo "<div class='alert alert-danger'>";
            echo "<strong>Erro na consulta:</strong><br>";
            echo htmlspecialchars($errorMessage);
            echo "<br><small>URL: " . htmlspecialchars($apiUrl) . "</small>";
            echo "</div>";
            
            // Log do erro para debug
            error_log("Consulta Identidade - Erro CURL: $curlError | HTTP Code: $httpCode | URL: $apiUrl");
            
            $response = null;
        } elseif ($httpCode >= 400) {
            $errorDetails = "";
            
            // Tentar decodificar a resposta para obter mais detalhes
            if (!empty($response)) {
                $errorData = json_decode($response, true);
                if (isset($errorData['erro']) || isset($errorData['error']) || isset($errorData['message'])) {
                    $errorDetails = isset($errorData['erro']) ? $errorData['erro'] : 
                                   (isset($errorData['error']) ? $errorData['error'] : $errorData['message']);
                }
            }
            
            echo "<div class='alert alert-danger'>";
            echo "<strong>Erro HTTP $httpCode:</strong><br>";
            
            if ($httpCode == 401) {
                echo "<strong>❌ Erro de Autenticação (401 - Unauthorized)</strong><br><br>";
                echo "O token da API não está sendo aceito pelo servidor.<br>";
                echo "Possíveis causas:<br>";
                echo "• Token inválido ou expirado<br>";
                echo "• Token não configurado corretamente no arquivo .env<br>";
                echo "• Token não autorizado no servidor da API (192.168.161.165:8082)<br><br>";
                if ($errorDetails) {
                    echo "<strong>Detalhes do servidor:</strong> " . htmlspecialchars($errorDetails) . "<br>";
                }
                echo "<small>Verifique a variável <code>API_TOKEN</code> no arquivo <code>.env</code></small>";
            } elseif ($httpCode == 404) {
                echo "<strong>❌ Endpoint não encontrado (404)</strong><br><br>";
                echo "O endpoint da API não foi encontrado.<br>";
                echo "Verifique se a URL da API está correta: <code>$API_BASE_URL</code><br>";
                if ($errorDetails) {
                    echo "<strong>Detalhes:</strong> " . htmlspecialchars($errorDetails) . "<br>";
                }
            } elseif ($httpCode == 500) {
                echo "<strong>❌ Erro interno do servidor (500)</strong><br><br>";
                echo "O servidor da API retornou um erro interno.<br>";
                echo "Verifique os logs do servidor 192.168.161.165:8082<br>";
                if ($errorDetails) {
                    echo "<strong>Detalhes:</strong> " . htmlspecialchars($errorDetails) . "<br>";
                }
            } else {
                echo "O servidor retornou um erro. Verifique os parâmetros da consulta.<br>";
                if ($errorDetails) {
                    echo "<strong>Detalhes:</strong> " . htmlspecialchars($errorDetails) . "<br>";
                }
            }
            
            echo "<br><small>URL da requisição: " . htmlspecialchars(preg_replace('/token=[^&]+/', 'token=***', $apiUrl)) . "</small>";
            echo "</div>";
            
            error_log("Consulta Identidade - HTTP Error $httpCode | URL: " . preg_replace('/token=[^&]+/', 'token=***', $apiUrl) . " | Response: " . substr($response, 0, 500));
            $response = null;
        }

        // Só processa a resposta se houver dados válidos
        if ($response === null || empty($response)) {
            // Erro já foi exibido acima, apenas mostra mensagem de nenhum resultado
            echo "<div class='alert alert-warning'>Nenhum resultado encontrado.</div>";
        } else {
            $data = json_decode($response, true);
            $key = $searchType === 'name' ? 'list' : 'identidade';

            if (!isset($data[$key]) || empty($data[$key])) {
                echo "<div class='alert alert-warning'>Nenhum resultado encontrado.</div>";
            } else {
            echo "<div class='table-responsive'><table class='table table-sm table-bordered table-striped table-hover'>";
            echo "<thead><tr>
                    <th>ID</th><th>Nome</th><th>CPF</th><th>AR</th><th>CRO</th>
                    <th>Categoria</th><th>Inscricao.</th><th>QR Code</th><th>Foto / Data Criação</th><th>Ações</th>
                  </tr></thead><tbody>";

            foreach ($data[$key] as $item) {
                // Debug: Log dos campos disponíveis (apenas no primeiro item)
                static $debugLogged = false;
                if (!$debugLogged) {
                    $camposDisponiveis = array_keys($item);
                    error_log("DEBUG - Campos disponíveis na resposta da API: " . json_encode($camposDisponiveis));
                    // Mostrar debug na página também (temporário)
                    echo "<!-- DEBUG: Campos disponíveis: " . htmlspecialchars(json_encode($camposDisponiveis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . " -->";
                    echo "<!-- DEBUG: Item completo (primeiro): " . htmlspecialchars(json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . " -->";
                    $debugLogged = true;
                }
                
                // Tratar nome (pode ser array ou string)
                $nome = '';
                if (isset($item['nome'])) {
                    if (is_array($item['nome']) && !empty($item['nome'])) {
                        $nome = $item['nome'][0];
                    } elseif (is_string($item['nome'])) {
                        $nome = $item['nome'];
                    }
                }
                
                // Preparar dados para data attributes (sem a foto para evitar problemas)
                $itemId = htmlspecialchars($item['id'] ?? '', ENT_QUOTES);
                $itemNome = htmlspecialchars($nome, ENT_QUOTES);
                $itemCpf = htmlspecialchars($item['cpf'] ?? '', ENT_QUOTES);
                $itemAr = htmlspecialchars($item['ar'] ?? '', ENT_QUOTES);
                $itemCro = htmlspecialchars($item['cro'] ?? '', ENT_QUOTES);
                $itemCategoria = htmlspecialchars($item['categoria'] ?? '', ENT_QUOTES);
                $itemInscricao = htmlspecialchars($item['inscricao'] ?? '', ENT_QUOTES);
                $itemQrCode = htmlspecialchars($item['qr_code'] ?? '', ENT_QUOTES);
                $itemFoto = !empty($item['foto']) ? $item['foto'] : '';
                $itemCpfLimpo = preg_replace('/\D/', '', $item['cpf'] ?? '');
                
                // Debug: Log dos dados antes de gerar o botão (apenas no primeiro item)
                static $debugButtonLogged = false;
                if (!$debugButtonLogged) {
                    error_log("DEBUG BOTÃO - Dados preparados:");
                    error_log("  ID: " . $itemId);
                    error_log("  Nome: " . $itemNome);
                    error_log("  CPF: " . $itemCpf);
                    error_log("  AR: " . $itemAr);
                    error_log("  CRO: " . $itemCro);
                    error_log("  Categoria: " . $itemCategoria);
                    error_log("  Inscrição: " . $itemInscricao);
                    $debugButtonLogged = true;
                }
                
                // Obter data_criacao da resposta da API do servidor 165
                // Prioridade: data_criacao > data_solicitacao > buscar no servidor de foto
                $dataCriacao = '';
                
                // 1. Tentar buscar data_criacao na raiz do item
                $possiveisCampos = [
                    'data_criacao',
                    'data_criacao_foto',
                    'foto_data_criacao',
                    'data_foto',
                    'foto_data',
                    'data_criacao_imagem',
                    'imagem_data_criacao',
                    'created_at',
                    'data_insercao',
                    'data_upload',
                    'upload_date',
                    'foto_created_at'
                ];
                
                foreach ($possiveisCampos as $campo) {
                    if (isset($item[$campo]) && !empty($item[$campo])) {
                        $dataCriacao = $item[$campo];
                        break;
                    }
                }
                
                // 2. Se não encontrou data_criacao, usar data_solicitacao (disponível no servidor 165)
                if (empty($dataCriacao) && isset($item['data_solicitacao']) && !empty($item['data_solicitacao'])) {
                    $dataCriacao = $item['data_solicitacao'];
                }
                
                // 3. Se não encontrou na raiz, verificar dentro do objeto 'foto' (se for objeto)
                if (empty($dataCriacao) && isset($item['foto']) && is_array($item['foto'])) {
                    foreach ($possiveisCampos as $campo) {
                        if (isset($item['foto'][$campo]) && !empty($item['foto'][$campo])) {
                            $dataCriacao = $item['foto'][$campo];
                            break;
                        }
                    }
                }
                
                // 4. Se ainda não encontrou e tem foto + CPF + CRO, buscar no servidor de foto
                // O servidor de foto retorna data_criacao no endpoint: POST /service/v1/consulta/solicitacao/cpf
                if (empty($dataCriacao) && !empty($itemFoto) && !empty($itemCpfLimpo) && !empty($itemCro)) {
                    $croParaBusca = $itemCro;
                    $dataCriacao = buscarDataCriacaoFoto($itemCpfLimpo, $croParaBusca, $FOTO_SERVERS, $FOTO_SERVER_USUARIO, $FOTO_SERVER_CHAVE, $dataCriacaoCache);
                }
                
                // 4. Debug: Log todos os campos disponíveis (apenas no primeiro item para não poluir)
                static $debugLogado = false;
                if (!$debugLogado && empty($dataCriacao) && !empty($itemFoto)) {
                    error_log("DEBUG data_criacao - Campos disponíveis no item: " . implode(', ', array_keys($item)));
                    if (isset($item['foto']) && is_array($item['foto'])) {
                        error_log("DEBUG data_criacao - Campos dentro de 'foto': " . implode(', ', array_keys($item['foto'])));
                    }
                    $debugLogado = true; // Logar apenas uma vez
                }
                
                $dataCriacaoFormatada = !empty($dataCriacao) ? htmlspecialchars($dataCriacao, ENT_QUOTES) : '-';
                $dataCriacaoAttr = htmlspecialchars($dataCriacao, ENT_QUOTES);
                
                echo "<tr>";
                echo "<td>" . $itemId . "</td>";
                echo "<td>" . $itemNome . "</td>";
                echo "<td>" . $itemCpf . "</td>";
                echo "<td>" . $itemAr . "</td>";
                echo "<td>" . $itemCro . "</td>";
                echo "<td>" . $itemCategoria . "</td>";
                echo "<td>" . $itemInscricao . "</td>";
                echo "<td><a href='" . $itemQrCode . "' target='_blank'>Ver QR Code</a></td>";
                echo "<td style='vertical-align: middle;'>";
                if (!empty($itemFoto)) {
                    echo "<div style='display: flex; align-items: center; gap: 10px;'>";
                    echo "<img src='data:image/jpeg;base64," . htmlspecialchars($itemFoto) . "' alt='Foto' class='foto-identidade' data-foto='" . htmlspecialchars($itemFoto, ENT_QUOTES) . "' style='max-width: 80px; max-height: 80px; border-radius: 5px;'>";
                    echo "<div style='flex: 1;'>";
                    if (!empty($dataCriacaoFormatada) && $dataCriacaoFormatada !== '-') {
                        echo "<div style='font-size: 11px; color: #666; margin-top: 5px;'><strong>Data Criação:</strong><br>" . $dataCriacaoFormatada . "</div>";
                    } else {
                        echo "<div style='font-size: 11px; color: #999; font-style: italic; margin-top: 5px;'>Data não disponível</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "-";
                }
                echo "</td>";
                echo "<td>";
                echo "<button class='btn btn-sm btn-info' onclick=\"consultarRastreamento('" . $itemId . "')\" style='margin-right: 5px;'>Rastreamento</button>";
                
                // Usar aspas duplas e json_encode para garantir escape correto dos valores
                $buttonData = [
                    'id' => $itemId,
                    'nome' => $itemNome,
                    'cpf' => $itemCpf,
                    'ar' => $itemAr,
                    'cro' => $itemCro,
                    'categoria' => $itemCategoria,
                    'inscricao' => $itemInscricao,
                    'qrcode' => $itemQrCode,
                    'foto' => ($itemFoto ? '1' : '0'),
                    'criacao' => $dataCriacaoAttr
                ];
                
                // Construir atributos data-* com escape adequado
                $dataAttributes = '';
                foreach ($buttonData as $key => $value) {
                    // Escapar o valor para atributo HTML usando htmlspecialchars
                    // Usar ENT_QUOTES para escapar aspas simples e duplas
                    $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    // Substituir aspas duplas por entidade HTML para evitar problemas
                    $escapedValue = str_replace('"', '&quot;', $escapedValue);
                    $dataAttributes .= ' data-' . $key . '="' . $escapedValue . '"';
                }
                
                // Debug: Log do HTML do botão (apenas no primeiro item)
                static $debugButtonHtmlLogged = false;
                if (!$debugButtonHtmlLogged) {
                    error_log("DEBUG BOTÃO - HTML gerado: " . substr("<button class='btn btn-sm btn-success btn-imprimir' title='Exportar PDF'" . $dataAttributes . ">Exportar PDF</button>", 0, 500));
                    $debugButtonHtmlLogged = true;
                }
                
                echo "<button class='btn btn-sm btn-success btn-imprimir' title='Exportar PDF'" . $dataAttributes . ">Exportar PDF</button>";
                echo "</td>";
                echo "</tr>";
            }

            echo "</tbody></table></div>";
            }
        }
    }
    ?>
</div>

</body>
</html>
