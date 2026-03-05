<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();
if (!$isAdmin && isset($row) && isset($row['RE3acesso']) && $row['RE3acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='index';
    </script>";
    exit;
  }
  
// Verificar permissões de acesso
$userGrupo = Session::get('grupo');
$userSubgrupo = Session::get('subgrupo');

// Permitir acesso para Administrador (grupo 0), CFO (grupo 1) e Gestores (subgrupo 1)
$isAdmin = ($userGrupo == 0) || ($userGrupo == 1) || ($userSubgrupo == 1);



// Conectar ao banco de dados
$db = Database1::getInstance();
$con = $db->getConnection();

// Buscar dados do banco
try {
    $queryLAI = "SELECT 
                    id, uf, nome_autoridade_oficial_lai, email_autoridade_lai, telefone_autoridade_lai,
                    cargo_oficial_lai, vinculo_oficial_lai, portaria_oficial_lai, curso_capacitacao_lai,
                    nivel_autopercepcao_lai, observacoes_lai, observacoes_lgpd, tipo_portal_lai,
                    ano_atualizacao_dados_lai, link_e_sic, link_e_ouve, sistema_atos_normativos_proprio,
                    interesse_sisato_cfo, satisfacao_dados_abertos_tcu, link_portal_transparencia,
                    link_dados_abertos, indice_transparencia_ativa, indice_transparencia_passiva,
                    tempo_medio_resposta_pedido, grau_adequacao_lgpd, nome_encarregado_lgpd,
                    email_encarregado_lgpd, telefone_encarregado_lgpd, portaria_encarregado_lgpd,
                    politica_privacidade_link, inventario_dados_status, relatorio_impacto_prodados,
                    ultimo_treinamento_lgpd, titular_canal_solicitacao, 
                    setores_alimentacao_transparencia
                  FROM 
                    db_sistema_consultas.tbl_dados_lgpd_cros
                  ORDER BY uf";

    $stmt = $con->prepare($queryLAI);
    $stmt->execute();
    $dadosLAI = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    die("Erro ao retornar os dados: " . $error->getMessage());
}

// TESTE: Adicionar um estado com links para verificar se a funcionalidade funciona
$estadoTeste = [
    'id' => 999,
    'uf' => 'TS', // Estado de Teste
    'nome_autoridade_oficial_lai' => 'Autoridade Teste',
    'email_autoridade_lai' => 'teste@exemplo.com',
    'telefone_autoridade_lai' => '(11) 9999-9999',
    'cargo_oficial_lai' => 'Cargo Teste',
    'vinculo_oficial_lai' => 'Efetivo',
    'portaria_oficial_lai' => '123/2024',
    'curso_capacitacao_lai' => 'avancado',
    'nivel_autopercepcao_lai' => 'apto',
    'observacoes_lai' => '',
    'observacoes_lgpd' => '',
    'tipo_portal_lai' => 'proprio',
    'ano_atualizacao_dados_lai' => '2024',
    'link_e_sic' => 'https://exemplo.com/esic',
    'link_e_ouve' => 'https://exemplo.com/eouv',
    'sistema_atos_normativos_proprio' => 'sim',
    'interesse_sisato_cfo' => 'nao',
    'satisfacao_dados_abertos_tcu' => 'bom',
    'link_portal_transparencia' => 'https://exemplo.com/portal',
    'link_dados_abertos' => 'https://exemplo.com/dados',
    'indice_transparencia_ativa' => 'adequado',
    'indice_transparencia_passiva' => 'adequado',
    'tempo_medio_resposta_pedido' => '10',
    'grau_adequacao_lgpd' => 'adequado',
    'nome_encarregado_lgpd' => 'DPO Teste',
    'email_encarregado_lgpd' => 'dpo@exemplo.com',
    'telefone_encarregado_lgpd' => '(11) 8888-8888',
    'portaria_encarregado_lgpd' => '456/2024',
    'politica_privacidade_link' => 'https://exemplo.com/privacidade',
    'inventario_dados_status' => 'concluído',
    'relatorio_impacto_prodados' => '1',
    'ultimo_treinamento_lgpd' => '2024-01-15',
    'titular_canal_solicitacao' => 'titular@exemplo.com',
    
    'setores_alimentacao_transparencia' => 'TI,Secretaria'
];

// Debug: verificar se há links nos dados primeiro
$estadosComLinksTemp = [];
foreach ($dadosLAI as $dado) {
    if (!empty($dado['link_portal_transparencia'])  || 
        !empty($dado['link_dados_abertos']) || !empty($dado['link_e_sic']) || !empty($dado['link_e_ouve'])) {
        $estadosComLinksTemp[] = $dado['uf'];
    }
}

// Adicionar estado de teste apenas se não há nenhum link no banco
if (empty($estadosComLinksTemp)) {
    $dadosLAI[] = $estadoTeste;
    error_log("TESTE: Adicionado estado de teste com links - nenhum link encontrado no banco");
} else {
    error_log("TESTE: Links encontrados nos estados: " . implode(', ', $estadosComLinksTemp));
}

// Debug: verificar se há links nos dados
$estadosComLinks = [];
foreach ($dadosLAI as $dado) {
    $temLink = false;
    $links = [];
    
    // Verificar ambos os nomes possíveis para o campo portal
    $linkPortal = $dado['link_portal_transparencia'] ?? '';
    if (!empty($linkPortal)) {
        $links['portal'] = $linkPortal;
        $temLink = true;
    }
    if (!empty($dado['link_dados_abertos'])) {
        $links['dados'] = $dado['link_dados_abertos'];
        $temLink = true;
    }
    if (!empty($dado['link_e_sic'])) {
        $links['sic'] = $dado['link_e_sic'];
        $temLink = true;
    }
    if (!empty($dado['link_e_ouve'])) {
        $links['ouv'] = $dado['link_e_ouve'];
        $temLink = true;
    }
    
    if ($temLink) {
        $estadosComLinks[$dado['uf']] = $links;
    }
}

// Log para debug
error_log("Estados com links: " . print_r($estadosComLinks, true));

// Debug adicional: mostrar dados do primeiro estado
if (!empty($dadosLAI)) {
    $primeiroEstado = $dadosLAI[0];
    error_log("Primeiro estado completo: " . print_r($primeiroEstado, true));
    error_log("Links do primeiro estado: portal1=" . ($primeiroEstado['link_portal_transparencia'] ?? 'null') . 
              
              ", dados=" . ($primeiroEstado['link_dados_abertos'] ?? 'null') . 
              ", sic=" . ($primeiroEstado['link_e_sic'] ?? 'null') . 
              ", ouv=" . ($primeiroEstado['link_e_ouve'] ?? 'null'));
}

// Calcular estatísticas completas
$totalEstados = count($dadosLAI);
$comPortaria = 0;
$semPortaria = 0;
$comCargoInformado = 0;
$semCargoInformado = 0;
$comPortal = 0;
$semPortal = 0;
$comExperiencia = 0;
$semExperiencia = 0;
$comResponsavel = 0;
$semResponsavel = 0;

// Arrays para armazenar os estados de cada condição
$estadosComPortaria = [];
$estadosSemPortaria = [];
$estadosComCargo = [];
$estadosSemCargo = [];
$estadosComPortal = [];
$estadosSemPortal = [];
$estadosComExperiencia = [];
$estadosSemExperiencia = [];
$estadosComResponsavel = [];
$estadosSemResponsavel = [];

$grauAdequacao = [
    'baixo' => 0,
    'básico' => 0,
    'intermediário' => 0,
    'adequado' => 0,
    'excelente' => 0
];
$estadosGrauAdequacaoSimples = [
    'baixo' => [],
    'básico' => [],
    'intermediário' => [],
    'adequado' => [],
    'excelente' => []
];

$estadosGrauAdequacao = [
    'baixo' => [],
    'básico' => [],
    'intermediário' => [],
    'adequado' => [],
    'excelente' => []
];

// Estatísticas LAI
$comPortariaLAI = 0;
$comPortalLAI = 0;
$cursoCapacitacaoLAI = [
    'nao_capacitado' => 0,
    'basico' => 0,
    'intermediario' => 0,
    'avancado' => 0,
    'nao_informado' => 0
];
$estadosCapacitacaoLAI = [
    'nao_capacitado' => [],
    'basico' => [],
    'intermediario' => [],
    'avancado' => [],
    'nao_informado' => []
];

// Estatísticas LGPD
$comPoliticaPrivacidade = 0;
$comRelatorioImpacto = 0;
$inventarioDados = [
    'não iniciado' => 0,
    'em andamento' => 0,
    'concluído' => 0,
    'nao_informado' => 0
];
$estadosInventarioDados = [
    'não iniciado' => [],
    'em andamento' => [],
    'concluído' => [],
    'nao_informado' => []
];

// Cálculos adicionais para os gráficos
$anoAtualizacao = [];
$tipoPortal = [
    'proprio' => 0,
    'implanta' => 0,
    'empresa_terceirizada' => 0,
    'nao_tem' => 0
];
$estadosTipoPortal = [
    'proprio' => [],
    'implanta' => [],
    'empresa_terceirizada' => [],
    'nao_tem' => []
];
$nivelAutopercepcao = [
    'nao_apto' => 0,
    'parcialmente_apto' => 0,
    'apto' => 0,
    'nao_informado' => 0
];
$estadosAutopercepcao = [
    'nao_apto' => [],
    'parcialmente_apto' => [],
    'apto' => [],
    'nao_informado' => []
];
$indiceTransparenciaAtiva = [
    'baixo' => 0,
    'básico' => 0,
    'intermediário' => 0,
    'adequado' => 0,
    'excelente' => 0
];
$estadosTransparenciaAtiva = [
    'baixo' => [],
    'básico' => [],
    'intermediário' => [],
    'adequado' => [],
    'excelente' => []
];
$indiceTransparenciaPassiva = [
    'baixo' => 0,
    'básico' => 0,
    'intermediário' => 0,
    'adequado' => 0,
    'excelente' => 0
];
$estadosTransparenciaPassiva = [
    'baixo' => [],
    'básico' => [],
    'intermediário' => [],
    'adequado' => [],
    'excelente' => []
];
$tempoResposta = [];
$sistemaAtosNormativos = [
    'proprio' => 0,
    'sisato' => 0,
    'nenhum' => 0
];
$estadosSistemaAtos = [
    'proprio' => [],
    'sisato' => [],
    'nenhum' => []
];
$linksESIC = 0;
$linksEOUV = 0;
$estadosComESIC = [];
$estadosComEOUV = [];
$estadosComDadosAbertos = [];
$satisfacaoTCU = [
    'ruim' => 0,
    'regular' => 0,
    'bom' => 0,
    'ótimo' => 0,
    'excelente' => 0
];
$estadosSatisfacaoTCU = [
    'ruim' => [],
    'regular' => [],
    'bom' => [],
    'ótimo' => [],
    'excelente' => []
];
$dadosAbertos = 0;
$setoresAlimentacao = [
    'Centralizado' => 0,
    'TI' => 0,
    'SISDOC' => 0,
    'Secretaria' => 0,
    'Contabilidade' => 0,
    'Recursos Humanos' => 0,
    'Passagens/Diarias' => 0,
    'Patrimonio' => 0,
    'Compras/Contratos/Licitações' => 0,
    'Jurídico' => 0,
    'Comunicação' => 0,
    'Superintendência' => 0,
    'Auditoria Interna' => 0,
    'Diversos' => 0,
    'Nenhum' => 0
];
$treinamentoLGPD = [];

foreach ($dadosLAI as $dado) {
    // Lógica da portaria: se portaria_oficial_lai tem valor = Sim, senão = Não
    if (!empty(trim($dado['portaria_oficial_lai']))) {
        $comPortaria++;
        $comPortariaLAI++;
        $estadosComPortaria[] = $dado['uf'];
    } else {
        $semPortaria++;
        $estadosSemPortaria[] = $dado['uf'];
    }
    
    // Verificar se tem cargo informado (nome_autoridade_oficial_lai preenchido)
    if (!empty(trim($dado['nome_autoridade_oficial_lai']))) {
        $comCargoInformado++;
        $estadosComCargo[] = $dado['uf'];
    } else {
        $semCargoInformado++;
        $estadosSemCargo[] = $dado['uf'];
    }
    
    // Verificar se tem portal próprio
    if ($dado['tipo_portal_lai'] == 'implanta') {
        $comPortal++;
        $comPortalLAI++;
        $estadosComPortal[] = $dado['uf'];
    } else {
        $semPortal++;
        $estadosSemPortal[] = $dado['uf'];
    }
    
    // Verificar se tem experiência (nivel_autopercepcao_lai)
    if ($dado['nivel_autopercepcao_lai'] == 'apto') {
        $comExperiencia++;
        $estadosComExperiencia[] = $dado['uf'];
    } else {
        $semExperiencia++;
        $estadosSemExperiencia[] = $dado['uf'];
    }
    
    // Verificar se tem responsável (nome_encarregado_lgpd)
    if (!empty(trim($dado['nome_encarregado_lgpd']))) {
        $comResponsavel++;
        $estadosComResponsavel[] = $dado['uf'];
    } else {
        $semResponsavel++;
        $estadosSemResponsavel[] = $dado['uf'];
    }
    
    // Grau de adequação à LGPD
    $grau = $dado['grau_adequacao_lgpd'] ?? 'baixo';
    $grauAdequacao[$grau]++;
    $estadosGrauAdequacao[$grau][] = $dado['uf'];
    $estadosGrauAdequacaoSimples[$grau][] = $dado['uf'];
    
    // Estatísticas LAI
    $capacitacao = $dado['curso_capacitacao_lai'] ?? 'nao_capacitado';
    if (isset($cursoCapacitacaoLAI[$capacitacao])) {
    $cursoCapacitacaoLAI[$capacitacao]++;
        $estadosCapacitacaoLAI[$capacitacao][] = $dado['uf'];
    } else {
        $cursoCapacitacaoLAI['nao_informado']++;
        $estadosCapacitacaoLAI['nao_informado'][] = $dado['uf'];
    }
    
    // Estatísticas LGPD
    if (!empty($dado['politica_privacidade_link'])) {
        $comPoliticaPrivacidade++;
    }
    
    if ($dado['relatorio_impacto_prodados'] == '1') {
        $comRelatorioImpacto++;
    }
    
    $inventario = $dado['inventario_dados_status'] ?? 'nao_informado';
    if (isset($inventarioDados[$inventario])) {
    $inventarioDados[$inventario]++;
        $estadosInventarioDados[$inventario][] = $dado['uf'];
    } else {
        $inventarioDados['nao_informado']++;
        $estadosInventarioDados['nao_informado'][] = $dado['uf'];
    }
    
    // Ano de atualização
    if (!empty($dado['ano_atualizacao_dados_lai'])) {
        $ano = $dado['ano_atualizacao_dados_lai'];
        $anoAtualizacao[$ano] = ($anoAtualizacao[$ano] ?? 0) + 1;
    }
    
    // Tipo de portal
    $tipo = $dado['tipo_portal_lai'] ?? 'nao_tem';
    if (isset($tipoPortal[$tipo])) {
        $tipoPortal[$tipo]++;
        $estadosTipoPortal[$tipo][] = $dado['uf'];
    } else {
        $tipoPortal['nao_tem']++;
        $estadosTipoPortal['nao_tem'][] = $dado['uf'];
    }
    
    // Nível de autopercepção
    $nivel = $dado['nivel_autopercepcao_lai'] ?? 'nao_informado';
    if (isset($nivelAutopercepcao[$nivel])) {
        $nivelAutopercepcao[$nivel]++;
        $estadosAutopercepcao[$nivel][] = $dado['uf'];
    } else {
        $nivelAutopercepcao['nao_informado']++;
        $estadosAutopercepcao['nao_informado'][] = $dado['uf'];
    }
    
    // Índices de transparência
    $ativa = $dado['indice_transparencia_ativa'] ?? 'baixo';
    if (isset($indiceTransparenciaAtiva[$ativa])) {
        $indiceTransparenciaAtiva[$ativa]++;
        $estadosTransparenciaAtiva[$ativa][] = $dado['uf'];
    } else {
        $indiceTransparenciaAtiva['baixo']++;
        $estadosTransparenciaAtiva['baixo'][] = $dado['uf'];
    }
    
    $passiva = $dado['indice_transparencia_passiva'] ?? 'baixo';
    if (isset($indiceTransparenciaPassiva[$passiva])) {
        $indiceTransparenciaPassiva[$passiva]++;
        $estadosTransparenciaPassiva[$passiva][] = $dado['uf'];
    } else {
        $indiceTransparenciaPassiva['baixo']++;
        $estadosTransparenciaPassiva['baixo'][] = $dado['uf'];
    }
    
    // Tempo de resposta
    if (!empty($dado['tempo_medio_resposta_pedido'])) {
        $tempoResposta[] = (int)$dado['tempo_medio_resposta_pedido'];
    }
    
    // Sistema de atos normativos
    $sistema = $dado['sistema_atos_normativos_proprio'] ?? 'nenhum';
    if ($sistema == 'sim') {
        $sistemaAtosNormativos['proprio']++;
        $estadosSistemaAtos['proprio'][] = $dado['uf'];
    } elseif ($dado['interesse_sisato_cfo'] == 'sim') {
        $sistemaAtosNormativos['sisato']++;
        $estadosSistemaAtos['sisato'][] = $dado['uf'];
    } else {
        $sistemaAtosNormativos['nenhum']++;
        $estadosSistemaAtos['nenhum'][] = $dado['uf'];
    }
    
    // Links
    if (!empty($dado['link_e_sic'])) {
        $linksESIC++;
        $estadosComESIC[] = $dado['uf'];
    }
    if (!empty($dado['link_e_ouve'])) {
        $linksEOUV++;
        $estadosComEOUV[] = $dado['uf'];
    }
    
    // Satisfação TCU
    $satisfacao = $dado['satisfacao_dados_abertos_tcu'] ?? 'regular';
    if (isset($satisfacaoTCU[$satisfacao])) {
        $satisfacaoTCU[$satisfacao]++;
        $estadosSatisfacaoTCU[$satisfacao][] = $dado['uf'];
    } else {
        $satisfacaoTCU['regular']++;
        $estadosSatisfacaoTCU['regular'][] = $dado['uf'];
    }
    
    // Dados abertos
    if (!empty($dado['link_dados_abertos'])) {
        $dadosAbertos++;
        $estadosComDadosAbertos[] = $dado['uf'];
    }
    
    // Setores de alimentação
    if (!empty($dado['setores_alimentacao_transparencia'])) {
        $setores = explode(',', $dado['setores_alimentacao_transparencia']);
        foreach ($setores as $setor) {
            $setor = trim($setor);
            if (isset($setoresAlimentacao[$setor])) {
                $setoresAlimentacao[$setor]++;
            }
        }
    }
    
    // Treinamento LGPD
    if (!empty($dado['ultimo_treinamento_lgpd'])) {
        $ano = date('Y', strtotime($dado['ultimo_treinamento_lgpd']));
        $treinamentoLGPD[$ano] = ($treinamentoLGPD[$ano] ?? 0) + 1;
    }
}

// Ordenar arrays
ksort($anoAtualizacao);
ksort($treinamentoLGPD);

// Calcular percentuais
$percentualComPortariaLAI = round(($comPortariaLAI / $totalEstados) * 100, 1);
$percentualComPortalLAI = round(($comPortalLAI / $totalEstados) * 100, 1);
$percentualComPoliticaPrivacidade = round(($comPoliticaPrivacidade / $totalEstados) * 100, 1);
$percentualComRelatorioImpacto = round(($comRelatorioImpacto / $totalEstados) * 100, 1);

// Calcular estatísticas de tempo de resposta
$tempoRespostaStats = [];
if (!empty($tempoResposta)) {
    sort($tempoResposta);
    $tempoRespostaStats = [
        'min' => min($tempoResposta),
        'max' => max($tempoResposta),
        'media' => round(array_sum($tempoResposta) / count($tempoResposta), 1),
        'mediana' => $tempoResposta[floor(count($tempoResposta) / 2)]
    ];
}

// Definir regiões para o radar
$regioes = [
    'Norte' => ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
    'Nordeste' => ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
    'Centro-Oeste' => ['DF', 'GO', 'MT', 'MS'],
    'Sudeste' => ['ES', 'MG', 'RJ', 'SP'],
    'Sul' => ['PR', 'RS', 'SC']
];

$radarRegioes = [];
foreach ($regioes as $regiao => $estados) {
    $dadosRegiao = array_filter($dadosLAI, function($dado) use ($estados) {
        return in_array($dado['uf'], $estados);
    });
    
    if (!empty($dadosRegiao)) {
        $total = count($dadosRegiao);
        $comPortaria = count(array_filter($dadosRegiao, function($d) { return !empty($d['portaria_oficial_lai']); }));
        $comPortal = count(array_filter($dadosRegiao, function($d) { return $d['tipo_portal_lai'] == 'proprio'; }));
        $comDPO = count(array_filter($dadosRegiao, function($d) { return !empty($d['nome_encarregado_lgpd']); }));
        $comInventario = count(array_filter($dadosRegiao, function($d) { return $d['inventario_dados_status'] == 'concluído'; }));
        $comRIPD = count(array_filter($dadosRegiao, function($d) { return $d['relatorio_impacto_prodados'] == '1'; }));
        
        $radarRegioes[$regiao] = [
            'portaria' => round(($comPortaria / $total) * 100, 1),
            'portal' => round(($comPortal / $total) * 100, 1),
            'dpo' => round(($comDPO / $total) * 100, 1),
            'inventario' => round(($comInventario / $total) * 100, 1),
            'ripd' => round(($comRIPD / $total) * 100, 1)
        ];
    }
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4 text-primary">
                <i class="fas fa-chart-line mr-2"></i>
                Relatório LAI - Lei de Acesso à Informação e LGPD Lei Geral de Proteção de Dados
            </h2>
            <p class="text-muted mb-4">
                Análise da adequação dos Conselhos Regionais de Odontologia à Lei de Acesso à Informação e Lei Geral de Proteção de Dados
            </p>
        </div>
    </div>

    <!-- Identificador LAI -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="flex-grow-1" style="height: 1px; background: linear-gradient(to right, #b3d9f2, transparent);"></div>
                <div class="px-3">
                    <span class="badge badge-light" style="font-size: 0.85rem; padding: 0.5rem 1rem; color: #4a90b8; border: 1px solid #b3d9f2;">
                        <i class="fas fa-info-circle mr-2"></i>
                        Lei de Acesso à Informação (LAI)
                    </span>
                </div>
                <div class="flex-grow-1" style="height: 1px; background: linear-gradient(to left, #b3d9f2, transparent);"></div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo LAI -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stats bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Portaria LAI</h6>
                            <h4><?= $comPortariaLAI ?>/<?= $totalEstados ?></h4>
                            <small><?= $percentualComPortariaLAI ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Capacitados</h6>
                            <h4><?= $cursoCapacitacaoLAI['avancado'] + $cursoCapacitacaoLAI['intermediario'] ?>/<?= $totalEstados ?></h4>
                            <small><?= round((($cursoCapacitacaoLAI['avancado'] + $cursoCapacitacaoLAI['intermediario']) / $totalEstados) * 100, 1) ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Portal Implanta</h6>
                            <h4><?= $comPortalLAI ?>/<?= $totalEstados ?></h4>
                            <small><?= $percentualComPortalLAI ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-globe fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Dados Abertos</h6>
                            <h4><?= $dadosAbertos ?>/<?= $totalEstados ?></h4>
                            <small><?= round(($dadosAbertos / $totalEstados) * 100, 1) ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-database fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Separador entre LAI e LGPD -->
    <div class="row my-5">
        <div class="col-12">
            <div class="d-flex align-items-center mb-3">
                <div class="flex-grow-1" style="height: 1px; background: linear-gradient(to right, #f2b3c4, transparent);"></div>
                <div class="px-3">
                    <span class="badge badge-light" style="font-size: 0.85rem; padding: 0.5rem 1rem; color: #b85a6b; border: 1px solid #f2b3c4;">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Lei Geral de Proteção de Dados (LGPD)
                    </span>
                </div>
                <div class="flex-grow-1" style="height: 1px; background: linear-gradient(to left, #f2b3c4, transparent);"></div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo LGPD -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stats bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">DPO Nomeado</h6>
                            <h4><?= $comResponsavel ?>/<?= $totalEstados ?></h4>
                            <small><?= round(($comResponsavel / $totalEstados) * 100, 1) ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-shield fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Política de Privacidade</h6>
                            <h4><?= $comPoliticaPrivacidade ?>/<?= $totalEstados ?></h4>
                            <small><?= $percentualComPoliticaPrivacidade ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Inventário Concluído</h6>
                            <h4><?= $inventarioDados['concluído'] ?>/<?= $totalEstados ?></h4>
                            <small><?= round(($inventarioDados['concluído'] / $totalEstados) * 100, 1) ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stats bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Relatório de Impacto</h6>
                            <h4><?= $comRelatorioImpacto ?>/<?= $totalEstados ?></h4>
                            <small><?= $percentualComRelatorioImpacto ?>%</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapa Interativo do Brasil -->
    <div class="row mb-6">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-map-marked-alt mr-2"></i>
                        Mapa Interativo do Brasil
                    </h6>
                </div>
                <div class="card-body d-flex flex-column">
                    <div id="mapa-brasil" style="width: 100%; height: 300px; position: relative; flex: 1;">
                        <div id="chart_div" style="width: 100%; height: 100%;"></div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Clique nos estados para ver informações detalhadas
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100" style="overflow: hidden; max-width: 100%;">
                <div class="card-header" style="overflow: hidden; max-width: 100%; word-wrap: break-word;">
                    <h6 class="card-title mb-0" style="word-wrap: break-word; overflow-wrap: break-word; max-width: 100%;">
                        <i class="fas fa-info-circle mr-2"></i>
                        Informações do Estado - <span id="estado-sigla" class="badge badge-primary ml-2"></span>
                    </h6>
                </div>
                <div class="card-body" id="info-estado" style="max-height: 400px; overflow-y: auto; overflow-x: hidden; width: 100%; box-sizing: border-box;">
                    <div class="text-center text-muted">
                        <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                        <p>Selecione um estado no mapa para ver suas informações</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<br>
    <!-- A. VISÃO GERAL DO PAÍS -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">
                <i class="fas fa-globe-americas mr-2"></i>
                A. Visão Geral dos CRO (LAI)
            </h4>
        </div>
    </div>

    <!-- 1-4. Gráficos de Visão Geral -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-pie mr-2"></i>
                        1. Portarias LAI
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="portariasChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-doughnut mr-2"></i>
                        2. Tipo Portal
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="tipoPortalChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>
                        3. Capacitação
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="capacitacaoChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>
                        4. Auto-percepção
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="autopercepcaoChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- B. CUMPRIMENTO LAI -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">
                <i class="fas fa-file-alt mr-2"></i>
                B. Cumprimento LAI
            </h4>
        </div>
    </div>

    <!-- 5-8. Gráficos de Cumprimento LAI -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line mr-2"></i>
                        5. Transparência Ativa
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="transparenciaAtivaChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line mr-2"></i>
                        6. Transparência Passiva
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="transparenciaPassivaChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-clock mr-2"></i>
                        7. Tempo de Resposta
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="tempoRespostaChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-gavel mr-2"></i>
                        8. Atos Normativos
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="atosNormativosChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- C. CUMPRIMENTO LGPD -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">
                <i class="fas fa-shield-alt mr-2"></i>
                C. Cumprimento LGPD
            </h4>
        </div>
    </div>

    <!-- 9-12. Gráficos de Cumprimento LGPD -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar mr-2"></i>
                        9. Grau de Adequação
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="grauAdequacaoChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-clipboard-list mr-2"></i>
                        10. Inventário de Dados
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="inventarioDadosChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-area mr-2"></i>
                        11. Treinamentos LGPD
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="treinamentoLGPDChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-radar-chart mr-2"></i>
                        12. Análise Regional
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="analiseRegionalChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- D. SISTEMAS E DADOS -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="text-primary mb-3">
                <i class="fas fa-server mr-2"></i>
                D. Sistemas e Dados
            </h4>
        </div>
    </div>

    <!-- 13-16. Gráficos de Sistemas e Dados -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-star mr-2"></i>
                        13. Satisfação TCU
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="satisfacaoTCUChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        14. Atualização Dados
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="atualizacaoDadosChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-users mr-2"></i>
                        15. Setores Alimentação
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="setoresAlimentacaoChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-link mr-2"></i>
                        16. Links e Canais
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="linksCanaisChart" width="200" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TABELAS MODERNAS LAI / LGPD ============ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-header">
                <h4 class="text-primary mb-1">
                <i class="fas fa-table mr-2"></i>
                Dados Detalhados - LAI e LGPD
            </h4>
                <p class="text-muted mb-4">Informações completas sobre transparência e proteção de dados</p>
            </div>
        </div>
    </div>

    <!-- Navegação Moderna -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-tabs-container">
                <ul class="nav nav-pills modern-nav-pills" id="modernTab" role="tablist">
                <li class="nav-item" role="presentation">
                        <a class="nav-link active modern-tab-lai" id="modern-lai-tab" data-toggle="pill" href="#modern-lai" role="tab" aria-controls="modern-lai" aria-selected="true">
                            <div class="tab-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="tab-content">
                                <div class="tab-title">Lei de Acesso à Informação</div>
                                <div class="tab-subtitle">Autoridades e Transparência</div>
                            </div>
                            <div class="tab-count">
                                <span class="badge badge-light"><?= count($dadosLAI) ?></span>
                            </div>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                        <a class="nav-link modern-tab-lgpd" id="modern-lgpd-tab" data-toggle="pill" href="#modern-lgpd" role="tab" aria-controls="modern-lgpd" aria-selected="false">
                            <div class="tab-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="tab-content">
                                <div class="tab-title">Lei Geral de Proteção de Dados</div>
                                <div class="tab-subtitle">DPOs e Governança</div>
                            </div>
                            <div class="tab-count">
                                <span class="badge badge-light"><?= count($dadosLAI) ?></span>
                            </div>
                    </a>
                </li>
            </ul>
                </div>
        </div>
    </div>

    <!-- Conteúdo das Tabelas -->
    <div class="tab-content modern-tab-content" id="modernTabContent">
        <!-- ============ TABELA LAI ============ -->
        <div class="tab-pane fade show active" id="modern-lai" role="tabpanel" aria-labelledby="modern-lai-tab">
            <!-- Separador LAI -->
            <div class="row mb-2">
                <div class="col-12">
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                        <i class="fas fa-info-circle mr-2"></i>
                        Lei de Acesso à Informação (LAI) - Dados Detalhados
                    </h6>
                </div>
            </div>
            
            <div class="modern-table-container">
                <div class="table-header">
                                            <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="table-title">
                                    <i class="fas fa-file-alt text-info mr-2"></i>
                                    Dados da Lei de Acesso à Informação
                    </h5>
                                <?php if (!empty($estadosComLinks)): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?= count($estadosComLinks) ?> estado(s) com links encontrados
                                    </small>
                                <?php else: ?>
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Nenhum link encontrado no banco de dados
                                    </small>
                                <?php endif; ?>
                </div>
                        <div class="col-md-6 text-right">
                            <div class="table-tools">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#editarModal" data-action="inserir">
                                    <i class="fas fa-plus mr-1"></i>Novo Estado
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="exportTableToExcel('laiTableModern', 'dados-lai')">
                                    <i class="fas fa-file-excel mr-1"></i>Exportar Excel
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="printTable('laiTableModern')">
                                    <i class="fas fa-print mr-1"></i>Imprimir
                        </button>
                    </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive modern-table-wrapper">
                    <table id="laiTableModern" class="table modern-table table-hover">
                        <thead class="modern-thead">
                            <tr>
                                <th class="col-uf">UF</th>
                                <th class="col-autoridade">Autoridade LAI</th>
                                <th class="col-cargo">Cargo</th>
                                <th class="col-portaria">Portaria</th>
                                <th class="col-capacitacao">Capacit.</th>
                                <th class="col-percepcao">Percep.</th>
                                <th class="col-portal">Portal</th>
                                <th class="col-ano">Ano</th>
                                <th class="col-sistema">Sistema</th>
                                <th class="col-link-portal" title="Portal de Transparência"><i class="fas fa-globe"></i></th>
                                <th class="col-link-dados" title="Dados Abertos"><i class="fas fa-database"></i></th>
                                <th class="col-link-sic" title="e-SIC"><i class="fas fa-info-circle"></i></th>
                                <th class="col-link-ouv" title="e-OUV"><i class="fas fa-headset"></i></th>
                                <th class="col-acoes">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
        <?php foreach ($dadosLAI as $row): ?>
                            <tr class="modern-row">
                                <td class="uf-cell">
                                    <span class="uf-badge"><?= htmlspecialchars($row['uf']) ?></span>
                                    </td>
                                <td class="autoridade-cell">
                                    <div class="person-info">
                                        <div class="person-name"><?= htmlspecialchars($row['nome_autoridade_oficial_lai'] ?: 'Não informado') ?></div>
                                        <?php if ($row['email_autoridade_lai']): ?>
                                        <div class="person-email"><?= htmlspecialchars($row['email_autoridade_lai']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    </td>
                                <td class="cargo-cell">
                                    <div class="cargo-info">
                                        <?php if ($row['cargo_oficial_lai']): ?>
                                        <div class="cargo-title"><?= htmlspecialchars($row['cargo_oficial_lai']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['vinculo_oficial_lai']): ?>
                                        <div class="cargo-vinculo"><?= htmlspecialchars($row['vinculo_oficial_lai']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    </td>
                                <td class="portaria-cell">
                                    <?php if ($row['portaria_oficial_lai']): ?>
                                        <span class="status-badge status-success"><?= htmlspecialchars($row['portaria_oficial_lai']) ?></span>
                                        <?php else: ?>
                                        <span class="status-badge status-danger">Não possui</span>
                                        <?php endif; ?>
                                    </td>
                                <td class="capacitacao-cell">
                                    <span class="level-badge level-<?= $row['curso_capacitacao_lai'] ?: 'none' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['curso_capacitacao_lai'] ?: 'Não informado')) ?>
                                    </span>
                                    </td>
                                <td class="percepcao-cell">
                                    <span class="level-badge level-<?= $row['nivel_autopercepcao_lai'] ?: 'none' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['nivel_autopercepcao_lai'] ?: 'Não informado')) ?>
                                    </span>
                                    </td>
                                <td class="portal-cell">
                                    <span class="portal-badge portal-<?= $row['tipo_portal_lai'] ?: 'none' ?>">
                                        <?= ucfirst($row['tipo_portal_lai'] ?: 'Não informado') ?>
                                    </span>
                                    </td>
                                <td class="ano-cell">
                                    <?= $row['ano_atualizacao_dados_lai'] ?: '—' ?>
                                    </td>
                                <td class="sistema-cell">
                                    <span class="status-badge <?= $row['sistema_atos_normativos_proprio'] === 'sim' ? 'status-success' : 'status-secondary' ?>">
                                        <?= $row['sistema_atos_normativos_proprio'] === 'sim' ? 'Próprio' : 'Não possui' ?>
                                    </span>
                                </td>
                                <td class="link-portal-cell">
                                        <?php 
                                    $linkPortal = $row['link_portal_transparencia'] ??  '';
                                    if ($linkPortal): ?>
                                        <a href="<?= htmlspecialchars($linkPortal) ?>" target="_blank" class="link-btn-solo link-portal" title="Portal de Transparência">
                                            <i class="fas fa-globe" aria-hidden="true"></i>
                                            </a>
                                        <?php else: ?>
                                        <span class="link-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                <td class="link-dados-cell">
                    <?php if ($row['link_dados_abertos']): ?>
                                        <a href="<?= htmlspecialchars($row['link_dados_abertos']) ?>" target="_blank" class="link-btn-solo link-dados" title="Dados Abertos">
                                            <i class="fas fa-database" aria-hidden="true"></i>
                                            </a>
                                        <?php else: ?>
                                        <span class="link-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                <td class="link-sic-cell">
                    <?php if ($row['link_e_sic']): ?>
                                        <a href="<?= htmlspecialchars($row['link_e_sic']) ?>" target="_blank" class="link-btn-solo link-sic" title="e-SIC">
                                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                                            </a>
                                        <?php else: ?>
                                        <span class="link-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                <td class="link-ouv-cell">
                    <?php if ($row['link_e_ouve']): ?>
                                        <a href="<?= htmlspecialchars($row['link_e_ouve']) ?>" target="_blank" class="link-btn-solo link-ouv" title="e-OUV">
                                            <i class="fas fa-headset" aria-hidden="true"></i>
                                            </a>
                                        <?php else: ?>
                                        <span class="link-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                <td class="acoes-cell">
                                    <div class="action-buttons">
                                        <button class="action-btn action-view" 
                            data-toggle="modal" 
                            data-target="#detalheModal" 
                            data-uf="<?= $row['uf'] ?>"
                            title="Ver detalhes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <?php if ($isAdmin ?? true): ?>
                                            <button class="action-btn action-edit" 
                                data-toggle="modal" 
                                data-target="#editarModal" 
                                data-uf="<?= $row['uf'] ?>"
                                title="Editar dados">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    </div>
                </td>
                                </tr>
                                <?php endforeach; ?>
                                    </tbody>
                                </table>
                        </div>
                    </div>
                </div>
                
        <!-- ============ TABELA LGPD ============ -->
        <div class="tab-pane fade" id="modern-lgpd" role="tabpanel" aria-labelledby="modern-lgpd-tab">
            <!-- Separador LGPD -->
            <div class="row mb-2">
                <div class="col-12">
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Lei Geral de Proteção de Dados (LGPD) - Dados Detalhados
                    </h6>
                </div>
            </div>
            
            <div class="modern-table-container">
                <div class="table-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="table-title">
                                <i class="fas fa-shield-alt text-danger mr-2"></i>
                                Dados da Lei Geral de Proteção de Dados
                            </h5>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="table-tools">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#editarModal" data-action="inserir">
                                    <i class="fas fa-plus mr-1"></i>Novo Estado
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="exportTableToExcel('lgpdTableModern', 'dados-lgpd')">
                                    <i class="fas fa-file-excel mr-1"></i>Exportar Excel
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="printTable('lgpdTableModern')">
                                    <i class="fas fa-print mr-1"></i>Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive modern-table-wrapper">
                    <table id="lgpdTableModern" class="table modern-table table-hover">
                        <thead class="modern-thead">
                            <tr>
                                <th class="col-uf">UF</th>
                                <th class="col-dpo">Encarregado (DPO)</th>
                                <th class="col-contato">Contato</th>
                                <th class="col-portaria">Portaria</th>
                                <th class="col-adequacao">Adequação</th>
                                <th class="col-inventario">Inventário</th>
                                <th class="col-ripd">RIPD</th>
                                <th class="col-treinamento">Treinamento</th>
                                <th class="col-politica">Política</th>
                                <th class="col-acoes">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
        <?php foreach ($dadosLAI as $row): ?>
                            <tr class="modern-row">
                                <td class="uf-cell">
                                    <span class="uf-badge"><?= htmlspecialchars($row['uf']) ?></span>
                                    </td>
                                <td class="dpo-cell">
                                    <div class="person-info">
                                        <div class="person-name"><?= htmlspecialchars($row['nome_encarregado_lgpd'] ?: 'Não nomeado') ?></div>
                                        <?php if ($row['email_encarregado_lgpd']): ?>
                                        <div class="person-email"><?= htmlspecialchars($row['email_encarregado_lgpd']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    </td>
                                <td class="contato-cell">
                                    <?php if ($row['telefone_encarregado_lgpd']): ?>
                                    <div class="contact-info">
                                        <i class="fas fa-phone mr-1"></i>
                                        <?= htmlspecialchars($row['telefone_encarregado_lgpd']) ?>
                                    </div>
                                                <?php else: ?>
                                        —
                                                <?php endif; ?>
                                            </td>
                                <td class="portaria-cell">
                                    <?php if ($row['portaria_encarregado_lgpd']): ?>
                                        <span class="status-badge status-success"><?= htmlspecialchars($row['portaria_encarregado_lgpd']) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-danger">Não possui</span>
                                    <?php endif; ?>
                                            </td>
                                <td class="adequacao-cell">
                                    <span class="level-badge level-<?= $row['grau_adequacao_lgpd'] ?: 'baixo' ?>">
                                        <?= ucfirst($row['grau_adequacao_lgpd'] ?: 'Baixo') ?>
                                    </span>
                                </td>
                                <td class="inventario-cell">
                                    <span class="status-badge status-<?= 
                                        $row['inventario_dados_status'] === 'concluído' ? 'success' :
                                        ($row['inventario_dados_status'] === 'em andamento' ? 'warning' : 'danger')
                                    ?>">
                                        <?= ucfirst($row['inventario_dados_status'] ?: 'Não iniciado') ?>
                                    </span>
                                </td>
                                <td class="ripd-cell">
                                    <span class="status-badge <?= $row['relatorio_impacto_prodados'] ? 'status-success' : 'status-danger' ?>">
                                        <?= $row['relatorio_impacto_prodados'] ? 'Possui' : 'Não possui' ?>
                                    </span>
                                </td>
                                <td class="treinamento-cell">
                                    <?php if ($row['ultimo_treinamento_lgpd']): ?>
                                        <div class="date-info">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?= date('d/m/Y', strtotime($row['ultimo_treinamento_lgpd'])) ?>
                                        </div>
                                                <?php else: ?>
                                        <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                <td class="politica-cell">
                    <?php if ($row['politica_privacidade_link']): ?>
                                        <a href="<?= htmlspecialchars($row['politica_privacidade_link']) ?>" target="_blank" class="link-btn link-politica" title="Política de Privacidade">
                            <i class="fas fa-shield-alt"></i>
                        </a>
                                                <?php else: ?>
                                        <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                <td class="acoes-cell">
                                    <div class="action-buttons">
                                        <button class="action-btn action-view" 
                            data-toggle="modal" 
                            data-target="#detalheModal" 
                            data-uf="<?= $row['uf'] ?>"
                            title="Ver detalhes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <?php if ($isAdmin ?? true): ?>
                                            <button class="action-btn action-edit" 
                                data-toggle="modal" 
                                data-target="#editarModal" 
                                data-uf="<?= $row['uf'] ?>"
                                title="Editar dados">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    </div>
                </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
            </div>
        </div>
    </div>
</div>

    <!-- ============ MODAL DETALHES ============ -->
    <div class="modal fade" id="detalheModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Estado <span id="detUF"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
                <div class="modal-body" id="detBody">
                    <!-- conteúdo preenchido via JS -->
                            </div>
                        </div>
                    </div>
                        </div>

    <!-- ============ MODAL EDITAR COMPLETO ============ -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form id="formEditar" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit mr-2"></i>Editar Dados do Estado <span id="editUF" class="badge badge-light text-primary"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    
                <div class="modal-body p-4">
                    <!-- Campo UF -->
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">UF do Estado <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="uf" id="inputUF" maxlength="2" style="text-transform: uppercase;" placeholder="Ex: SP" required>
                            <small class="text-muted">Sigla do estado (2 letras)</small>
                        </div>
                    </div>

                    <!-- ========== GRUPO 1: AUTORIDADE DE MONITORAMENTO LAI ========== -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Autoridade de Monitoramento – LAI</h6>
                        </div>
                        <div class="card-body">
                    <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Completo da Autoridade</label>
                                    <input type="text" class="form-control" name="nome_autoridade_oficial_lai" id="inputAutoridade">
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-mail Institucional</label>
                                    <input type="email" class="form-control" name="email_autoridade_lai" id="inputEmailAutoridade">
                        </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefone Institucional</label>
                                    <input type="text" class="form-control" name="telefone_autoridade_lai" id="inputTelefoneAutoridade" placeholder="(xx) xxxx-xxxx">
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cargo ou Função</label>
                                    <input type="text" class="form-control" name="cargo_oficial_lai" id="inputCargo">
                        </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Vínculo Funcional</label>
                                    <input type="text" class="form-control" name="vinculo_oficial_lai" id="inputVinculo" placeholder="Efetivo, Comissão, etc.">
                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nº/Ano da Portaria de Designação</label>
                                    <input type="text" class="form-control" name="portaria_oficial_lai" id="inputPortaria" placeholder="Ex: 123/2024">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nível de Capacitação LAI</label>
                                    <select class="form-control" name="curso_capacitacao_lai" id="inputCapacitacao">
                                        <option value="">Selecione...</option>
                                            <option value="avancado">Avançado</option>
                                        <option value="intermediario">Intermediário</option>
                                        <option value="basico">Básico</option>
                                        <option value="nao_capacitado">Não capacitado</option>
                                </select>
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nível de Autopercepção</label>
                                    <select class="form-control" name="nivel_autopercepcao_lai" id="inputAutopercepcao">
                                        <option value="">Selecione...</option>
                                            <option value="apto">Apto</option>
                                        <option value="parcialmente_apto">Parcialmente apto</option>
                                        <option value="nao_apto">Não apto</option>
                                </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Observações LAI</label>
                                    <textarea class="form-control" name="observacoes_lai" id="inputObsLAI" rows="3" placeholder="Observações complementares sobre LAI"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ========== GRUPO 2: ESTRUTURA DE PORTAL / TRANSPARÊNCIA LAI ========== -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-globe mr-2"></i>Estrutura de Portal / Transparência – LAI</h6>
                        </div>
                        <div class="card-body">
                    <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tipo de Portal</label>
                                    <select class="form-control" name="tipo_portal_lai" id="inputTipoPortal">
                                        <option value="">Selecione...</option>
                                            <option value="proprio">Próprio</option>
                                            <option value="implanta">Implanta</option>
                                            <option value="empresa_terceirizada">Empresa Terceirizada</option>
                                </select>
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ano de Atualização dos Dados</label>
                                    <input type="number" class="form-control" name="ano_atualizacao_dados_lai" id="inputAnoAtualizacao" min="2010" max="2030">
                        </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">URL do Portal de Transparência</label>
                                    <input type="url" class="form-control" name="link_portal_transparencia" id="inputLinkPortal" placeholder="https://...">
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">URL do e-SIC</label>
                                    <input type="url" class="form-control" name="link_e_sic" id="inputLinkESIC" placeholder="https://...">
                        </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">URL do e-OUV</label>
                                    <input type="url" class="form-control" name="link_e_ouve" id="inputLinkEOUV" placeholder="https://...">
                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sistema Próprio de Atos Normativos?</label>
                                    <select class="form-control" name="sistema_atos_normativos_proprio" id="inputSistemaProprio">
                                        <option value="">Selecione...</option>
                                            <option value="sim">Sim</option>
                                        <option value="nao">Não</option>
                                        </select>
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Interesse no SisAto CFO?</label>
                                    <select class="form-control" name="interesse_sisato_cfo" id="inputInteresseSisato">
                                        <option value="">Selecione...</option>
                                            <option value="sim">Sim</option>
                                        <option value="nao">Não</option>
                                        </select>
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Satisfação Dados Abertos TCU</label>
                                    <select class="form-control" name="satisfacao_dados_abertos_tcu" id="inputSatisfacaoTCU">
                                        <option value="">Selecione...</option>
                                            <option value="excelente">Excelente</option>
                                        <option value="ótimo">Ótimo</option>
                                        <option value="bom">Bom</option>
                                        <option value="regular">Regular</option>
                                        <option value="ruim">Ruim</option>
                                        </select>
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">URL Dados Abertos</label>
                                    <input type="url" class="form-control" name="link_dados_abertos" id="inputLinkDados" placeholder="https://...">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Setores que Alimentam o Sistema de Transparência</label>
                            <div class="row">
                                        <div class="col-md-3">
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Centralizado" id="setorCentralizado">
                                                <label class="form-check-label" for="setorCentralizado">Centralizado</label>
                            </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="TI" id="setorTI">
                                                <label class="form-check-label" for="setorTI">TI</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="SISDOC" id="setorSISDOC">
                                                <label class="form-check-label" for="setorSISDOC">SISDOC</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Secretaria" id="setorSecretaria">
                                                <label class="form-check-label" for="setorSecretaria">Secretaria</label>
                                                </div>
                                            </div>
                                        <div class="col-md-3">
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Contabilidade" id="setorContabilidade">
                                                <label class="form-check-label" for="setorContabilidade">Contabilidade</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Recursos Humanos" id="setorRH">
                                                <label class="form-check-label" for="setorRH">Recursos Humanos</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Passagens/Diarias" id="setorPassagens">
                                                <label class="form-check-label" for="setorPassagens">Passagens/Diárias</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Patrimonio" id="setorPatrimonio">
                                                <label class="form-check-label" for="setorPatrimonio">Patrimônio</label>
                                                </div>
                                            </div>
                                        <div class="col-md-3">
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Compras/Contratos/Licitações" id="setorCompras">
                                                <label class="form-check-label" for="setorCompras">Compras/Contratos/Licitações</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Jurídico" id="setorJuridico">
                                                <label class="form-check-label" for="setorJuridico">Jurídico</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Comunicação" id="setorComunicacao">
                                                <label class="form-check-label" for="setorComunicacao">Comunicação</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Superintendência" id="setorSuperintendencia">
                                                <label class="form-check-label" for="setorSuperintendencia">Superintendência</label>
                                                </div>
                                            </div>
                                        <div class="col-md-3">
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Auditoria Interna" id="setorAuditoria">
                                                <label class="form-check-label" for="setorAuditoria">Auditoria Interna</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Diversos" id="setorDiversos">
                                                <label class="form-check-label" for="setorDiversos">Diversos</label>
                                                </div>
                                                <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="setores_alimentacao[]" value="Nenhum" id="setorNenhum">
                                                <label class="form-check-label" for="setorNenhum">Nenhum</label>
                                            </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        </div>
                    </div>
                    
                    <!-- ========== GRUPO 3: INDICADORES DE ATENDIMENTO LAI ========== -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Indicadores de Atendimento – LAI</h6>
                        </div>
                        <div class="card-body">
                    <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Índice Transparência Ativa</label>
                                    <select class="form-control" name="indice_transparencia_ativa" id="inputTransparenciaAtiva">
                                        <option value="">Selecione...</option>
                                        <option value="excelente">Excelente</option>
                                        <option value="adequado">Adequado</option>
                                        <option value="intermediário">Intermediário</option>
                                        <option value="básico">Básico</option>
                                        <option value="baixo">Baixo</option>
                                    </select>
                                    </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Índice Transparência Passiva</label>
                                    <select class="form-control" name="indice_transparencia_passiva" id="inputTransparenciaPassiva">
                                        <option value="">Selecione...</option>
                                        <option value="excelente">Excelente</option>
                                        <option value="adequado">Adequado</option>
                                        <option value="intermediário">Intermediário</option>
                                        <option value="básico">Básico</option>
                                        <option value="baixo">Baixo</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tempo Médio Resposta (dias)</label>
                                    <input type="number" class="form-control" name="tempo_medio_resposta_pedido" id="inputTempoResposta" min="0" max="365">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ========== GRUPO 4: ENCARREGADO DE DADOS LGPD ========== -->
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fas fa-user-shield mr-2"></i>Encarregado de Dados – LGPD</h6>
                        </div>
                        <div class="card-body">
                    <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Completo do Encarregado (DPO)</label>
                                    <input type="text" class="form-control" name="nome_encarregado_lgpd" id="inputEncarregado">
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-mail Oficial do DPO</label>
                                    <input type="email" class="form-control" name="email_encarregado_lgpd" id="inputEmailEncarregado">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefone para Titulares / ANPD</label>
                                    <input type="text" class="form-control" name="telefone_encarregado_lgpd" id="inputTelefoneEncarregado" placeholder="(xx) xxxx-xxxx">
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nº/Ano da Portaria LGPD</label>
                                    <input type="text" class="form-control" name="portaria_encarregado_lgpd" id="inputPortariaLGPD" placeholder="Ex: 456/2024">
                                </div>
                                    </div>
                                </div>
                            </div>
                            
                    <!-- ========== GRUPO 5: GOVERNANÇA DE DADOS LGPD ========== -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-cogs mr-2"></i>Governança de Dados – LGPD</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Grau de Adequação LGPD</label>
                                    <select class="form-control" name="grau_adequacao_lgpd" id="inputGrau">
                                        <option value="">Selecione...</option>
                                            <option value="excelente">Excelente</option>
                                        <option value="adequado">Adequado</option>
                                        <option value="intermediário">Intermediário</option>
                                        <option value="básico">Básico</option>
                                        <option value="baixo">Baixo</option>
                                </select>
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status do Inventário de Dados</label>
                                    <select class="form-control" name="inventario_dados_status" id="inputInventario">
                                        <option value="">Selecione...</option>
                                            <option value="concluído">Concluído</option>
                                        <option value="em andamento">Em andamento</option>
                                        <option value="não iniciado">Não iniciado</option>
                                        </select>
                                    </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">URL da Política de Privacidade</label>
                                    <input type="url" class="form-control" name="politica_privacidade_link" id="inputPoliticaPrivacidade" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Possui Relatório de Impacto (RIPD)?</label>
                                    <select class="form-control" name="relatorio_impacto_prodados" id="inputRelatorioImpacto">
                                        <option value="">Selecione...</option>
                                        <option value="1">Sim</option>
                                        <option value="0">Não</option>
                                        </select>
                                    </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data do Último Treinamento LGPD</label>
                                    <input type="date" class="form-control" name="ultimo_treinamento_lgpd" id="inputUltimoTreinamento">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Canal para Solicitações de Titulares</label>
                                    <input type="text" class="form-control" name="titular_canal_solicitacao" id="inputCanalTitular" placeholder="URL ou e-mail exclusivo">
                                    </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Observações LGPD</label>
                                    <textarea class="form-control" name="observacoes_lgpd" id="inputObsLGPD" rows="3" placeholder="Observações complementares sobre LGPD"></textarea>
                                </div>
                            </div>
                                    </div>
                                </div>
                            </div>
                            
                <div class="modal-footer bg-light">
                    <div id="editMsg" class="me-auto small text-muted"></div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css" />

<!-- Scripts externos -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<script>
console.log('=== INICIANDO GRÁFICOS SIMPLIFICADOS ===');

// Debug das variáveis PHP
console.log('Total Estados:', <?= $totalEstados ?>);
console.log('Com Portaria LAI:', <?= $comPortariaLAI ?>);
console.log('Sem Portaria:', <?= $semPortaria ?>);
console.log('Tipo Portal:', {
    proprio: <?= $tipoPortal['proprio'] ?>,
    implanta: <?= $tipoPortal['implanta'] ?>,
    empresa_terceirizada: <?= $tipoPortal['empresa_terceirizada'] ?>,
    nao_tem: <?= $tipoPortal['nao_tem'] ?>
});

$(document).ready(function() {
    console.log('jQuery carregado');
    
    // Configurações globais para tooltips
    Chart.defaults.plugins.tooltip.displayColors = false;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    Chart.defaults.plugins.tooltip.titleColor = '#fff';
    Chart.defaults.plugins.tooltip.bodyColor = '#fff';
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.bodyFont = {
        size: 12,
        family: 'Arial, sans-serif'
    };
    Chart.defaults.plugins.tooltip.titleFont = {
        size: 14,
        weight: 'bold',
        family: 'Arial, sans-serif'
    };
    
    // Aguardar um pouco para garantir que tudo carregou
    setTimeout(function() {
        console.log('Criando gráficos...');
        
        // 1. Gráfico de Portarias LAI
        const portariasElement = document.getElementById('portariasChart');
        if (portariasElement) {
            try {
                console.log('Criando gráfico Portarias...');
                new Chart(portariasElement, {
                    type: 'pie',
                    data: {
                        labels: ['Com Portaria', 'Sem Portaria'],
                        datasets: [{
                            data: [<?= intval($comPortariaLAI) ?>, <?= intval($semPortaria) ?>],
                            backgroundColor: ['#28a745', '#dc3545'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosComPortaria = <?= json_encode($estadosComPortaria) ?>;
                                        const estadosSemPortaria = <?= json_encode($estadosSemPortaria) ?>;
                                        
                                        let estados = [];
                                        if (context.dataIndex === 0) {
                                            estados = estadosComPortaria;
                                        } else {
                                            estados = estadosSemPortaria;
                                        }
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Portarias criado');
            } catch (error) {
                console.error('❌ Erro Portarias:', error);
            }
        } else {
            console.error('❌ Elemento portariasChart não encontrado');
        }
        
        // 2. Gráfico de Tipo Portal
        const tipoPortalElement = document.getElementById('tipoPortalChart');
        if (tipoPortalElement) {
            try {
                console.log('Criando gráfico Tipo Portal...');
                new Chart(tipoPortalElement, {
                    type: 'doughnut',
                    data: {
                        labels: ['Próprio', 'Implanta', 'Terceirizada', 'Não Tem'],
                        datasets: [{
                            data: [<?= intval($tipoPortal['proprio']) ?>, <?= intval($tipoPortal['implanta']) ?>, <?= intval($tipoPortal['empresa_terceirizada']) ?>, <?= intval($tipoPortal['nao_tem']) ?>],
                            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosTipoPortal = <?= json_encode($estadosTipoPortal) ?>;
                                        const tipos = ['proprio', 'implanta', 'empresa_terceirizada', 'nao_tem'];
                                        const tipoAtual = tipos[context.dataIndex];
                                        const estados = estadosTipoPortal[tipoAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Tipo Portal criado');
            } catch (error) {
                console.error('❌ Erro Tipo Portal:', error);
            }
        } else {
            console.error('❌ Elemento tipoPortalChart não encontrado');
        }
        
        // 3. Gráfico de Capacitação
        const capacitacaoElement = document.getElementById('capacitacaoChart');
        if (capacitacaoElement) {
            try {
                console.log('Criando gráfico Capacitação...');
                new Chart(capacitacaoElement, {
                    type: 'bar',
                    data: {
                        labels: ['Não Capacitado', 'Básico', 'Intermediário', 'Avançado'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($cursoCapacitacaoLAI['nao_capacitado']) ?>, <?= intval($cursoCapacitacaoLAI['basico']) ?>, <?= intval($cursoCapacitacaoLAI['intermediario']) ?>, <?= intval($cursoCapacitacaoLAI['avancado']) ?>],
                            backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosCapacitacao = <?= json_encode($estadosCapacitacaoLAI) ?>;
                                        const niveis = ['nao_capacitado', 'basico', 'intermediario', 'avancado'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosCapacitacao[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Capacitação criado');
            } catch (error) {
                console.error('❌ Erro Capacitação:', error);
            }
        } else {
            console.error('❌ Elemento capacitacaoChart não encontrado');
        }
        
        // 4. Gráfico de Auto-percepção
        const autopercepcaoElement = document.getElementById('autopercepcaoChart');
        if (autopercepcaoElement) {
            try {
                console.log('Criando gráfico Auto-percepção...');
                new Chart(autopercepcaoElement, {
                    type: 'bar',
                    data: {
                        labels: ['Não Apto', 'Parcialmente Apto', 'Apto', 'Não Informado'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($nivelAutopercepcao['nao_apto']) ?>, <?= intval($nivelAutopercepcao['parcialmente_apto']) ?>, <?= intval($nivelAutopercepcao['apto']) ?>, <?= intval($nivelAutopercepcao['nao_informado']) ?>],
                            backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#6c757d'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosAutopercepcao = <?= json_encode($estadosAutopercepcao) ?>;
                                        const niveis = ['nao_apto', 'parcialmente_apto', 'apto', 'nao_informado'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosAutopercepcao[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Auto-percepção criado');
            } catch (error) {
                console.error('❌ Erro Auto-percepção:', error);
            }
        } else {
            console.error('❌ Elemento autopercepcaoChart não encontrado');
        }
        
        // 5. Gráfico de Transparência Ativa
        const transparenciaAtivaElement = document.getElementById('transparenciaAtivaChart');
        if (transparenciaAtivaElement) {
            try {
                console.log('Criando gráfico Transparência Ativa...');
                new Chart(transparenciaAtivaElement, {
                    type: 'bar',
                    data: {
                        labels: ['Baixo', 'Básico', 'Intermediário', 'Adequado', 'Excelente'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($indiceTransparenciaAtiva['baixo']) ?>, <?= intval($indiceTransparenciaAtiva['básico']) ?>, <?= intval($indiceTransparenciaAtiva['intermediário']) ?>, <?= intval($indiceTransparenciaAtiva['adequado']) ?>, <?= intval($indiceTransparenciaAtiva['excelente']) ?>],
                            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosTransparenciaAtiva = <?= json_encode($estadosTransparenciaAtiva) ?>;
                                        const niveis = ['baixo', 'básico', 'intermediário', 'adequado', 'excelente'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosTransparenciaAtiva[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Transparência Ativa criado');
            } catch (error) {
                console.error('❌ Erro Transparência Ativa:', error);
            }
        }

        // 6. Gráfico de Transparência Passiva
        const transparenciaPassivaElement = document.getElementById('transparenciaPassivaChart');
        if (transparenciaPassivaElement) {
            try {
                console.log('Criando gráfico Transparência Passiva...');
                new Chart(transparenciaPassivaElement, {
                    type: 'bar',
                    data: {
                        labels: ['Baixo', 'Básico', 'Intermediário', 'Adequado', 'Excelente'],
    datasets: [{
                            label: 'CROs',
                            data: [<?= intval($indiceTransparenciaPassiva['baixo']) ?>, <?= intval($indiceTransparenciaPassiva['básico']) ?>, <?= intval($indiceTransparenciaPassiva['intermediário']) ?>, <?= intval($indiceTransparenciaPassiva['adequado']) ?>, <?= intval($indiceTransparenciaPassiva['excelente']) ?>],
                            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'],
        borderWidth: 2
    }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosTransparenciaPassiva = <?= json_encode($estadosTransparenciaPassiva) ?>;
                                        const niveis = ['baixo', 'básico', 'intermediário', 'adequado', 'excelente'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosTransparenciaPassiva[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Transparência Passiva criado');
            } catch (error) {
                console.error('❌ Erro Transparência Passiva:', error);
            }
        }

        // 7. Gráfico de Tempo de Resposta
        const tempoRespostaElement = document.getElementById('tempoRespostaChart');
        if (tempoRespostaElement) {
            try {
                console.log('Criando gráfico Tempo de Resposta...');
                const tempoStats = <?= json_encode($tempoRespostaStats) ?>;
                new Chart(tempoRespostaElement, {
                    type: 'bar',
                    data: {
                        labels: ['Mínimo', 'Média', 'Mediana', 'Máximo'],
    datasets: [{
                            label: 'Dias',
        data: [
                                tempoStats.min || 0,
                                tempoStats.media || 0,
                                tempoStats.mediana || 0,
                                tempoStats.max || 0
                            ],
                            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
        borderWidth: 2
    }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfico Tempo de Resposta criado');
            } catch (error) {
                console.error('❌ Erro Tempo de Resposta:', error);
            }
        }

        // 8. Gráfico de Atos Normativos
        const atosNormativosElement = document.getElementById('atosNormativosChart');
        if (atosNormativosElement) {
            try {
                console.log('Criando gráfico Atos Normativos...');
                new Chart(atosNormativosElement, {
                    type: 'doughnut',
                    data: {
                        labels: ['Sistema Próprio', 'SISATO CFO', 'Nenhum'],
    datasets: [{
                            data: [<?= intval($sistemaAtosNormativos['proprio']) ?>, <?= intval($sistemaAtosNormativos['sisato']) ?>, <?= intval($sistemaAtosNormativos['nenhum']) ?>],
                            backgroundColor: ['#28a745', '#17a2b8', '#dc3545'],
        borderWidth: 2
    }]
                    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
                            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                                        const estadosSistemaAtos = <?= json_encode($estadosSistemaAtos) ?>;
                                        const tipos = ['proprio', 'sisato', 'nenhum'];
                                        const tipoAtual = tipos[context.dataIndex];
                                        const estados = estadosSistemaAtos[tipoAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                    }
                }
            }
        }
    }
                });
                console.log('✅ Gráfico Atos Normativos criado');
            } catch (error) {
                console.error('❌ Erro Atos Normativos:', error);
            }
        }

        // 9. Gráfico de Grau de Adequação
        const grauAdequacaoElement = document.getElementById('grauAdequacaoChart');
        if (grauAdequacaoElement) {
            try {
                console.log('Criando gráfico Grau de Adequação...');
                new Chart(grauAdequacaoElement, {
    type: 'bar',
                    data: {
                        labels: ['Baixo', 'Básico', 'Intermediário', 'Adequado', 'Excelente'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($grauAdequacao['baixo']) ?>, <?= intval($grauAdequacao['básico']) ?>, <?= intval($grauAdequacao['intermediário']) ?>, <?= intval($grauAdequacao['adequado']) ?>, <?= intval($grauAdequacao['excelente']) ?>],
                            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'],
                            borderWidth: 2
                        }]
                    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        const estadosGrauAdequacao = <?= json_encode($estadosGrauAdequacaoSimples) ?>;
                                        const niveis = ['baixo', 'básico', 'intermediário', 'adequado', 'excelente'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosGrauAdequacao[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Gráfico Grau de Adequação criado');
            } catch (error) {
                console.error('❌ Erro Grau de Adequação:', error);
            }
        }

        // 10. Gráfico de Inventário de Dados
        const inventarioDadosElement = document.getElementById('inventarioDadosChart');
        if (inventarioDadosElement) {
            try {
                console.log('Criando gráfico Inventário de Dados...');
                new Chart(inventarioDadosElement, {
                    type: 'pie',
                    data: {
                        labels: ['Não Iniciado', 'Em Andamento', 'Concluído', 'Não Informado'],
                        datasets: [{
                            data: [<?= intval($inventarioDados['não iniciado']) ?>, <?= intval($inventarioDados['em andamento']) ?>, <?= intval($inventarioDados['concluído']) ?>, <?= intval($inventarioDados['nao_informado']) ?>],
                            backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#6c757d'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
        plugins: {
                            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                                        const estadosInventario = <?= json_encode($estadosInventarioDados) ?>;
                                        const status = ['não iniciado', 'em andamento', 'concluído', 'nao_informado'];
                                        const statusAtual = status[context.dataIndex];
                                        const estados = estadosInventario[statusAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                    }
                }
            }
        }
    }
                });
                console.log('✅ Gráfico Inventário de Dados criado');
            } catch (error) {
                console.error('❌ Erro Inventário de Dados:', error);
            }
        }

        // 11. Gráfico de Treinamentos LGPD
        const treinamentoLGPDElement = document.getElementById('treinamentoLGPDChart');
        if (treinamentoLGPDElement) {
            try {
                console.log('Criando gráfico Treinamentos LGPD...');
                const treinamentos = <?= json_encode($treinamentoLGPD) ?>;
                const anos = Object.keys(treinamentos).sort();
                const valores = anos.map(ano => treinamentos[ano]);
                
                new Chart(treinamentoLGPDElement, {
                    type: 'line',
                    data: {
                        labels: anos,
                        datasets: [{
                            label: 'Treinamentos',
                            data: valores,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfico Treinamentos LGPD criado');
            } catch (error) {
                console.error('❌ Erro Treinamentos LGPD:', error);
            }
        }

        // 12. Gráfico de Análise Regional (Radar)
        const analiseRegionalElement = document.getElementById('analiseRegionalChart');
        if (analiseRegionalElement) {
            try {
                console.log('Criando gráfico Análise Regional...');
                const radarData = <?= json_encode($radarRegioes) ?>;
                const regioes = Object.keys(radarData);
                const datasets = regioes.map((regiao, index) => ({
                    label: regiao,
                    data: [
                        radarData[regiao].portaria || 0,
                        radarData[regiao].portal || 0,
                        radarData[regiao].dpo || 0,
                        radarData[regiao].inventario || 0,
                        radarData[regiao].ripd || 0
                    ],
                    borderColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1'][index],
                    backgroundColor: ['rgba(0, 123, 255, 0.1)', 'rgba(40, 167, 69, 0.1)', 'rgba(255, 193, 7, 0.1)', 'rgba(220, 53, 69, 0.1)', 'rgba(111, 66, 193, 0.1)'][index],
                    borderWidth: 2
                }));
                
                new Chart(analiseRegionalElement, {
                    type: 'radar',
                    data: {
                        labels: ['Portaria', 'Portal', 'DPO', 'Inventário', 'RIPD'],
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
                console.log('✅ Gráfico Análise Regional criado');
            } catch (error) {
                console.error('❌ Erro Análise Regional:', error);
            }
        }

        // 13. Gráfico de Satisfação TCU
        const satisfacaoTCUElement = document.getElementById('satisfacaoTCUChart');
        if (satisfacaoTCUElement) {
            try {
                console.log('Criando gráfico Satisfação TCU...');
                new Chart(satisfacaoTCUElement, {
                    type: 'bar',
                    data: {
                        labels: ['Ruim', 'Regular', 'Bom', 'Ótimo', 'Excelente'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($satisfacaoTCU['ruim']) ?>, <?= intval($satisfacaoTCU['regular']) ?>, <?= intval($satisfacaoTCU['bom']) ?>, <?= intval($satisfacaoTCU['ótimo']) ?>, <?= intval($satisfacaoTCU['excelente']) ?>],
                            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                                        const estadosSatisfacao = <?= json_encode($estadosSatisfacaoTCU) ?>;
                                        const niveis = ['ruim', 'regular', 'bom', 'ótimo', 'excelente'];
                                        const nivelAtual = niveis[context.dataIndex];
                                        const estados = estadosSatisfacao[nivelAtual];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                    }
                }
            }
        }
    }
                });
                console.log('✅ Gráfico Satisfação TCU criado');
            } catch (error) {
                console.error('❌ Erro Satisfação TCU:', error);
            }
        }

        // 14. Gráfico de Atualização de Dados
        const atualizacaoDadosElement = document.getElementById('atualizacaoDadosChart');
        if (atualizacaoDadosElement) {
            try {
                console.log('Criando gráfico Atualização de Dados...');
                const atualizacao = <?= json_encode($anoAtualizacao) ?>;
                const anos = Object.keys(atualizacao).sort();
                const valores = anos.map(ano => atualizacao[ano]);
                
                new Chart(atualizacaoDadosElement, {
                    type: 'line',
                    data: {
                        labels: anos,
                        datasets: [{
                            label: 'Atualizações',
                            data: valores,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfico Atualização de Dados criado');
            } catch (error) {
                console.error('❌ Erro Atualização de Dados:', error);
            }
        }

        // 15. Gráfico de Setores de Alimentação
        const setoresAlimentacaoElement = document.getElementById('setoresAlimentacaoChart');
        if (setoresAlimentacaoElement) {
            try {
                console.log('Criando gráfico Setores de Alimentação...');
                const setores = <?= json_encode($setoresAlimentacao) ?>;
                const labels = Object.keys(setores);
                const valores = Object.values(setores);
                
                new Chart(setoresAlimentacaoElement, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'CROs',
                            data: valores,
                            backgroundColor: '#17a2b8',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true },
            x: {
                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                }
            }
        },
        plugins: {
                            legend: { display: false }
                        }
                    }
                });
                console.log('✅ Gráfico Setores de Alimentação criado');
            } catch (error) {
                console.error('❌ Erro Setores de Alimentação:', error);
            }
        }

        // 16. Gráfico de Links e Canais
        const linksCanaisElement = document.getElementById('linksCanaisChart');
        if (linksCanaisElement) {
            try {
                console.log('Criando gráfico Links e Canais...');
                new Chart(linksCanaisElement, {
                    type: 'bar',
                    data: {
                        labels: ['e-SIC', 'e-OUV', 'Dados Abertos'],
                        datasets: [{
                            label: 'CROs',
                            data: [<?= intval($linksESIC) ?>, <?= intval($linksEOUV) ?>, <?= intval($dadosAbertos) ?>],
                            backgroundColor: ['#007bff', '#6f42c1', '#20c997'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { display: false },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                                        const estadosLinks = [
                                            <?= json_encode($estadosComESIC) ?>,
                                            <?= json_encode($estadosComEOUV) ?>,
                                            <?= json_encode($estadosComDadosAbertos) ?>
                                        ];
                                        
                                        const estados = estadosLinks[context.dataIndex];
                                        
                                        // Quebrar linha a cada 5 estados
                                        const linhas = [];
                                        for (let i = 0; i < estados.length; i += 5) {
                                            linhas.push(estados.slice(i, i + 5).join(', '));
                                        }
                                        
                                        return ['Estados:'].concat(linhas);
                    }
                }
            }
        }
    }
                });
                console.log('✅ Gráfico Links e Canais criado');
            } catch (error) {
                console.error('❌ Erro Links e Canais:', error);
            }
        }

        console.log('=== TODOS OS 16 GRÁFICOS CONCLUÍDOS ===');
    }, 1000);
});

// Mapa Interativo do Brasil
google.charts.load('current', {
    'packages': ['geochart'],
    'mapsApiKey': 'AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY',
    'language': 'pt-BR'
});

google.charts.setOnLoadCallback(drawRegionsMap);

function drawRegionsMap() {
    const dadosEstados = <?= json_encode($dadosLAI, JSON_UNESCAPED_UNICODE) ?>;
    
    const nomesEstados = {
        'AC': 'Acre', 'AL': 'Alagoas', 'AP': 'Amapá', 'AM': 'Amazonas', 'BA': 'Bahia', 'CE': 'Ceará',
        'DF': 'Distrito Federal', 'ES': 'Espírito Santo', 'GO': 'Goiás', 'MA': 'Maranhão',
        'MT': 'Mato Grosso', 'MS': 'Mato Grosso do Sul', 'MG': 'Minas Gerais', 'PA': 'Pará',
        'PB': 'Paraíba', 'PR': 'Paraná', 'PE': 'Pernambuco', 'PI': 'Piauí', 'RJ': 'Rio de Janeiro',
        'RN': 'Rio Grande do Norte', 'RS': 'Rio Grande do Sul', 'RO': 'Rondônia',
        'RR': 'Roraima', 'SC': 'Santa Catarina', 'SP': 'São Paulo', 'SE': 'Sergipe', 'TO': 'Tocantins'
    };

    // Preparar dados para o mapa - arrayToDataTable espera: ['Estado', valor, tooltipHtml]
    const rows = dadosEstados.map(e => {
        const uf = e.uf;
        const nomeUF = nomesEstados[uf] || uf;
        const tooltip = `<b>${nomeUF} (${uf})</b><br>` +
                       `Autoridade: ${e.nome_autoridade_oficial_lai || '-'}<br>` +
                       `Cargo: ${e.cargo_oficial_lai || '-'}`;
        return [`BR-${uf}`, 1, tooltip];
    });

    const data = google.visualization.arrayToDataTable([
        ['Estado', 'Total', {role: 'tooltip', p: {html: true}}],
        ...rows
    ]);

    const options = {
        region: 'BR',
        resolution: 'provinces',
        colorAxis: {
            colors: ['#e8f4f8', '#1c7ed6']
        },
        datalessRegionColor: '#e9ecef',
        backgroundColor: '#f8f9fa',
        tooltip: {
            isHtml: true
        },
        width: '100%',
        height: 300
    };

    const chart = new google.visualization.GeoChart(document.getElementById('chart_div'));
    chart.draw(data, options);

    // Adicionar evento de clique
    google.visualization.events.addListener(chart, 'regionClick', function(e) {
        const uf = e.region.slice(3, 5); // Remove 'BR-' prefix
        mostrarInfoEstado(uf);
    });
}

function mostrarInfoEstado(uf) {
    const dadosEstados = <?= json_encode($dadosLAI, JSON_UNESCAPED_UNICODE) ?>;
    const estado = dadosEstados.find(e => e.uf === uf);
    const siglaElement = document.getElementById('estado-sigla');
    const infoDiv = document.getElementById('info-estado');

    if (!infoDiv) return;

    siglaElement.textContent = uf;

    if (!estado) {
        infoDiv.innerHTML = '<em>Nenhum dado encontrado para este estado.</em>';
        return;
    }

    // Função para criar botão de link
    function criarBotaoLink(url, texto, icone = 'fas fa-external-link-alt') {
        if (!url) return '-';
        return `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="${icone} mr-1"></i>${texto}
                </a>`;
    }

    // Função para formatar data
    function formatarData(data) {
        if (!data) return '-';
        return new Date(data).toLocaleDateString('pt-BR');
    }

    const html = `
        <div class="estado-detalhes">
            <!-- SEÇÃO LAI -->
            <div class="secao-lai mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2">
                    <i class="fas fa-file-alt mr-2"></i>LEI DE ACESSO À INFORMAÇÃO (LAI)
                </h6>
                
                <!-- Autoridade de Monitoramento -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-user-tie mr-1"></i>Autoridade de Monitoramento
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Nome:</small><br>
                            <strong>${estado.nome_autoridade_oficial_lai || '-'}</strong><br><br>
                            
                            <small class="text-muted">Cargo:</small><br>
                            ${estado.cargo_oficial_lai || '-'}<br><br>
                            
                            <small class="text-muted">Vínculo:</small><br>
                            ${estado.vinculo_oficial_lai || '-'}<br><br>
                                </div>
                        <div class="col-md-6">
                            <small class="text-muted">Email:</small><br>
                            ${estado.email_autoridade_lai || '-'}<br><br>
                            
                            <small class="text-muted">Telefone:</small><br>
                            ${estado.telefone_autoridade_lai || '-'}<br><br>
                            
                            <small class="text-muted">Portaria:</small><br>
                            <span class="badge badge-${estado.portaria_oficial_lai ? 'success' : 'danger'}">
                                ${estado.portaria_oficial_lai || 'Não informado'}
                            </span><br><br>
                            </div>
                        </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Capacitação:</small><br>
                            <span class="badge badge-${
                                estado.curso_capacitacao_lai === 'avancado' ? 'success' :
                                estado.curso_capacitacao_lai === 'intermediario' ? 'primary' :
                                estado.curso_capacitacao_lai === 'basico' ? 'warning' : 'danger'
                            }">
                                ${estado.curso_capacitacao_lai || 'Não capacitado'}
                                    </span>
                                </div>
                        <div class="col-md-6">
                            <small class="text-muted">Auto-percepção:</small><br>
                            <span class="badge badge-${
                                estado.nivel_autopercepcao_lai === 'apto' ? 'success' :
                                estado.nivel_autopercepcao_lai === 'parcialmente_apto' ? 'warning' : 'danger'
                            }">
                                ${estado.nivel_autopercepcao_lai || 'Não informado'}
                                    </span>
                                </div>
                            </div>
                        </div>

                <!-- Estrutura de Portal / Transparência -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-globe mr-1"></i>Estrutura de Portal / Transparência
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Tipo Portal:</small><br>
                            <span class="badge badge-${estado.tipo_portal_lai === 'proprio' ? 'success' : 'warning'}">
                                ${estado.tipo_portal_lai || 'Não informado'}
                            </span><br><br>
                            
                            <small class="text-muted">Última Atualização:</small><br>
                            ${estado.ano_atualizacao_dados_lai || '-'}<br><br>
                            
                            <small class="text-muted">Sistema Atos Normativos:</small><br>
                            <span class="badge badge-${estado.sistema_atos_normativos_proprio === 'sim' ? 'success' : 'danger'}">
                                ${estado.sistema_atos_normativos_proprio === 'sim' ? 'Próprio' : 'Não possui'}
                                    </span>
                                </div>
                        <div class="col-md-6">
                            <small class="text-muted">Interesse SISATO CFO:</small><br>
                            <span class="badge badge-${estado.interesse_sisato_cfo === 'sim' ? 'success' : 'secondary'}">
                                ${estado.interesse_sisato_cfo === 'sim' ? 'Sim' : 'Não'}
                            </span><br><br>
                            
                            <small class="text-muted">Satisfação TCU:</small><br>
                            <span class="badge badge-${
                                estado.satisfacao_dados_abertos_tcu === 'excelente' ? 'success' :
                                estado.satisfacao_dados_abertos_tcu === 'ótimo' ? 'primary' :
                                estado.satisfacao_dados_abertos_tcu === 'bom' ? 'info' :
                                estado.satisfacao_dados_abertos_tcu === 'regular' ? 'warning' : 'danger'
                            }">
                                ${estado.satisfacao_dados_abertos_tcu || 'Não informado'}
                            </span><br><br>
                            
                            <small class="text-muted">Links:</small><br>
                            <div class="btn-group-vertical btn-group-sm">
                                ${criarBotaoLink(estado.link_portal_transparencia, 'Portal', 'fas fa-globe')}
                                ${criarBotaoLink(estado.link_e_sic, 'e-SIC', 'fas fa-inbox')}
                                ${criarBotaoLink(estado.link_e_ouve, 'e-OUV', 'fas fa-comments')}
                                ${criarBotaoLink(estado.link_dados_abertos, 'Dados Abertos', 'fas fa-database')}
                        </div>
                    </div>
                </div>
            </div>
            
                <!-- Métricas de Transparência -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-chart-line mr-1"></i>Métricas de Transparência
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Transparência Ativa:</small><br>
                            <span class="badge badge-${
                                estado.indice_transparencia_ativa === 'excelente' ? 'success' :
                                estado.indice_transparencia_ativa === 'adequado' ? 'primary' :
                                estado.indice_transparencia_ativa === 'intermediário' ? 'info' :
                                estado.indice_transparencia_ativa === 'básico' ? 'warning' : 'danger'
                            }">
                                ${estado.indice_transparencia_ativa || 'Não informado'}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Transparência Passiva:</small><br>
                            <span class="badge badge-${
                                estado.indice_transparencia_passiva === 'excelente' ? 'success' :
                                estado.indice_transparencia_passiva === 'adequado' ? 'primary' :
                                estado.indice_transparencia_passiva === 'intermediário' ? 'info' :
                                estado.indice_transparencia_passiva === 'básico' ? 'warning' : 'danger'
                            }">
                                ${estado.indice_transparencia_passiva || 'Não informado'}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Tempo Médio Resposta:</small><br>
                            <span class="badge badge-info">
                                ${estado.tempo_medio_resposta_pedido ? estado.tempo_medio_resposta_pedido + ' dias' : 'Não informado'}
                            </span>
                        </div>
                    </div>
                        </div>

                <!-- Setores que Alimentam o Sistema -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-users mr-1"></i>Setores que Alimentam o Sistema de Transparência
                    </h6>
                    <div class="setores-badges">
                        ${estado.setores_alimentacao_transparencia ? 
                            estado.setores_alimentacao_transparencia.split(',').map(setor => 
                                `<span class="badge badge-secondary mr-1 mb-1">${setor.trim()}</span>`
                            ).join('') : 
                            '<span class="badge badge-warning">Não informado</span>'
                        }
                        </div>
                    </div>

                <!-- Observações LAI -->
                <div class="subsecao mb-3">
                    <small class="text-muted">Observações LAI:</small><br>
                    <span class="badge badge-${estado.observacoes_lai ? 'info' : 'secondary'}">
                        ${estado.observacoes_lai ? 'Possui observações' : 'Sem observações'}
                    </span>
                    ${estado.observacoes_lai ? `<br><small class="text-muted mt-1">${estado.observacoes_lai}</small>` : ''}
                </div>
            </div>
            
            <!-- SEÇÃO LGPD -->
            <div class="secao-lgpd mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2">
                    <i class="fas fa-shield-alt mr-2"></i>LEI GERAL DE PROTEÇÃO DE DADOS (LGPD)
                    </h6>
                
                <!-- Encarregado de Dados (DPO) -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-user-shield mr-1"></i>Encarregado de Dados (DPO)
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Nome:</small><br>
                            <strong>${estado.nome_encarregado_lgpd || '-'}</strong><br><br>
                            
                            <small class="text-muted">Email:</small><br>
                            ${estado.email_encarregado_lgpd || '-'}<br><br>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Telefone:</small><br>
                            ${estado.telefone_encarregado_lgpd || '-'}<br><br>
                            
                            <small class="text-muted">Portaria:</small><br>
                            <span class="badge badge-${estado.portaria_encarregado_lgpd ? 'success' : 'danger'}">
                                ${estado.portaria_encarregado_lgpd || 'Não informado'}
                            </span><br><br>
                        </div>
                    </div>
                        </div>

                <!-- Governança de Dados -->
                <div class="subsecao mb-3">
                    <h6 class="text-secondary mb-2">
                        <i class="fas fa-cogs mr-1"></i>Governança de Dados
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Grau de Adequação:</small><br>
                            <span class="badge badge-${
                                estado.grau_adequacao_lgpd === 'excelente' ? 'success' :
                                estado.grau_adequacao_lgpd === 'adequado' ? 'primary' :
                                estado.grau_adequacao_lgpd === 'intermediário' ? 'info' :
                                estado.grau_adequacao_lgpd === 'básico' ? 'warning' : 'danger'
                            }">
                                ${estado.grau_adequacao_lgpd || 'Não informado'}
                            </span><br><br>
                            
                            <small class="text-muted">Inventário de Dados:</small><br>
                            <span class="badge badge-${
                                estado.inventario_dados_status === 'concluído' ? 'success' :
                                estado.inventario_dados_status === 'em andamento' ? 'warning' : 'danger'
                            }">
                                ${estado.inventario_dados_status || 'Não iniciado'}
                            </span><br><br>
                            
                            <small class="text-muted">Relatório de Impacto (RIPD):</small><br>
                            <span class="badge badge-${estado.relatorio_impacto_prodados ? 'success' : 'danger'}">
                                ${estado.relatorio_impacto_prodados ? 'Possui' : 'Não possui'}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Último Treinamento:</small><br>
                            ${formatarData(estado.ultimo_treinamento_lgpd)}<br><br>
                            
                            <small class="text-muted">Política de Privacidade:</small><br>
                            ${criarBotaoLink(estado.politica_privacidade_link, 'Acessar', 'fas fa-shield-alt')}<br><br>
                            
                            <small class="text-muted">Canal Titular:</small><br>
                            ${estado.titular_canal_solicitacao || '-'}
                        </div>
                </div>
            </div>
            
                <!-- Observações LGPD -->
                <div class="subsecao mb-3">
                    <small class="text-muted">Observações LGPD:</small><br>
                    <span class="badge badge-${estado.observacoes_lgpd ? 'info' : 'secondary'}">
                        ${estado.observacoes_lgpd ? 'Possui observações' : 'Sem observações'}
                    </span>
                    ${estado.observacoes_lgpd ? `<br><small class="text-muted mt-1">${estado.observacoes_lgpd}</small>` : ''}
            </div>
            </div>
        </div>
    `;
    
    infoDiv.innerHTML = html;
}
</script>

<style>
/* =================== PALETA DE CORES =================== */
:root {
    --azul-primario: #1c7ed6;
    --cinza-fundo: #f8f9fa;
    --cinza-claro: #e9ecef;
    --cinza-borda: #dee2e6;
    --texto-claro: #fff;
    --texto-escuro: #495057;
    --sombra: 0 1px 2px rgba(0,0,0,.05);
    --verde-sim: #37b24d;
    --vermelho-nao: #f03e3e;
    --laranja-warning: #fd7e14;
}

/* =================== IMPORTAÇÃO FONTAWESOME =================== */
@import url('../assets/fontawesome/css/all.min.css');

/* =================== ESTILOS GERAIS =================== */
.card-body canvas {
    max-height: 300px !important;
    max-width: 100% !important;
}

/* =================== GARANTIR FONTAWESOME =================== */
.fas, .far, .fab, .fa {
    font-family: "Font Awesome 5 Free", "Font Awesome 5 Brands", "Font Awesome 5 Pro" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased !important;
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
}

/* Permitir tooltips ultrapassarem os limites dos cards */
.card {
    overflow: visible !important;
}

.card-body {
    overflow: visible !important;
}

.card {
    margin-bottom: 20px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header h6 {
    font-size: 0.9rem;
    margin-bottom: 0;
}

h4.text-primary {
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .card-body canvas {
        max-height: 200px !important;
    }
}

.card-stats {
    transition: transform 0.2s ease-in-out;
}

.card-stats:hover {
    transform: translateY(-2px);
}

.estado-info {
    font-size: 0.9rem;
}

.estado-info ul {
    padding-left: 0;
}

.estado-info li {
    margin-bottom: 0.25rem;
}

.badge {
    font-size: 0.8rem;
}

#chart_div {
    border-radius: 0.25rem;
}

.text-primary {
    color: #007bff !important;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-header .fas {
    color: #007bff;
}

.row.mb-4 {
    margin-bottom: 2rem !important;
}

.container-fluid {
    padding: 1rem;
}

/* Melhorias para os cards de estatísticas */
.card-stats .card-body {
    padding: 1.5rem;
}

.card-stats h4 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.card-stats h6 {
    font-size: 0.9rem;
    font-weight: 500;
    opacity: 0.9;
}

.card-stats small {
    font-size: 0.8rem;
    opacity: 0.8;
}

.card-stats .fas {
    opacity: 0.7;
}

/* Estilos para o painel de detalhes do estado */
#info-estado {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    min-height: 200px;
    font-size: 0.85rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
    overflow-x: hidden !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

#info-estado .estado-detalhes {
    line-height: 1.3;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

#info-estado .secao-lai,
#info-estado .secao-lgpd {
    background-color: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

#info-estado .subsecao {
    border-left: 3px solid #007bff;
    padding-left: 0.75rem;
    margin-bottom: 1rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

#info-estado .subsecao h6 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.5rem;
}

#info-estado .text-muted {
    font-size: 0.75rem;
    color: #6c757d !important;
    font-weight: 500;
}

#info-estado .badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    font-weight: 500;
}

#info-estado .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
    margin-bottom: 0.25rem;
}

#info-estado .btn-group-vertical .btn {
    margin-bottom: 0.25rem;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#info-estado .setores-badges {
    max-height: 80px;
    overflow-y: auto;
}

#info-estado .setores-badges .badge {
    font-size: 0.65rem;
    padding: 0.15rem 0.3rem;
}

/* =================== VALIDAÇÃO DE CAMPOS =================== */
.form-control.is-valid {
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.45-.45 1.93-1.93-1.41-1.41L2.16 4.05.73 2.62 0 3.35l2.3 2.38z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(.375em + .1875rem) center;
    background-size: calc(.75em + .375rem) calc(.75em + .375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 5.8 2.4 2.4m0-2.4-2.4 2.4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(.375em + .1875rem) center;
    background-size: calc(.75em + .375rem) calc(.75em + .375rem);
}

#info-estado strong {
    color: #495057;
    font-weight: 600;
}

#info-estado .row {
    margin-bottom: 0.5rem !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

#info-estado .col-md-6,
#info-estado .col-md-4,
#info-estado .col-12,
#info-estado [class*="col-"] {
    padding-right: 0.5rem !important;
    padding-left: 0.5rem !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
    box-sizing: border-box !important;
}

/* Garantir que textos longos não ultrapassem os limites */
#info-estado * {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

#info-estado strong,
#info-estado .badge,
#info-estado .btn,
#info-estado .text-muted,
#info-estado small,
#info-estado h6,
#info-estado p,
#info-estado div,
#info-estado span {
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

#info-estado .btn-group-vertical {
    width: 100% !important;
    max-width: 100% !important;
}

#info-estado .btn-group-vertical .btn {
    width: 100% !important;
    text-align: left !important;
    max-width: 100% !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

#estado-sigla {
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
}

/* Melhorias no mapa */
#chart_div {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}

.row.mb-4 h4 {
    font-weight: 600;
    color: #fff;
}

/* =================== WRAPPER RESPONSIVO DAS TABELAS =================== */
.tabela-wrapper {
    overflow-x: auto;
    background: var(--cinza-fundo);
    padding: 1rem;
    border-radius: 0.75rem;
    box-shadow: var(--sombra);
    margin-bottom: 1.5rem;
}

/* =================== TABELAS PRINCIPAIS =================== */
.tabela-registros, #laiTable, #lgpdTable {
    width: 100%;
    border-collapse: collapse;
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 0.8125rem;
    color: var(--texto-escuro);
    table-layout: fixed !important;
}

/* Cabeçalho das tabelas */
.tabela-registros thead, #laiTable thead, #lgpdTable thead {
    background: var(--azul-primario);
    color: var(--texto-claro);
}

.tabela-registros th, #laiTable th, #lgpdTable th {
    padding: 0.6rem 0.7rem;
    text-align: center;
    font-weight: 600;
    white-space: nowrap;
    border: 1px solid var(--azul-primario);
    font-size: 10px;
    line-height: 1.2;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Linhas do corpo das tabelas */
.tabela-registros td, #laiTable td, #lgpdTable td {
    padding: 0.5rem 0.6rem;
    border: 1px solid var(--cinza-borda);
    font-size: 0.75rem;
    line-height: 1.2;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tabela-registros tbody tr:nth-child(even), 
#laiTable tbody tr:nth-child(even), 
#lgpdTable tbody tr:nth-child(even) {
    background: var(--cinza-fundo);
}

.tabela-registros tbody tr:hover, 
#laiTable tbody tr:hover, 
#lgpdTable tbody tr:hover {
    background: #e3f2fd;
    transition: 0.15s;
}

/* =================== BADGES PERSONALIZADOS =================== */
.badge {
    display: inline-block;
    padding: 0.25em 0.55em;
    border-radius: 0.45rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--texto-claro);
}

.badge-sim, .badge-success {
    background: var(--verde-sim) !important;
}

.badge-nao, .badge-danger {
    background: var(--vermelho-nao) !important;
}

.badge-warning {
    background: var(--laranja-warning) !important;
}

.badge-primary {
    background: var(--azul-primario) !important;
}

.badge-secondary {
    background: #6c757d !important;
}

/* =================== AJUSTES DATATABLES =================== */
table.dataTable.tabela-registros thead th,
table.dataTable.tabela-registros thead td,
table.dataTable thead th,
table.dataTable thead td {
    border-bottom: none;
}

table.dataTable.tabela-registros.no-footer,
table.dataTable.no-footer {
    border-bottom: 1px solid var(--cinza-borda);
}

#laiTable.dataTable, #lgpdTable.dataTable {
    table-layout: fixed !important;
}

#laiTable.dataTable th, #laiTable.dataTable td,
#lgpdTable.dataTable th, #lgpdTable.dataTable td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* =================== COLUNAS ESPECÍFICAS =================== */
/* Nome da autoridade/encarregado */
#laiTable td:nth-child(2),
#lgpdTable td:nth-child(2) {
    font-size: 0.7rem;
    line-height: 1.2;
    padding: 0.4rem 0.3rem;
    max-width: 120px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    text-align: left;
}

/* Cargo/Vínculo mais compacto */
#laiTable td:nth-child(3) {
    font-size: 0.75rem;
    line-height: 1.2;
    padding: 0.4rem 0.3rem;
    max-width: 100px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Colunas de portaria e badges */
#laiTable td:nth-child(4),
#lgpdTable td:nth-child(5) {
    max-width: 80px;
    padding: 0.4rem 0.3rem;
}

/* Badges menores nas tabelas */
#laiTable .badge,
#lgpdTable .badge {
    font-size: 0.65rem;
    padding: 0.2rem 0.3rem;
}

/* Cargo/vínculo */
#laiTable td:nth-child(3) {
    font-size: 0.65rem;
    line-height: 1.1;
    padding: 0.3rem 0.2rem;
    max-width: 100px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    text-align: left;
}

/* Email */
#lgpdTable td:nth-child(3) {
    font-size: 0.65rem;
    line-height: 1.1;
    padding: 0.3rem 0.2rem;
    max-width: 100px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    text-align: left;
}

/* Bordas verticais */
#laiTable th, #lgpdTable th {
    border-right: 1px solid var(--azul-primario);
}

#laiTable td, #lgpdTable td {
    border-right: 1px solid var(--cinza-borda);
}

/* Última coluna sem borda direita */
#laiTable td:last-child,
#lgpdTable td:last-child,
#laiTable th:last-child,
#lgpdTable th:last-child {
    border-right: none;
}

/* =================== LARGURAS ANTIGAS REMOVIDAS - USANDO NOVAS ACIMA =================== */

.table-responsive {
    border-radius: 0.375rem;
    overflow-x: auto;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white !important;
    border-color: #007bff;
}

.nav-tabs .nav-link {
    color: #007bff;
    border: 1px solid transparent;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    background-color: #f8f9fa;
}

/* =================== CSS RÁPIDO PARA CENTRALIZAÇÃO =================== */
.tabela-registros th {
    text-align: center !important;
    vertical-align: middle !important;
}
.tabela-registros td {
    vertical-align: middle;
    white-space: nowrap;     /* evita quebra que desalinha */
}

/* =================== FORÇAR ALINHAMENTO DEFINITIVO DAS COLUNAS =================== */
#laiTable {
    width: 100% !important;
    table-layout: fixed !important;
}

#laiTable thead th,
#laiTable tbody td {
    padding: 8px 4px !important;
    border: 1px solid #dee2e6 !important;
    text-align: center !important;
    vertical-align: middle !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

/* =================== TABELA LAI MODERNA =================== */
#laiTableModern {
    width: 100% !important;
    table-layout: fixed !important;
}

#laiTableModern thead th,
#laiTableModern tbody td {
    padding: 8px 4px !important;
    border: 1px solid #dee2e6 !important;
    text-align: center !important;
    vertical-align: middle !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

/* Larguras fixas específicas para cada coluna da tabela LAI */
#laiTable th:nth-child(1), #laiTable td:nth-child(1) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }   /* Estado */
#laiTable th:nth-child(2), #laiTable td:nth-child(2) { width: 150px !important; min-width: 150px !important; max-width: 150px !important; text-align: left !important; } /* Autoridade */
#laiTable th:nth-child(3), #laiTable td:nth-child(3) { width: 120px !important; min-width: 120px !important; max-width: 120px !important; text-align: left !important; } /* Cargo */
#laiTable th:nth-child(4), #laiTable td:nth-child(4) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }   /* Portaria */
#laiTable th:nth-child(5), #laiTable td:nth-child(5) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Capacitação */
#laiTable th:nth-child(6), #laiTable td:nth-child(6) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Autopercepção */
#laiTable th:nth-child(7), #laiTable td:nth-child(7) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }   /* Tipo Portal */
#laiTable th:nth-child(8), #laiTable td:nth-child(8) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }   /* Ano */
#laiTable th:nth-child(9), #laiTable td:nth-child(9) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Sistema */
#laiTable th:nth-child(10), #laiTable td:nth-child(10) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; } /* SisAto */
#laiTable th:nth-child(11), #laiTable td:nth-child(11) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; } /* Link Portal */
#laiTable th:nth-child(12), #laiTable td:nth-child(12) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; } /* Link Dados */
#laiTable th:nth-child(13), #laiTable td:nth-child(13) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; } /* E-SIC */
#laiTable th:nth-child(14), #laiTable td:nth-child(14) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; } /* E-OUV */
#laiTable th:nth-child(15), #laiTable td:nth-child(15) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; } /* Ações */

/* Larguras fixas específicas para cada coluna da tabela LAI MODERNA */
#laiTableModern th:nth-child(1), #laiTableModern td:nth-child(1) { width: 45px !important; min-width: 45px !important; max-width: 45px !important; }   /* Estado */
#laiTableModern th:nth-child(2), #laiTableModern td:nth-child(2) { width: 130px !important; min-width: 130px !important; max-width: 130px !important; text-align: left !important; } /* Autoridade */
#laiTableModern th:nth-child(3), #laiTableModern td:nth-child(3) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: left !important; } /* Cargo */
#laiTableModern th:nth-child(4), #laiTableModern td:nth-child(4) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; }   /* Portaria */
#laiTableModern th:nth-child(5), #laiTableModern td:nth-child(5) { width: 75px !important; min-width: 75px !important; max-width: 75px !important; }   /* Capacitação */
#laiTableModern th:nth-child(6), #laiTableModern td:nth-child(6) { width: 75px !important; min-width: 75px !important; max-width: 75px !important; }   /* Percepção */
#laiTableModern th:nth-child(7), #laiTableModern td:nth-child(7) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; }   /* Portal */
#laiTableModern th:nth-child(8), #laiTableModern td:nth-child(8) { width: 60px !important; min-width: 60px !important; max-width: 60px !important; }   /* Ano */
#laiTableModern th:nth-child(9), #laiTableModern td:nth-child(9) { width: 75px !important; min-width: 75px !important; max-width: 75px !important; }   /* Sistema */
#laiTableModern th:nth-child(10), #laiTableModern td:nth-child(10) { width: 40px !important; min-width: 40px !important; max-width: 40px !important; } /* Link Portal */
#laiTableModern th:nth-child(11), #laiTableModern td:nth-child(11) { width: 40px !important; min-width: 40px !important; max-width: 40px !important; } /* Link Dados */
#laiTableModern th:nth-child(12), #laiTableModern td:nth-child(12) { width: 40px !important; min-width: 40px !important; max-width: 40px !important; } /* E-SIC */
#laiTableModern th:nth-child(13), #laiTableModern td:nth-child(13) { width: 40px !important; min-width: 40px !important; max-width: 40px !important; } /* E-OUV */
#laiTableModern th:nth-child(14), #laiTableModern td:nth-child(14) { width: 70px !important; min-width: 70px !important; max-width: 70px !important; } /* Ações */

/* Mesmo para LGPD */
#lgpdTable {
    width: 100% !important;
    table-layout: fixed !important;
}

#lgpdTable thead th,
#lgpdTable tbody td {
    padding: 8px 4px !important;
    border: 1px solid #dee2e6 !important;
    text-align: center !important;
    vertical-align: middle !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

#lgpdTable th:nth-child(1), #lgpdTable td:nth-child(1) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }   /* Estado */
#lgpdTable th:nth-child(2), #lgpdTable td:nth-child(2) { width: 150px !important; min-width: 150px !important; max-width: 150px !important; text-align: left !important; } /* Encarregado */
#lgpdTable th:nth-child(3), #lgpdTable td:nth-child(3) { width: 120px !important; min-width: 120px !important; max-width: 120px !important; text-align: left !important; } /* Email */
#lgpdTable th:nth-child(4), #lgpdTable td:nth-child(4) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* Telefone */
#lgpdTable th:nth-child(5), #lgpdTable td:nth-child(5) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Portaria */
#lgpdTable th:nth-child(6), #lgpdTable td:nth-child(6) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Adequação */
#lgpdTable th:nth-child(7), #lgpdTable td:nth-child(7) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* Política */
#lgpdTable th:nth-child(8), #lgpdTable td:nth-child(8) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Inventário */
#lgpdTable th:nth-child(9), #lgpdTable td:nth-child(9) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }   /* Relatório */
#lgpdTable th:nth-child(10), #lgpdTable td:nth-child(10) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; } /* Treinamento */
#lgpdTable th:nth-child(11), #lgpdTable td:nth-child(11) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; } /* Canal */
#lgpdTable th:nth-child(12), #lgpdTable td:nth-child(12) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; } /* Ações */

/* Desabilitar estilos DataTables que interferem */
.dataTables_wrapper .dataTable {
    width: 100% !important;
    table-layout: fixed !important;
}

.dataTables_wrapper .dataTable thead th,
.dataTables_wrapper .dataTable tbody td {
    box-sizing: border-box !important;
}

/* =================== ESTILOS MODERNOS PARA TABELAS =================== */

/* Cabeçalho da Seção */
.section-header {
    text-align: center;
    margin-bottom: 2rem;
}

.section-header h4 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* Navegação Moderna */
.modern-tabs-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.modern-nav-pills {
    border: none;
    background: transparent;
}

.modern-nav-pills .nav-item {
    flex: 1;
    margin: 0 0.5rem;
}

.modern-nav-pills .nav-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem;
    border: 2px solid transparent;
    background: white;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.modern-nav-pills .nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    border-color: rgba(0, 123, 255, 0.3);
}

.modern-nav-pills .nav-link.active.modern-tab-lai {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border-color: #17a2b8;
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
}

.modern-nav-pills .nav-link.active.modern-tab-lgpd {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-color: #dc3545;
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
}

.tab-icon {
    font-size: 1.5rem;
    margin-right: 1rem;
}

.tab-content {
    flex: 1;
    text-align: left;
}

.tab-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.tab-subtitle {
    font-size: 0.9rem;
    opacity: 0.8;
}

.tab-count {
    margin-left: 1rem;
}

.tab-count .badge {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

/* Container da Tabela */
.modern-table-container {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #dee2e6;
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #495057;
}

.table-tools .btn {
    margin-left: 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
}

/* Wrapper da Tabela */
.modern-table-wrapper {
    background: white;
    border-radius: 0 0 1rem 1rem;
}

/* Tabela Moderna */
.modern-table {
    margin: 0;
    font-size: 0.9rem;
    border: none;
}

.modern-table .modern-thead {
    background: linear-gradient(135deg, #343a40 0%, #495057 100%);
    color: white;
}

.modern-table .modern-thead th {
    padding: 1rem 0.75rem;
    border: none;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    vertical-align: middle;
    white-space: nowrap;
}

.modern-table .modern-row {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f8f9fa;
}

.modern-table .modern-row:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transform: scale(1.01);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modern-table .modern-row td {
    padding: 1rem 0.75rem;
    border: none;
    vertical-align: middle;
}

/* Células Específicas */
.uf-cell {
    text-align: center;
    width: 60px;
}

.uf-badge {
    display: inline-block;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    min-width: 40px;
    text-align: center;
}

.person-info, .cargo-info {
    line-height: 1.4;
}

.person-name, .cargo-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.person-email, .cargo-vinculo {
    font-size: 0.8rem;
    color: #6c757d;
}

.contact-info, .date-info {
    font-size: 0.85rem;
    color: #495057;
    display: flex;
    align-items: center;
}

/* Badges de Status */
.status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
    min-width: 80px;
}

.status-badge.status-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.status-badge.status-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    color: white;
}

.status-badge.status-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
}

.status-badge.status-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

/* Badges de Nível */
.level-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
    min-width: 100px;
}

.level-badge.level-avancado, .level-badge.level-apto, .level-badge.level-excelente {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.level-badge.level-intermediario, .level-badge.level-parcialmente-apto, .level-badge.level-adequado {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.level-badge.level-basico, .level-badge.level-intermediário {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
}

.level-badge.level-nao-capacitado, .level-badge.level-nao-apto, .level-badge.level-baixo, .level-badge.level-none {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    color: white;
}

/* Badges de Portal */
.portal-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
    min-width: 80px;
}

.portal-badge.portal-proprio {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.portal-badge.portal-implanta {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.portal-badge.portal-empresa-terceirizada {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
}

.portal-badge.portal-none {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

/* Links */
.links-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.link-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.link-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

/* Links Individuais */
.link-btn-solo {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 24px !important;
    height: 24px !important;
    border-radius: 0.4rem !important;
    text-decoration: none !important;
    transition: all 0.15s ease !important;
    font-size: 12px !important;
    line-height: 1 !important;
    border: none !important;
    cursor: pointer !important;
}

.link-btn-solo i {
    font-size: 12px !important;
    line-height: 1 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.link-btn-solo:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2) !important;
    text-decoration: none !important;
}

.link-empty {
    color: #6c757d !important;
    font-size: 0.8rem !important;
    text-align: center !important;
}

.link-portal-cell, .link-dados-cell, .link-sic-cell, .link-ouv-cell {
    text-align: center !important;
    width: 40px !important;
    padding: 4px !important;
    vertical-align: middle !important;
}

.link-btn.link-portal, .link-btn-solo.link-portal {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    color: white !important;
}

.link-btn.link-portal:hover, .link-btn-solo.link-portal:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
    color: white !important;
}

.link-btn.link-sic, .link-btn-solo.link-sic {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white !important;
}

.link-btn.link-sic:hover, .link-btn-solo.link-sic:hover {
    background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%) !important;
    color: white !important;
}

.link-btn.link-ouv, .link-btn-solo.link-ouv {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
    color: #212529 !important;
}

.link-btn.link-ouv:hover, .link-btn-solo.link-ouv:hover {
    background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%) !important;
    color: white !important;
}

.link-btn.link-dados, .link-btn-solo.link-dados {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
    color: white !important;
}

.link-btn.link-dados:hover, .link-btn-solo.link-dados:hover {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%) !important;
    color: white !important;
}

.link-btn.link-politica {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    color: white;
}

/* Botões de Ação */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 0.5rem;
    background: transparent;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 0.9rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.action-btn.action-view {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.action-btn.action-edit {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.action-btn.action-delete {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.action-btn.action-delete:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Responsividade */
@media (max-width: 768px) {
    .modern-nav-pills .nav-link {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .tab-icon {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .tab-count {
        margin-left: 0;
        margin-top: 0.5rem;
    }
    
    .table-header {
        padding: 1rem;
    }
    
    .table-header .row {
        flex-direction: column;
        text-align: center;
    }
    
    .table-tools {
        margin-top: 1rem;
    }
    
    .modern-table {
        font-size: 0.8rem;
    }
    
    .modern-table .modern-thead th {
        padding: 0.75rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .modern-table .modern-row td {
        padding: 0.75rem 0.5rem;
    }
    
    .person-name, .cargo-title {
        font-size: 0.85rem;
    }
    
    .person-email, .cargo-vinculo {
        font-size: 0.75rem;
    }
    
    .status-badge, .level-badge, .portal-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        min-width: 60px;
    }
    
    .links-group {
        justify-content: center;
    }
    
    .link-btn, .action-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
}

/* CSS FINAL PARA CORRIGIR TÍTULOS DA TABELA LGPD */
#lgpdTableModern th {
    background-color: #343a40 !important;
    color: white !important;
    font-weight: bold !important;
    text-align: center !important;
    padding: 12px 8px !important;
    border: 1px solid #495057 !important;
}

/* Garantir que DataTables não interfira no layout dos títulos */
.dataTables_scrollHead {
    overflow: visible !important;
}

.dataTables_scrollHeadInner {
    box-sizing: content-box !important;
    width: 100% !important;
}

.dataTables_scrollHeadInner table {
    width: 100% !important;
    table-layout: fixed !important;
}

/* Forçar alinhamento horizontal para ambas as tabelas */
#laiTableModern thead tr,
#lgpdTableModern thead tr {
    display: table-row !important;
}

#laiTableModern th,
#lgpdTableModern th {
    display: table-cell !important;
    white-space: nowrap !important;
}
</style>

<script>
$(document).ready(function() {
    console.log('=== INICIANDO TABELAS DATATABLES ===');
    
    // Dados dos estados para o modal
    window.dadosEstados = <?= json_encode($dadosLAI, JSON_UNESCAPED_UNICODE) ?>;
    
        const baseConfig = {
        language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' },
        pageLength: 10,
        lengthChange: false,
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: [1, 2], className: 'text-left' },
            { targets: -1, orderable: false }
        ]
    };

    // Configuração específica para LAI com larguras fixas
    const laiConfig = {
        ...baseConfig,
        columnDefs: [
            ...baseConfig.columnDefs,
            { targets: 0, width: '50px' },   // Estado
            { targets: 1, width: '150px' },  // Autoridade
            { targets: 2, width: '120px' },  // Cargo
            { targets: 3, width: '80px' },   // Portaria
            { targets: 4, width: '90px' },   // Capacitação
            { targets: 5, width: '90px' },   // Autopercepção
            { targets: 6, width: '80px' },   // Tipo Portal
            { targets: 7, width: '80px' },   // Ano
            { targets: 8, width: '90px' },   // Sistema
            { targets: 9, width: '90px' },   // SisAto
            { targets: 10, width: '70px' },  // Link Portal
            { targets: 11, width: '70px' },  // Link Dados
            { targets: 12, width: '70px' },  // E-SIC
            { targets: 13, width: '70px' },  // E-OUV
            { targets: 14, width: '80px' }   // Ações
        ]
    };

    // Configuração específica para LGPD com larguras fixas
    const lgpdConfig = {
        ...baseConfig,
        columnDefs: [
            ...baseConfig.columnDefs,
            { targets: 0, width: '50px' },   // Estado
            { targets: 1, width: '150px' },  // Encarregado
            { targets: 2, width: '120px' },  // Email
            { targets: 3, width: '100px' },  // Telefone
            { targets: 4, width: '90px' },   // Portaria
            { targets: 5, width: '90px' },   // Adequação
            { targets: 6, width: '100px' },  // Política
            { targets: 7, width: '90px' },   // Inventário
            { targets: 8, width: '90px' },   // Relatório
            { targets: 9, width: '100px' },  // Treinamento
            { targets: 10, width: '90px' },  // Canal
            { targets: 11, width: '80px' }   // Ações
        ]
    };

    // Verificar se existem tabelas antigas e inicializar apenas se existirem
    if ($('#laiTable').length > 0) {
        const laiTable = $('#laiTable').DataTable(laiConfig);
        setTimeout(() => laiTable.columns.adjust().draw(), 100);
    }
    
    if ($('#lgpdTable').length > 0) {
        const lgpdTable = $('#lgpdTable').DataTable(lgpdConfig);
        setTimeout(() => lgpdTable.columns.adjust().draw(), 100);
    }

    // Código de ajuste removido - agora feito individualmente acima

    /* corrige desalinhamento quando se troca de aba */
    $('a[data-toggle="pill"], a[data-toggle="tab"]').on('shown.bs.tab', () => {
        setTimeout(() => {
            $.fn.dataTable.tables({visible:true, api:true}).columns.adjust().draw();
        }, 50);
    });
    
    // Modal de detalhes
    $('#detalheModal').on('show.bs.modal', function (e) {
        const uf = $(e.relatedTarget).data('uf');
        const dados = window.dadosEstados.find(x => x.uf === uf);
        $('#detUF').text(uf);

        if (!dados) { 
            $('#detBody').html('<p class="text-muted">Nenhum dado encontrado para este estado.</p>'); 
            return; 
        }

        // Função para formatar link
        function formatarLink(url, texto) {
            if (!url) return '—';
            return `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">${texto} <i class="fas fa-external-link-alt ml-1"></i></a>`;
        }

        // Função para formatar data
        function formatarData(data) {
            if (!data) return '—';
            return new Date(data).toLocaleDateString('pt-BR');
        }

        const html = `
                        <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3"><i class="fas fa-file-alt mr-2"></i>Dados LAI</h6>
                    <table class="table table-sm table-striped">
                        <tbody>
                            <tr><th width="40%">UF</th><td><strong>${uf}</strong></td></tr>
                            <tr><th>Autoridade LAI</th><td>${dados.nome_autoridade_oficial_lai || '—'}</td></tr>
                            <tr><th>Email</th><td>${dados.email_autoridade_lai || '—'}</td></tr>
                            <tr><th>Telefone</th><td>${dados.telefone_autoridade_lai || '—'}</td></tr>
                            <tr><th>Cargo</th><td>${dados.cargo_oficial_lai || '—'}</td></tr>
                            <tr><th>Vínculo</th><td>${dados.vinculo_oficial_lai || '—'}</td></tr>
                            <tr><th>Portaria</th><td><span class="badge badge-${dados.portaria_oficial_lai ? 'success' : 'danger'}">${dados.portaria_oficial_lai ? 'Sim' : 'Não'}</span></td></tr>
                            <tr><th>Capacitação</th><td><span class="badge badge-primary">${dados.curso_capacitacao_lai || 'Não informado'}</span></td></tr>
                            <tr><th>Auto-percepção</th><td><span class="badge badge-info">${dados.nivel_autopercepcao_lai || 'Não informado'}</span></td></tr>
                            <tr><th>Tipo Portal</th><td><span class="badge badge-secondary">${dados.tipo_portal_lai || 'Não informado'}</span></td></tr>
                            <tr><th>Ano Atualização</th><td>${dados.ano_atualizacao_dados_lai || '—'}</td></tr>
                            <tr><th>Sistema Próprio</th><td><span class="badge badge-${dados.sistema_atos_normativos_proprio === 'sim' ? 'success' : 'danger'}">${dados.sistema_atos_normativos_proprio === 'sim' ? 'Sim' : 'Não'}</span></td></tr>
                            <tr><th>Interesse SISATO</th><td><span class="badge badge-${dados.interesse_sisato_cfo === 'sim' ? 'success' : 'secondary'}">${dados.interesse_sisato_cfo === 'sim' ? 'Sim' : 'Não'}</span></td></tr>
                            <tr><th>Satisfação TCU</th><td><span class="badge badge-warning">${dados.satisfacao_dados_abertos_tcu || 'Não informado'}</span></td></tr>
                            <tr><th>Portal</th><td>${formatarLink(dados.link_portal_transparencia, 'Acessar')}</td></tr>
                            <tr><th>E-SIC</th><td>${formatarLink(dados.link_e_sic, 'Acessar')}</td></tr>
                            <tr><th>E-OUVE</th><td>${formatarLink(dados.link_e_ouve, 'Acessar')}</td></tr>
                            <tr><th>Dados Abertos</th><td>${formatarLink(dados.link_dados_abertos, 'Acessar')}</td></tr>
                        </tbody>
                    </table>
                            </div>
                <div class="col-md-6">
                    <h6 class="text-primary mb-3"><i class="fas fa-shield-alt mr-2"></i>Dados LGPD</h6>
                    <table class="table table-sm table-striped">
                        <tbody>
                            <tr><th width="40%">Encarregado</th><td>${dados.nome_encarregado_lgpd || '—'}</td></tr>
                            <tr><th>Email</th><td>${dados.email_encarregado_lgpd || '—'}</td></tr>
                            <tr><th>Telefone</th><td>${dados.telefone_encarregado_lgpd || '—'}</td></tr>
                            <tr><th>Portaria</th><td><span class="badge badge-${dados.portaria_encarregado_lgpd ? 'success' : 'danger'}">${dados.portaria_encarregado_lgpd ? 'Sim' : 'Não'}</span></td></tr>
                            <tr><th>Adequação LGPD</th><td><span class="badge badge-primary">${dados.grau_adequacao_lgpd || 'Não informado'}</span></td></tr>
                            <tr><th>Política Privacidade</th><td>${formatarLink(dados.politica_privacidade_link, 'Acessar')}</td></tr>
                            <tr><th>Inventário Dados</th><td><span class="badge badge-info">${dados.inventario_dados_status || 'Não iniciado'}</span></td></tr>
                            <tr><th>Relatório Impacto</th><td><span class="badge badge-${dados.relatorio_impacto_prodados ? 'success' : 'danger'}">${dados.relatorio_impacto_prodados ? 'Possui' : 'Não possui'}</span></td></tr>
                            <tr><th>Último Treinamento</th><td>${formatarData(dados.ultimo_treinamento_lgpd)}</td></tr>
                            <tr><th>Canal Titular</th><td>${dados.titular_canal_solicitacao || '—'}</td></tr>
                        </tbody>
                    </table>
                            </div>
                            </div>
            
            ${dados.observacoes_lai || dados.observacoes_lgpd ? `
                <div class="row mt-3">
                            <div class="col-12">
                        <h6 class="text-primary mb-3"><i class="fas fa-comment mr-2"></i>Observações</h6>
                        ${dados.observacoes_lai ? `<div class="alert alert-info"><strong>LAI:</strong> ${dados.observacoes_lai}</div>` : ''}
                        ${dados.observacoes_lgpd ? `<div class="alert alert-warning"><strong>LGPD:</strong> ${dados.observacoes_lgpd}</div>` : ''}
                                </div>
                            </div>
                            ` : ''}
        `;
        
        $('#detBody').html(html);
    });
    
        console.log('=== TABELAS INICIALIZADAS COM SUCESSO ===');
    
    // ============ FUNÇÃO PARA ATUALIZAR ESTATÍSTICAS ============
    window.atualizarEstatisticas = function() {
        try {
            const totalEstados = window.dadosEstados.length;
            
            // Calcular estatísticas LAI
            const comPortariaLAI = window.dadosEstados.filter(e => e.portaria_oficial_lai && e.portaria_oficial_lai.trim() !== '').length;
            const capacitados = window.dadosEstados.filter(e => e.curso_capacitacao_lai === 'avancado' || e.curso_capacitacao_lai === 'intermediario').length;
            const comPortalProprio = window.dadosEstados.filter(e => e.tipo_portal_lai === 'proprio').length;
            const comDadosAbertos = window.dadosEstados.filter(e => e.link_dados_abertos && e.link_dados_abertos.trim() !== '').length;
            
            // Calcular estatísticas LGPD
            const comDPO = window.dadosEstados.filter(e => e.nome_encarregado_lgpd && e.nome_encarregado_lgpd.trim() !== '').length;
            const comPoliticaPrivacidade = window.dadosEstados.filter(e => e.politica_privacidade_link && e.politica_privacidade_link.trim() !== '').length;
            const inventarioConcluido = window.dadosEstados.filter(e => e.inventario_dados_status === 'concluído').length;
            const comRelatorioImpacto = window.dadosEstados.filter(e => e.relatorio_impacto_prodados == '1').length;
            
            // Atualizar cards LAI
            $('.card-stats').eq(0).find('h4').text(`${comPortariaLAI}/${totalEstados}`);
            $('.card-stats').eq(0).find('small').text(`${Math.round((comPortariaLAI / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(1).find('h4').text(`${capacitados}/${totalEstados}`);
            $('.card-stats').eq(1).find('small').text(`${Math.round((capacitados / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(2).find('h4').text(`${comPortalProprio}/${totalEstados}`);
            $('.card-stats').eq(2).find('small').text(`${Math.round((comPortalProprio / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(3).find('h4').text(`${comDadosAbertos}/${totalEstados}`);
            $('.card-stats').eq(3).find('small').text(`${Math.round((comDadosAbertos / totalEstados) * 100)}%`);
            
            // Atualizar cards LGPD
            $('.card-stats').eq(4).find('h4').text(`${comDPO}/${totalEstados}`);
            $('.card-stats').eq(4).find('small').text(`${Math.round((comDPO / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(5).find('h4').text(`${comPoliticaPrivacidade}/${totalEstados}`);
            $('.card-stats').eq(5).find('small').text(`${Math.round((comPoliticaPrivacidade / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(6).find('h4').text(`${inventarioConcluido}/${totalEstados}`);
            $('.card-stats').eq(6).find('small').text(`${Math.round((inventarioConcluido / totalEstados) * 100)}%`);
            
            $('.card-stats').eq(7).find('h4').text(`${comRelatorioImpacto}/${totalEstados}`);
            $('.card-stats').eq(7).find('small').text(`${Math.round((comRelatorioImpacto / totalEstados) * 100)}%`);
            
            console.log('Estatísticas atualizadas:', {
                total: totalEstados,
                portariaLAI: comPortariaLAI,
                capacitados: capacitados,
                portalProprio: comPortalProprio,
                dadosAbertos: comDadosAbertos,
                dpo: comDPO,
                politicaPrivacidade: comPoliticaPrivacidade,
                inventario: inventarioConcluido,
                ripd: comRelatorioImpacto
            });
            
        } catch (error) {
            console.error('Erro ao atualizar estatísticas:', error);
        }
    };
    
    // ============ FUNÇÃO DELETAR ESTADO ============
    window.deletarEstado = async function(uf) {
        if (!uf) {
            alert('⚠️ UF não informada para deletar');
            return;
        }
        
        // Confirmar deleção
        const confirmacao = confirm(
            `🗑️ DELETAR ESTADO ${uf}\n\n` +
            `Tem certeza que deseja deletar TODOS os dados do estado ${uf}?\n\n` +
            `⚠️ Esta ação NÃO pode ser desfeita!\n\n` +
            `Todos os dados LAI e LGPD do estado serão perdidos permanentemente.`
        );
        
        if (!confirmacao) {
            console.log('Deleção cancelada pelo usuário');
            return;
        }
        
        // Segunda confirmação para ações críticas
        const confirmacaoFinal = confirm(
            `🚨 CONFIRMAÇÃO FINAL\n\n` +
            `Digite "DELETAR" se realmente deseja prosseguir:\n\n` +
            `Estado: ${uf}\n` +
            `Ação: DELETAR PERMANENTEMENTE`
        );
        
        if (!confirmacaoFinal) {
            console.log('Deleção cancelada na confirmação final');
            return;
        }
        
        try {
            console.log(`🗑️ Iniciando deleção do estado: ${uf}`);
            
            // Mostrar loading
            if (typeof window.showLoading === 'function') {
                window.showLoading('Deletando estado...');
            }
            
            const response = await fetch('/ajax_deletar_registro', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ uf: uf })
            });
            
            console.log('Status da resposta:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Resposta não é JSON:', text);
                throw new Error('Resposta inválida do servidor');
            }
            
            const data = await response.json();
            console.log('Resposta da deleção:', data);
            
            if (data.success) {
                // Sucesso - remover linha das tabelas
                console.log(`✅ Estado ${uf} deletado com sucesso`);
                
                // Remover da tabela LAI (DataTable moderna)
                if ($.fn.DataTable.isDataTable('#laiTableModern')) {
                    const $laiTable = $('#laiTableModern').DataTable();
                    $laiTable.rows().every(function(index) {
                        const rowData = this.data();
                        if (rowData && rowData[0] && rowData[0].includes(uf)) {
                            this.remove();
                        }
                    });
                    $laiTable.draw();
                    console.log('Linha removida da tabela LAI');
                }
                
                // Remover da tabela LGPD (DataTable moderna)
                if ($.fn.DataTable.isDataTable('#lgpdTableModern')) {
                    const $lgpdTable = $('#lgpdTableModern').DataTable();
                    $lgpdTable.rows().every(function(index) {
                        const rowData = this.data();
                        if (rowData && rowData[0] && rowData[0].includes(uf)) {
                            this.remove();
                        }
                    });
                    $lgpdTable.draw();
                    console.log('Linha removida da tabela LGPD');
                }
                
                // Remover das tabelas simples
                $(`#laiTable tbody tr, #lgpdTable tbody tr`).each(function() {
                    const primeiraCell = $(this).find('td:first').text().trim();
                    if (primeiraCell === uf) {
                        $(this).remove();
                        console.log(`Linha ${uf} removida da tabela simples`);
                    }
                });
                
                // Atualizar estatísticas
                if (typeof window.atualizarEstatisticas === 'function') {
                    window.atualizarEstatisticas();
                }
                
                // Mostrar sucesso
                alert(`✅ Estado ${uf} deletado com sucesso!\n\nTodos os dados foram removidos permanentemente.`);
                
            } else {
                console.error('Erro na deleção:', data.message);
                alert(`❌ Erro ao deletar estado ${uf}:\n\n${data.message}`);
            }
            
        } catch (error) {
            console.error('Erro na deleção:', error);
            alert(`❌ Erro ao deletar estado ${uf}:\n\n${error.message}`);
        } finally {
            // Esconder loading
            if (typeof window.hideLoading === 'function') {
                window.hideLoading();
            }
        }
    };
    
    // ============ EVENTOS ADICIONAIS ============
    
    // Garantir que UF seja sempre maiúscula
    $(document).on('input', '#inputUF', function() {
        const value = $(this).val().toUpperCase();
        $(this).val(value);
        
        // Validar se tem exatamente 2 letras
        if (value.length === 2 && /^[A-Z]{2}$/.test(value)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else if (value.length > 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });
    

    

    

    

    
    // ============ MODAL EDITAR ============
    
    /* ---------- abrir modal EDITAR/INSERIR ---------- */
    $('#editarModal').on('show.bs.modal', function (e) {
        const $trigger = $(e.relatedTarget);
        const action = $trigger.data('action');
        const uf = $trigger.data('uf');
        
        // Limpar formulário
        $('#formEditar')[0].reset();
        $('input[name="setores_alimentacao[]"]').prop('checked', false);
        $('#editMsg').text('').removeClass('text-success text-danger text-info');
        
                 // Armazenar o modo no formulário para fácil acesso
         $('#formEditar').data('action', action || 'editar');
         
         if (action === 'inserir') {
             // Modo inserção
             $('.modal-title').html('<i class="fas fa-plus mr-2"></i>Inserir Novo Estado');
             $('#editUF').text('NOVO');
             $('#inputUF').val('').prop('readonly', false).prop('required', true);
             $('#inputUF').closest('.row').show();
             
             // Marcar explicitamente como modo inserção
             $('#formEditar').addClass('modo-inserir').removeClass('modo-editar');
             
             console.log('🎯 MODO INSERÇÃO CONFIGURADO');
             // Deixar todos os campos em branco
         } else {
             // Modo edição
             $('.modal-title').html('<i class="fas fa-edit mr-2"></i>Editar Dados do Estado <span id="editUF" class="badge badge-light text-primary"></span>');
             const dados = window.dadosEstados.find(x => x.uf === uf);
             $('#editUF').text(uf);
             $('#inputUF').val(uf).prop('readonly', true).prop('required', false);
             $('#inputUF').closest('.row').hide();
             
             // Marcar explicitamente como modo edição
             $('#formEditar').addClass('modo-editar').removeClass('modo-inserir');
             
             console.log('🎯 MODO EDIÇÃO CONFIGURADO para:', uf);

            if (dados) {
                // ========== GRUPO 1: AUTORIDADE DE MONITORAMENTO LAI ==========
                $('#inputAutoridade').val(dados.nome_autoridade_oficial_lai || '');
                $('#inputEmailAutoridade').val(dados.email_autoridade_lai || '');
                $('#inputTelefoneAutoridade').val(dados.telefone_autoridade_lai || '');
                $('#inputCargo').val(dados.cargo_oficial_lai || '');
                $('#inputVinculo').val(dados.vinculo_oficial_lai || '');
                $('#inputPortaria').val(dados.portaria_oficial_lai || '');
                $('#inputCapacitacao').val(dados.curso_capacitacao_lai || '');
                $('#inputAutopercepcao').val(dados.nivel_autopercepcao_lai || '');
                $('#inputObsLAI').val(dados.observacoes_lai || '');
                
                // ========== GRUPO 2: ESTRUTURA DE PORTAL / TRANSPARÊNCIA LAI ==========
                $('#inputTipoPortal').val(dados.tipo_portal_lai || '');
                $('#inputAnoAtualizacao').val(dados.ano_atualizacao_dados_lai || '');
                $('#inputLinkPortal').val(dados.link_portal_transparencia || '');
                $('#inputLinkESIC').val(dados.link_e_sic || '');
                $('#inputLinkEOUV').val(dados.link_e_ouve || '');
                $('#inputSistemaProprio').val(dados.sistema_atos_normativos_proprio || '');
                $('#inputInteresseSisato').val(dados.interesse_sisato_cfo || '');
                $('#inputSatisfacaoTCU').val(dados.satisfacao_dados_abertos_tcu || '');
                $('#inputLinkDados').val(dados.link_dados_abertos || '');
                
                // Setores de alimentação (checkboxes)
                $('input[name="setores_alimentacao[]"]').prop('checked', false);
                if (dados.setores_alimentacao_transparencia) {
                    const setores = dados.setores_alimentacao_transparencia.split(',');
                    setores.forEach(setor => {
                        $(`input[name="setores_alimentacao[]"][value="${setor.trim()}"]`).prop('checked', true);
                    });
                }
                
                // ========== GRUPO 3: INDICADORES DE ATENDIMENTO LAI ==========
                $('#inputTransparenciaAtiva').val(dados.indice_transparencia_ativa || '');
                $('#inputTransparenciaPassiva').val(dados.indice_transparencia_passiva || '');
                $('#inputTempoResposta').val(dados.tempo_medio_resposta_pedido || '');
                
                // ========== GRUPO 4: ENCARREGADO DE DADOS LGPD ==========
                $('#inputEncarregado').val(dados.nome_encarregado_lgpd || '');
                $('#inputEmailEncarregado').val(dados.email_encarregado_lgpd || '');
                $('#inputTelefoneEncarregado').val(dados.telefone_encarregado_lgpd || '');
                $('#inputPortariaLGPD').val(dados.portaria_encarregado_lgpd || '');
                
                // ========== GRUPO 5: GOVERNANÇA DE DADOS LGPD ==========
                $('#inputGrau').val(dados.grau_adequacao_lgpd || '');
                $('#inputInventario').val(dados.inventario_dados_status || '');
                $('#inputPoliticaPrivacidade').val(dados.politica_privacidade_link || '');
                $('#inputRelatorioImpacto').val(dados.relatorio_impacto_prodados || '');
                $('#inputUltimoTreinamento').val(dados.ultimo_treinamento_lgpd || '');
                $('#inputCanalTitular').val(dados.titular_canal_solicitacao || '');
                $('#inputObsLGPD').val(dados.observacoes_lgpd || '');
            }
        }
    });

    /* ---------- submit do formulário ---------- */
    $('#formEditar').on('submit', function (evt) {
        evt.preventDefault();

        const $form = $(this);
        const formData = new FormData(this);
        
        // Detectar se é inserção ou edição usando múltiplos métodos
        const formAction = $('#formEditar').data('action');
        const ufReadonly = $('#inputUF').prop('readonly');
        const ufRequired = $('#inputUF').prop('required');
        const temClasseInserir = $('#formEditar').hasClass('modo-inserir');
        const tituloContemInserir = $('.modal-title').text().includes('Inserir');
        
        // Múltiplas verificações para maior confiabilidade
        const isInsert = formAction === 'inserir' || 
                         !ufReadonly || 
                         ufRequired || 
                         temClasseInserir || 
                         tituloContemInserir;
        
        const uf = $('#inputUF').val().trim().toUpperCase();
        
        // Debug detalhado da detecção de modo
        console.log('🔍 DETECÇÃO DE MODO DETALHADA:');
        console.log('- Form action data:', formAction);
        console.log('- Campo UF readonly:', ufReadonly);
        console.log('- Campo UF required:', ufRequired);
        console.log('- Tem classe modo-inserir:', temClasseInserir);
        console.log('- Título contém "Inserir":', tituloContemInserir);
        console.log('- Campo UF valor:', uf);
        console.log('- Modal title completo:', $('.modal-title').text());
        console.log('- Campo UF visível:', $('#inputUF').closest('.row').is(':visible'));
        console.log('🎯 RESULTADO FINAL - IsInsert:', isInsert);
        
        if (isInsert && !uf) {
            $('#editMsg').text('❌ UF é obrigatória para novo estado').removeClass('text-info text-success').addClass('text-danger');
            return;
        }
        
        // Processar dados do formulário
        const dadosProcessados = {};
        
        // Campos normais
        for (let [key, value] of formData.entries()) {
            if (key !== 'setores_alimentacao[]') {
                dadosProcessados[key] = value;
            }
        }
        
        // Processar checkboxes dos setores
        const setoresSelecionados = formData.getAll('setores_alimentacao[]');
        dadosProcessados.setores_alimentacao_transparencia = setoresSelecionados.join(',');
        
        // Garantir que UF está maiúscula
        dadosProcessados.uf = uf;
        
        const action = isInsert ? 'inserindo' : 'salvando';
        $('#editMsg').text(`${action.charAt(0).toUpperCase() + action.slice(1)}...`).removeClass('text-success text-danger').addClass('text-info');

        // Debug: mostrar dados que serão enviados
        console.log('Dados a serem enviados:', dadosProcessados);
        console.log('Modo:', isInsert ? 'Inserção' : 'Edição');

        // Escolher endpoint baseado na ação
        const endpoint = isInsert ? '/ajax_inserir_registro' : '/ajax_salvar_registro';
        
        // Log final antes do envio
        console.log('🚀 ENVIANDO PARA:', endpoint);
        console.log('📦 DADOS FINAIS:', dadosProcessados);
        
        // Manter dados como estão (portarias agora são strings)
        const dadosParaArray = { ...dadosProcessados };
        fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dadosProcessados)
        })
        .then(response => {
            console.log('Status da resposta:', response.status);
            console.log('Headers da resposta:', response.headers);
            console.log('Content-Type:', response.headers.get('content-type'));
            
            // Sempre obter o texto primeiro
            return response.text().then(text => {
                console.log('Texto da resposta completo:', text);
                
                // Verificar se a resposta está vazia
                if (!text || text.trim() === '') {
                    throw new Error('Resposta vazia do servidor');
                }
                
                // Verificar se é um erro HTTP
                if (!response.ok) {
                    console.error('Resposta de erro HTTP:', text);
                    throw new Error(`Erro ${response.status}: ${text.substring(0, 200)}`);
                }
                
                // Tentar fazer parse do JSON
                try {
                    const jsonData = JSON.parse(text);
                    console.log('JSON parseado com sucesso:', jsonData);
                    return jsonData;
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    console.error('Texto que causou erro:', text.substring(0, 500));
                    
                    // Se contém HTML, provavelmente é uma página de erro
                    if (text.includes('<html') || text.includes('<!DOCTYPE')) {
                        throw new Error('Servidor retornou uma página HTML em vez de JSON. Verifique se o endpoint está correto.');
                    }
                    
                    throw new Error(`Resposta inválida do servidor. Esperado JSON, recebido: ${text.substring(0, 100)}...`);
                }
            });
        })
        .then(resp => {
            console.log('Resposta recebida:', resp);
            if (!resp.success) throw resp.message || 'Erro desconhecido';

            if (isInsert) {
                // MODO INSERÇÃO - Adicionar novo estado
                
                /* --- adicionar ao array dadosEstados --- */
                window.dadosEstados.push(dadosParaArray);
                
                /* --- adicionar às tabelas DataTable --- */
                try {
                    // Verificar se as tabelas existem e estão inicializadas
                    if ($.fn.DataTable.isDataTable('#laiTableModern')) {
                        const $laiTable = $('#laiTableModern').DataTable();
                        
                        // Criar nova linha para LAI
                        const novaLinhaLAI = [
                            `<span class="uf-badge">${dadosProcessados.uf}</span>`,
                            `<div class="person-info">
                                <div class="person-name">${dadosProcessados.nome_autoridade_oficial_lai || 'Não informado'}</div>
                                ${dadosProcessados.email_autoridade_lai ? '<div class="person-email">' + dadosProcessados.email_autoridade_lai + '</div>' : ''}
                            </div>`,
                            `<div class="cargo-info">
                                ${dadosProcessados.cargo_oficial_lai ? '<div class="cargo-title">' + dadosProcessados.cargo_oficial_lai + '</div>' : ''}
                                ${dadosProcessados.vinculo_oficial_lai ? '<div class="cargo-vinculo">' + dadosProcessados.vinculo_oficial_lai + '</div>' : ''}
                            </div>`,
                            dadosProcessados.portaria_oficial_lai ? 
                                `<span class="status-badge status-success">${dadosProcessados.portaria_oficial_lai}</span>` : 
                                '<span class="status-badge status-danger">Não possui</span>',
                            `<span class="level-badge level-${(dadosProcessados.curso_capacitacao_lai || 'none').toLowerCase().replace(/\s+/g, '-')}">${dadosProcessados.curso_capacitacao_lai || 'Não informado'}</span>`,
                            `<span class="level-badge level-${(dadosProcessados.nivel_autopercepcao_lai || 'none').toLowerCase().replace(/\s+/g, '-')}">${dadosProcessados.nivel_autopercepcao_lai || 'Não informado'}</span>`,
                            `<span class="portal-badge portal-${(dadosProcessados.tipo_portal_lai || 'none').toLowerCase().replace(/\s+/g, '-')}">${dadosProcessados.tipo_portal_lai || 'Não informado'}</span>`,
                            dadosProcessados.ano_atualizacao_dados_lai || '—',
                            dadosProcessados.sistema_atos_normativos_proprio === 'sim' ? 
                                '<span class="status-badge status-success">Próprio</span>' : 
                                '<span class="status-badge status-secondary">Não possui</span>',
                            dadosProcessados.link_portal_transparencia ? 
                                `<a href="${dadosProcessados.link_portal_transparencia}" target="_blank" class="link-btn-solo link-portal"><i class="fas fa-globe"></i></a>` : 
                                '<span class="link-empty">—</span>',
                            dadosProcessados.link_dados_abertos ? 
                                `<a href="${dadosProcessados.link_dados_abertos}" target="_blank" class="link-btn-solo link-dados"><i class="fas fa-database"></i></a>` : 
                                '<span class="link-empty">—</span>',
                            dadosProcessados.link_e_sic ? 
                                `<a href="${dadosProcessados.link_e_sic}" target="_blank" class="link-btn-solo link-sic"><i class="fas fa-info-circle"></i></a>` : 
                                '<span class="link-empty">—</span>',
                            dadosProcessados.link_e_ouve ? 
                                `<a href="${dadosProcessados.link_e_ouve}" target="_blank" class="link-btn-solo link-ouv"><i class="fas fa-headset"></i></a>` : 
                                '<span class="link-empty">—</span>',
                            `<div class="action-buttons">
                                <button class="action-btn action-view" data-toggle="modal" data-target="#detalheModal" data-uf="${dadosProcessados.uf}" title="Ver detalhes"><i class="fas fa-eye"></i></button>
                                <button class="action-btn action-edit" data-toggle="modal" data-target="#editarModal" data-uf="${dadosProcessados.uf}" title="Editar dados"><i class="fas fa-edit"></i></button>
                                <button class="action-btn action-delete" data-uf="${dadosProcessados.uf}" title="Deletar estado" onclick="deletarEstado('${dadosProcessados.uf}')"><i class="fas fa-trash"></i></button>
                            </div>`
                        ];
                        
                        // Adicionar linha e redesenhar
                        $laiTable.row.add(novaLinhaLAI).draw(false);
                        console.log('Nova linha LAI adicionada com sucesso');
                    }
                    
                    // Tabela LGPD
                    if ($.fn.DataTable.isDataTable('#lgpdTableModern')) {
                        const $lgpdTable = $('#lgpdTableModern').DataTable();
                        
                        const novaLinhaLGPD = [
                            `<span class="uf-badge">${dadosProcessados.uf}</span>`,
                            `<div class="person-info">
                                <div class="person-name">${dadosProcessados.nome_encarregado_lgpd || 'Não nomeado'}</div>
                                ${dadosProcessados.email_encarregado_lgpd ? '<div class="person-email">' + dadosProcessados.email_encarregado_lgpd + '</div>' : ''}
                            </div>`,
                            dadosProcessados.telefone_encarregado_lgpd ? 
                                `<div class="contact-info"><i class="fas fa-phone mr-1"></i>${dadosProcessados.telefone_encarregado_lgpd}</div>` : '—',
                            dadosProcessados.portaria_encarregado_lgpd ? 
                                `<span class="status-badge status-success">${dadosProcessados.portaria_encarregado_lgpd}</span>` : 
                                '<span class="status-badge status-danger">Não possui</span>',
                            `<span class="level-badge level-${(dadosProcessados.grau_adequacao_lgpd || 'baixo').toLowerCase()}">${dadosProcessados.grau_adequacao_lgpd || 'Baixo'}</span>`,
                            `<span class="status-badge status-${dadosProcessados.inventario_dados_status === 'concluído' ? 'success' : (dadosProcessados.inventario_dados_status === 'em andamento' ? 'warning' : 'danger')}">${dadosProcessados.inventario_dados_status || 'Não iniciado'}</span>`,
                            dadosProcessados.relatorio_impacto_prodados ? 
                                '<span class="status-badge status-success">Possui</span>' : 
                                '<span class="status-badge status-danger">Não possui</span>',
                            dadosProcessados.ultimo_treinamento_lgpd ? 
                                `<div class="date-info"><i class="fas fa-calendar mr-1"></i>${new Date(dadosProcessados.ultimo_treinamento_lgpd).toLocaleDateString('pt-BR')}</div>` : 
                                '<span class="text-muted">—</span>',
                            dadosProcessados.politica_privacidade_link ? 
                                `<a href="${dadosProcessados.politica_privacidade_link}" target="_blank" class="link-btn link-politica"><i class="fas fa-shield-alt"></i></a>` : 
                                '<span class="text-muted">—</span>',
                            `<div class="action-buttons">
                                <button class="action-btn action-view" data-toggle="modal" data-target="#detalheModal" data-uf="${dadosProcessados.uf}" title="Ver detalhes"><i class="fas fa-eye"></i></button>
                                <button class="action-btn action-edit" data-toggle="modal" data-target="#editarModal" data-uf="${dadosProcessados.uf}" title="Editar dados"><i class="fas fa-edit"></i></button>
                                <button class="action-btn action-delete" data-uf="${dadosProcessados.uf}" title="Deletar estado" onclick="deletarEstado('${dadosProcessados.uf}')"><i class="fas fa-trash"></i></button>
                            </div>`
                        ];
                        
                        // Adicionar linha e redesenhar
                        $lgpdTable.row.add(novaLinhaLGPD).draw(false);
                        console.log('Nova linha LGPD adicionada com sucesso');
                    }
                    
                    // Também adicionar nas tabelas simples se existirem
                    if ($('#laiTable').length > 0) {
                        const $laiSimples = $('#laiTable tbody');
                        const cargoVinculo = ((dadosProcessados.cargo_oficial_lai || '') + ' / ' + (dadosProcessados.vinculo_oficial_lai || '')).replace(/^\s*\/\s*|\s*\/\s*$/g, '') || '—';
                        const portariaBadge = dadosProcessados.portaria_oficial_lai ? 
                            `<span class="badge badge-success" title="${dadosProcessados.portaria_oficial_lai}">${dadosProcessados.portaria_oficial_lai}</span>` : 
                            '<span class="badge badge-danger">Não</span>';
                        
                        const novaLinhaSimples = `
                            <tr>
                                <td><strong>${dadosProcessados.uf}</strong></td>
                                <td>${dadosProcessados.nome_autoridade_oficial_lai || '—'}</td>
                                <td>${cargoVinculo}</td>
                                <td>${portariaBadge}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-2" data-toggle="modal" data-target="#editarModal" data-uf="${dadosProcessados.uf}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deletarEstado('${dadosProcessados.uf}')" title="Deletar estado">
                                        <i class="fas fa-trash"></i> Deletar
                                    </button>
                                </td>
                            </tr>
                        `;
                        $laiSimples.append(novaLinhaSimples);
                    }
                    
                    if ($('#lgpdTable').length > 0) {
                        const $lgpdSimples = $('#lgpdTable tbody');
                        const portariaLGPDBadge = dadosProcessados.portaria_encarregado_lgpd ? 
                            `<span class="badge badge-success" title="${dadosProcessados.portaria_encarregado_lgpd}">${dadosProcessados.portaria_encarregado_lgpd}</span>` : 
                            '<span class="badge badge-danger">Não</span>';
                        const grauBadge = dadosProcessados.grau_adequacao_lgpd ? 
                            `<span class="badge badge-primary">${dadosProcessados.grau_adequacao_lgpd}</span>` : 
                            '<span class="badge badge-secondary">Não informado</span>';
                        
                        const novaLinhaLGPDSimples = `
                            <tr>
                                <td><strong>${dadosProcessados.uf}</strong></td>
                                <td>${dadosProcessados.nome_encarregado_lgpd || '—'}</td>
                                <td>${dadosProcessados.email_encarregado_lgpd || '—'}</td>
                                <td>${dadosProcessados.telefone_encarregado_lgpd || '—'}</td>
                                <td>${portariaLGPDBadge}</td>
                                <td>${grauBadge}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-2" data-toggle="modal" data-target="#editarModal" data-uf="${dadosProcessados.uf}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deletarEstado('${dadosProcessados.uf}')" title="Deletar estado">
                                        <i class="fas fa-trash"></i> Deletar
                                    </button>
                                </td>
                            </tr>
                        `;
                        $lgpdSimples.append(novaLinhaLGPDSimples);
                    }
                    
                } catch (error) {
                    console.error('Erro ao adicionar linha nas tabelas:', error);
                }
                
                // Reativar eventos nos novos botões adicionados dinamicamente
                setTimeout(() => {
                    // Garantir que novos botões de ação funcionem
                    $(document).off('click', '.action-btn.action-view').on('click', '.action-btn.action-view', function(e) {
                        e.preventDefault();
                        const uf = $(this).data('uf');
                        $('#detalheModal').modal('show');
                        $('#detalheModal').trigger('show.bs.modal', [{ relatedTarget: this }]);
                    });
                    
                    $(document).off('click', '.action-btn.action-edit').on('click', '.action-btn.action-edit', function(e) {
                        e.preventDefault();
                        const uf = $(this).data('uf');
                        $('#editarModal').modal('show');
                        $('#editarModal').trigger('show.bs.modal', [{ relatedTarget: this }]);
                    });
                }, 100);
                
                // Atualizar estatísticas dos cards
                atualizarEstatisticas();
                
                $('#editMsg').removeClass('text-info').addClass('text-success').text('✓ Estado criado e adicionado às tabelas com sucesso!');
                
                // Mostrar notificação de sucesso mais visível
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Estado ${dadosProcessados.uf} criado com sucesso!`, 'Sucesso');
                } else {
                    // Fallback: mostrar alert personalizado
                    const $alert = $(`
                        <div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                            <strong>✓ Sucesso!</strong> Estado ${dadosProcessados.uf} criado com sucesso!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `);
                    $('body').append($alert);
                    setTimeout(() => $alert.fadeOut(() => $alert.remove()), 5000);
                }
                
            } else {
                // MODO EDIÇÃO - Atualizar estado existente
                
                /* --- atualizar array dadosEstados --- */
                const idx = window.dadosEstados.findIndex(x => x.uf === dadosProcessados.uf);
                if (idx >= 0) {
                    window.dadosEstados[idx] = {...window.dadosEstados[idx], ...dadosParaArray};
                }

                /* --- atualizar linhas nas tabelas existentes (código original) --- */
                // ... manter lógica de atualização das tabelas antigas ...
                
                $('#editMsg').removeClass('text-info').addClass('text-success').text('✓ Dados salvos com sucesso!');
            }
            
            setTimeout(() => $('#editarModal').modal('hide'), 1500);
        })
        .catch(err => {
            console.error('Erro completo ao salvar:', err);
            console.error('Stack trace:', err.stack);
            
            let mensagemErro = 'Erro desconhecido';
            if (err.message) {
                mensagemErro = err.message;
            } else if (typeof err === 'string') {
                mensagemErro = err;
            }
            
            // Log detalhado do erro
            console.log('=== DEBUG DO ERRO ===');
            console.log('Tipo do erro:', typeof err);
            console.log('Erro completo:', err);
            console.log('Endpoint usado:', endpoint);
            console.log('Modo:', isInsert ? 'Inserção' : 'Edição');
            console.log('Dados enviados:', dadosProcessados);
            console.log('=====================');
            
            $('#editMsg').removeClass('text-info').addClass('text-danger').text('❌ Erro ao salvar: ' + mensagemErro);
            
            // Mostrar erro mais detalhado em desenvolvimento
            if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
                const $debugAlert = $(`
                    <div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 70px; right: 20px; z-index: 9999; max-width: 500px;">
                        <strong>❌ Erro de Debug:</strong><br>
                        <small><strong>Endpoint:</strong> ${endpoint}</small><br>
                        <small><strong>Modo:</strong> ${isInsert ? 'Inserção' : 'Edição'}</small><br>
                        <small><strong>Erro:</strong> ${mensagemErro}</small><br>
                        <small>Veja o console para mais detalhes</small>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `);
                $('body').append($debugAlert);
                setTimeout(() => $debugAlert.fadeOut(() => $debugAlert.remove()), 10000);
            }
        });
    });
    
    // ============ FUNCIONALIDADES DAS TABELAS MODERNAS ============
    
    // Destruir tabela LAI existente se já foi inicializada para evitar conflitos
    if ($.fn.DataTable.isDataTable('#laiTableModern')) {
        $('#laiTableModern').DataTable().destroy();
        console.log('🗑️ Tabela LAI anterior destruída');
    }
    
    // Inicializar DataTables nas novas tabelas
    const laiTableModern = $('#laiTableModern').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' },
        pageLength: 25,
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: [9, 10, 11, 12], orderable: false }, // Colunas de links não ordenáveis
            { targets: -1, orderable: false } // Coluna de ações não ordenável
        ],
        // Callback após inicialização para garantir layout correto
        initComplete: function() {
            this.api().columns.adjust().draw();
            console.log('✅ Tabela LAI inicializada corretamente');
        }
    });

    // Destruir tabela LGPD existente se já foi inicializada para evitar conflitos
    if ($.fn.DataTable.isDataTable('#lgpdTableModern')) {
        $('#lgpdTableModern').DataTable().destroy();
        console.log('🗑️ Tabela LGPD anterior destruída');
    }
    
    const lgpdTableModern = $('#lgpdTableModern').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' },
        pageLength: 25,
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: -1, orderable: false } // Apenas coluna de ações não ordenável
        ],
        // Callback após inicialização para garantir layout correto
        initComplete: function() {
            this.api().columns.adjust().draw();
            console.log('✅ Tabela LGPD inicializada corretamente');
        }
    });

    // Ajustar tabelas quando trocar de aba
    $('a[data-toggle="pill"]').on('shown.bs.tab', function() {
        setTimeout(() => {
            $.fn.dataTable.tables({visible: true, api: true}).columns.adjust().draw();
        }, 100);
    });
    
    // Debug dos dados e ícones de links
    console.log('=== DEBUG DOS LINKS ===');
    console.log('Dados dos estados:', window.dadosEstados);
    
    // Verificar se há links nos dados
    let totalLinksEncontrados = 0;
    window.dadosEstados.forEach((estado, index) => {
        const links = {
            portal: estado.link_portal_transparencia,
            dados: estado.link_dados_abertos,
            sic: estado.link_e_sic,
            ouv: estado.link_e_ouve
        };
        const temLinks = Object.values(links).some(link => link && link.trim() !== '');
        if (temLinks) {
            console.log(`${estado.uf}:`, links);
            totalLinksEncontrados++;
        }
    });
    console.log(`Total de estados com links: ${totalLinksEncontrados}`);
    
    setTimeout(() => {
        const linkButtons = $('.link-btn-solo');
        console.log('Botões de link encontrados:', linkButtons.length);
        
        // Verificar e aplicar fallback se necessário
        linkButtons.each(function(index) {
            const $btn = $(this);
            const $icon = $btn.find('i');
            const hasIcon = $icon.length > 0;
            
            console.log(`Botão ${index + 1}:`, {
                classes: $btn.attr('class'),
                hasIcon: hasIcon,
                iconClasses: $icon.attr('class'),
                visible: $btn.is(':visible'),
                text: $icon.text()
            });
            
            // Fallback: se o ícone não está aparecendo, adicionar texto
            if (hasIcon && $icon.text() === '') {
                const iconClass = $icon.attr('class');
                let fallbackText = '🔗';
                
                if (iconClass.includes('fa-globe')) fallbackText = '🌐';
                else if (iconClass.includes('fa-database')) fallbackText = '💾';
                else if (iconClass.includes('fa-info-circle')) fallbackText = 'ℹ️';
                else if (iconClass.includes('fa-headset')) fallbackText = '🎧';
                
                // Se o ícone não está renderizando, usar fallback
                setTimeout(() => {
                    if ($icon.css('font-family').indexOf('Font Awesome') === -1) {
                        console.log('Aplicando fallback para:', iconClass);
                        $icon.text(fallbackText).css({
                            'font-family': 'Arial, sans-serif',
                            'font-size': '14px'
                        });
                    }
                }, 500);
            }
        });
        
        // Verificar se FontAwesome está carregado
        if (typeof FontAwesome !== 'undefined' || $('.fas').length > 0) {
            console.log('FontAwesome está carregado');
        } else {
            console.log('ATENÇÃO: FontAwesome pode não estar carregado - aplicando fallbacks');
        }
    }, 2000);
});

// ============ FUNÇÕES DE EXPORTAÇÃO E IMPRESSÃO ============

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table);
    XLSX.writeFile(wb, filename + '.xlsx');
}

function printTable(tableId) {
    const table = document.getElementById(tableId);
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Relatório LAI/LGPD</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .uf-badge { background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; }
                    .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                    .status-success { background: #28a745; color: white; }
                    .status-danger { background: #dc3545; color: white; }
                    .level-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
                    .person-name { font-weight: bold; }
                    .person-email { font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <h2>Relatório LAI/LGPD - ${new Date().toLocaleDateString('pt-BR')}</h2>
                ${table.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>

<!-- Biblioteca para exportação Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<?php require_once INC_PATH . '/footer.php'; ?> 