<?php
// Config
require_once realpath(dirname(__FILE__, 2)) . '/config/config.php';

// Pastas
define('LIB_PATH', realpath(dirname(__FILE__, 2) . '/lib'));
define('VIEWS_PATH', realpath(dirname(__FILE__, 2) . '/views'));
define('DB_PATH', realpath(dirname(__FILE__, 2) . '/database'));
define('SERVICES_PATH', realpath(dirname(__FILE__, 2) . '/services'));
define('INC_PATH', realpath(dirname(__FILE__, 2) . '/includes'));
define('ASSETS_PATH', realpath(dirname(__FILE__, 2) . '/public/assets'));

use Steampixel\Route;

// Sistema CRUD 
Route::add('/', function () {
    header('location:index');
    exit;
});
Route::add('/index', function () {
    require VIEWS_PATH . '/index.php';
    exit;
});
Route::add('/login', function () {
    require VIEWS_PATH . '/login.php';
}, ['get', 'post']);
Route::add('/perfil', function () {
    require VIEWS_PATH . '/perfil.php';
    exit;
}, ['get', 'post']);
Route::add('/users', function () {
    require VIEWS_PATH . '/users.php';
    exit;
});
Route::add('/adduser', function () {
    require VIEWS_PATH . '/usersAdd.php';
    exit;
}, ['get', 'post']);
Route::add('/mudar-senha', function () {
    require VIEWS_PATH . '/senhaMudar.php';
    exit;
}, ['get', 'post']);
Route::add('/recuperar-senha', function () {
    require VIEWS_PATH . '/senhaRecuperar.php';
    exit;
}, ['get', 'post']);
Route::add('/cadastrar-usuario', function () {
    require VIEWS_PATH . '/cadastrarUsuario.php';
    exit;
}, ['get', 'post']);
Route::add('/acessos', function () {
    require VIEWS_PATH . '/acessos.php';
    exit;
}, ['get', 'post']);
Route::add('/updateAcess', function () {
    require LIB_PATH . '/AcessUpdate.php';
    exit;
}, ['get', 'post']);

// Logs de Atividade (Admin)
Route::add('/logs', function () {
    require VIEWS_PATH . '/logs.php';
    exit;
}, ['get', 'post']);

// Consulta Identidade
Route::add('/consulta-identidade', function () {
    require VIEWS_PATH . '/consultaIdentidade.php';
    exit;
}, ['get', 'post']);

// Consulta Integrada
Route::add('/consulta-integrada', function () {
    require VIEWS_PATH . '/consultaIntegrada.php';
    exit;
}, ['get', 'post']);

// Consulta Eleições
Route::add('/consulta-eleicoes', function () {
    require VIEWS_PATH . '/consultaEleicoes.php';
    exit;
}, ['get', 'post']);

// AJAX para detalhes do eleitor
Route::add('/consulta-eleicoes/ajax-detalhes-eleitor.php', function () {
    require SERVICES_PATH . '/consulta-eleicoes/ajax-detalhes-eleitor.php';
    exit;
}, ['post']);



// Consulta Estatística
Route::add('/consulta-estatistica', function () {
    require VIEWS_PATH . '/consultaEstatistica.php';
    exit;
}, ['get', 'post']);
Route::add('/consulta-prescricao', function () {
    require VIEWS_PATH . '/consultaPrescricao.php';
    exit;
}, ['get', 'post']);


// Consulta Auditoria
Route::add('/consulta-auditoria', function () {
    require VIEWS_PATH . '/consultaAuditoria.php';
    exit;
}, ['get', 'post']);



// Consulta Fiscalização
Route::add('/consulta-fiscalizacao', function () {
    require VIEWS_PATH . '/consultaFiscalizacao.php';
    exit;
}, ['get', 'post']);

// Consulta Sigesp
Route::add('/consulta-sigesp', function () {
    require VIEWS_PATH . '/consultaSigesp.php';
    exit;
}, ['get', 'post']);

Route::add('/consulta-rfb', function () {
    require VIEWS_PATH . '/consultaRfb.php';
    exit;
}, ['get', 'post']);

// Gerenciamento de Consultas RFB (tipo=2 na URL)
Route::add('/consulta-rfb-gerenciar', function () {
    require SERVICES_PATH . '/consulta-rfb/gerenciar-rfb.php';
    exit;
}, ['get', 'post']);

// Exportação de logs RFB para Excel
Route::add('/exportar-log-rfb-excel', function () {
    require SERVICES_PATH . '/consulta-rfb/exportar-log-rfb-excel.php';
    exit;
}, ['get']);

// PDF Log RFB (relatório compilado de auditoria)
Route::add('/gerar-pdf-rfb-log', function () {
    require SERVICES_PATH . '/consulta-rfb/gerar-pdf-rfb-log.php';
    exit;
}, ['get']);

// Geração de PDF RFB
Route::add('/gerar-pdf-rfb-simples', function () {
    require SERVICES_PATH . '/consulta-rfb/gerar-pdf-rfb-simples.php';
    exit;
}, ['get']);

Route::add('/ExcelDownload', function () {
    require LIB_PATH . '/ExcelDownload.php';
    exit;
}, ['get', 'post']);

// Rotas para download de Excel otimizado - Consulta Identidade
Route::add('/download-excel-direto', function () {
    require SERVICES_PATH . '/consulta-identidade/download-excel-direto.php';
    exit;
}, ['get', 'post']);

Route::add('/download-e-compilar', function () {
    require SERVICES_PATH . '/consulta-identidade/download-e-compilar.php';
    exit;
}, ['get', 'post']);

Route::add('/teste-download', function () {
    require SERVICES_PATH . '/consulta-identidade/teste-download.php';
    exit;
}, ['get', 'post']);

// Novas rotas para download robusto
Route::add('/download-excel-streaming', function () {
    require SERVICES_PATH . '/consulta-identidade/download-excel-streaming.php';
    exit;
}, ['get', 'post']);

Route::add('/download-excel-robusto', function () {
    require SERVICES_PATH . '/consulta-identidade/download-excel-robusto.php';
    exit;
}, ['get', 'post']);

Route::add('/teste-simples', function () {
    require SERVICES_PATH . '/consulta-identidade/teste-simples.php';
    exit;
}, ['get', 'post']);

// Relatórios tarifas bancárias
Route::add('/relatorio-tarifas', function () {
    require VIEWS_PATH . '/relatorioTarifas.php';
    exit;
}, ['get', 'post']);
Route::add('/relatorio-tarifas-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-tarifas-gerar.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-processos-especialidade', function () {
    require VIEWS_PATH . '/relatorios/relatorio-processos-especialidade.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-processos-especialidade-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-processos-especialidade-gerar.php';
    exit;
}, ['get', 'post']);

Route::add('/ajax_salvar_registro', function () {
    require SERVICES_PATH . '/relatorios/ajax_salvar_registro.php';
    exit;
}, ['get', 'post']);

Route::add('/ajax_inserir_registro', function () {
    require SERVICES_PATH . '/relatorios/ajax_inserir_registro.php';
    exit;
}, ['post']);

Route::add('/ajax_deletar_registro', function () {
    require SERVICES_PATH . '/relatorios/ajax_deletar_registro.php';
    exit;
}, ['post', 'delete']);

Route::add('/teste-inserir', function () {
    require SERVICES_PATH . '/relatorios/teste_inserir.php';
    exit;
});





Route::add('/relatorios/gerar-excel-processos', function () {
    require SERVICES_PATH . '/relatorios/gerar-excel-processos.php';
    exit;
}, ['get', 'post']);


Route::add('/relatorio-adimplencia-cobranca', function () {
    require VIEWS_PATH . '/relatorioAdimplenciaCobranca.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-adimplencia-cobranca-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-adimplencia-cobranca-gerar.php';
    exit;
}, ['get', 'post']);

// Relatório de Processos
Route::add('/relatorio-processos', function () {
    require SERVICES_PATH . '/relatorios/relatorio-processos.php';
    exit;
}, ['get', 'post']);
Route::add('/relatorio-processos-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-processos-gerar.php';
    exit;
}, ['get', 'post']);

// Relatórios de adimplência
Route::add('/relatorio-adimplencia', function () {
    require VIEWS_PATH . '/relatorioAdimplencia.php';
    exit;
}, ['get', 'post']);
Route::add('/relatorio-adimplencia-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-adimplencia-gerar.php';
    exit;
}, ['get', 'post']);

// Relatório de adimplência com valores
Route::add('/relatorio-adimplencia-valores', function () {
    require VIEWS_PATH . '/relatorioAdimplenciaValores.php';
    exit;
}, ['get', 'post']);
Route::add('/relatorio-adimplencia-valores-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-adimplencia-valores-gerar.php';
    exit;
}, ['get', 'post']);

 //Relatórios de delegado eleitor 
 Route::add('/relatorio-delegadoeleitor', function () {
    require SERVICES_PATH . '/relatorioDelegadoEleitor.php';
     exit;
 }, ['get', 'post']);

Route::add('/relatorio-delegadoeleitor-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-delegadoeleitor-gerar.php';
    exit;
}, ['get', 'post']);

Route::add('/ExcelDownloadDelegado', function () {
    require SERVICES_PATH . '/relatorios/ExcelDownloadDelegado.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-delegadoeleitor-download', function () {
    require SERVICES_PATH . '/relatorios/relatorio-delegadoeleitor-download.php';
    exit;
}, ['get', 'post']);

// Relatórios Profissional x formacao
Route::add('/relatorio-profissional-formacao', function () {
    require SERVICES_PATH . '/relatorios/relatorio-profissional-formacao.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-profissional-formacao-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-profissional-formacao-gerar.php';
    exit;
}, ['get', 'post']);


// Relatórios da LAI
Route::add('/relatorio-lai', function () {
    require SERVICES_PATH . '/relatorio-LAI.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-lai-2', function () {
    require SERVICES_PATH . '/relatorios/relatorio-LAI-2.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-lai-editar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-lai-editar.php';
    exit;
}, ['post']);

Route::add('/relatorio-lai-ajax', function () {
    require SERVICES_PATH . '/relatorios/relatorio-lai-ajax.php';
    exit;
}, ['get', 'post']);

Route::add('/teste-tabela-lai', function () {
    require SERVICES_PATH . '/relatorios/teste-tabela-lai.php';
    exit;
}, ['get']);

// Formulario da LAI
Route::add('/form-lai', function () {
    require VIEWS_PATH . '/form-lai.php';
    exit;
}, ['get', 'post']);

// Cadastros
Route::add('/cadastro', function () {
    require VIEWS_PATH . '/cadastro.php';
    exit;
}, ['get', 'post']);

Route::add('/cadastro-1', function () {
    require SERVICES_PATH . '/cadastro/cadastro-1.php';
    exit;
}, ['get', 'post']);

Route::add('/cadastro-1', function () {
    require SERVICES_PATH . '/cadastro/cadastro-1.php';
    exit;
}, ['get', 'post']);

// Dados Abertos
Route::add('/dados_abertos', function () {
    require VIEWS_PATH . '/dadosAbertos.php';
    exit;
}, ['get', 'post']);
Route::add('/cracha', function () {
    require VIEWS_PATH . '/cracha.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorios', function () {
    require VIEWS_PATH . '/relatorios.php';
    exit;
}, ['get', 'post']);

Route::add('/tabelas-centralizadas', function () {
    require VIEWS_PATH . '/tabelasCentralizadas.php';
    exit;
}, ['get', 'post']);

// Relatórios da Eventos
Route::add('/relatorio-eventos', function () {
    require SERVICES_PATH . '/relatorio-eventos.php';
    exit;
}, ['get', 'post']);

Route::add('/atualizar-labels', function () {
    require VIEWS_PATH . '/atualizarLabels.php';
    exit;
}, ['get', 'post']);

Route::add('/gerenciar-labels', function () {
    require VIEWS_PATH . '/gerenciarLabels.php';
    exit;
}, ['get', 'post']);

Route::add('/services/labels-admin/labels-controller.php', function () {
    require SERVICES_PATH . '/labels-admin/labels-controller.php';
    exit;
}, ['get', 'post']);

// Relatórios de Processos de Especialidade e Habilitação


// 404 erro
Route::pathNotFound(function ($path) {
    header('HTTP/1.0 404 Not Found');
    echo "<link rel='stylesheet' href='../assets/css/styles.css'>
    <div classs='container p-5'>
        <div class='row no-gutters'>
        <div class='col-lg-6 col-md-12 m-auto'>
            <div class='alert alert-danger m-4 fade show' role='alert'>
            <h4 class='alert-heading mt-2 mb-4'>Página não encontrara!</h4>
                <p>
                Essa página <i>$path</i>, não foi encontrada.<br>
                <i>Você será redirecionado em instantes.</i>
                <meta http-equiv='refresh' content='2;url=/index'>
                </p>
            </div>
        </div>
      </div>
    </div>";
});

Route::add('/download-identidades-retornadas-csv', function () {
    require SERVICES_PATH . '/consulta-identidade/download-identidades-retornadas-csv.php';
    exit;
}, ['get', 'post']);

// API Proxy para CPFs Aprovados (Gestão de Carteiras)
Route::add('/api-cpf-aprovados', function () {
    require SERVICES_PATH . '/consulta-identidade/api-cpf-aprovados-proxy.php';
    exit;
}, ['get', 'post']);

// Gestão de Carteiras Policarbonatos - Página Principal (CI24)
Route::add('/consulta-identidade-24', function () {
    require SERVICES_PATH . '/consulta-identidade/consultaIdentidade-24.php';
    exit;
}, ['get', 'post']);

// Estabilização Diária - Gestão de Carteiras (CI25)
Route::add('/consulta-identidade-25', function () {
    require SERVICES_PATH . '/consulta-identidade/consultaIdentidade-25.php';
    exit;
}, ['get', 'post']);

// Alias para facilitar acesso
Route::add('/gestao-carteiras', function () {
    require SERVICES_PATH . '/consulta-identidade/consultaIdentidade-24.php';
    exit;
}, ['get', 'post']);

Route::add('/estabilizacao-diaria', function () {
    require SERVICES_PATH . '/consulta-identidade/consultaIdentidade-25.php';
    exit;
}, ['get', 'post']);

Route::add('/download-cpfs-duplicados-eleicoes-csv', function () {
    require SERVICES_PATH . '/consulta-eleicoes/download-cpfs-duplicados-csv.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-arrecadacao-bb', function () {
    require SERVICES_PATH . '/relatorios/relatorio-arrecadacao-bb.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-arrecadacao-bb-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-arrecadacao-bb-gerar.php';
    exit;
}, ['post']);

// Relatório de Tarifas BB
Route::add('/relatorio-tarifas-bb', function () {
    require SERVICES_PATH . '/relatorios/relatorio-tarifas-bb.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-tarifas-bb-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-tarifas-bb-gerar.php';
    exit;
}, ['post']);

// Relatório de Arrecadação e Tarifas do CARTÃO DE CRÉDITO - SELFPAY / BKBANK
Route::add('/relatorio-arrecadacao-tarifas-selfpay', function () {
    require SERVICES_PATH . '/relatorios/relatorio-arrecadacao-tarifas-selfpay.php';
    exit;
}, ['get', 'post']);
Route::add('/relatorio-arrecadacao-tarifas-selfpay-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-arrecadacao-tarifas-selfpay-gerar.php';
    exit;
}, ['post']);

// Relatório de Pagamentos Diversos
Route::add('/relatorio-pagamentos-diversos', function () {
    require SERVICES_PATH . '/relatorios/relatorio-pagamentos-diversos.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-pagamentos-diversos-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-pagamentos-diversos-gerar.php';
    exit;
}, ['post']);

// Relatório de Pagamentos Selfpay
Route::add('/relatorio-pagamentos-selfpay', function () {
    require SERVICES_PATH . '/relatorios/relatorio-pagamentos-selfpay.php';
    exit;
}, ['get', 'post']);

Route::add('/relatorio-pagamentos-selfpay-gerar', function () {
    require SERVICES_PATH . '/relatorios/relatorio-pagamentos-selfpay-gerar.php';
    exit;
}, ['post']);

Route::run('/');