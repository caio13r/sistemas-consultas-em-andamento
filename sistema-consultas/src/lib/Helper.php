<?php

// Classe de arrays auxiliares
namespace Cfo\SisConsultas\lib;

use Cfo\SisConsultas\database\Database3;

use PDO;
use PDOException;

class Helper
{

    public static $acessList = [
        0 => 'Administrador',
        1 => 'CFO',
        2 => 'CRO-AC',
        3 => 'CRO-AL',
        4 => 'CRO-AM',
        5 => 'CRO-AP',
        6 => 'CRO-BA',
        7 => 'CRO-CE',
        8 => 'CRO-DF',
        9 => 'CRO-ES',
        10 => 'CRO-GO',
        11 => 'CRO-MA',
        12 => 'CRO-MT',
        13 => 'CRO-MS',
        14 => 'CRO-MG',
        15 => 'CRO-PA',
        16 => 'CRO-PB',
        17 => 'CRO-PR',
        18 => 'CRO-PE',
        19 => 'CRO-PI',
        20 => 'CRO-RJ',
        21 => 'CRO-RN',
        22 => 'CRO-RO',
        23 => 'CRO-RS',
        24 => 'CRO-RR',
        25 => 'CRO-SC',
        26 => 'CRO-SE',
        27 => 'CRO-SP',
        28 => 'CRO-TO'
    ];

    public static $subAcessList = [
        1 => 'Gestor',
        2 => 'Financeiro',
        3 => 'Cadastro',
        4 => 'Fiscalização',
        5 => 'TI',
        6 => 'LAI',
        7 => 'Supex',
        8 => 'Fiscalização - Coordenação',
        9 => 'Central de atendimento - CFO',
        10 => 'Colaborador CFO',
        11 => 'Cadastro_e_Consulta_Receita'
    ];

    public static $ufList = [
        'ALL' => 'Todos',
        'CFO_BR' => 'CFO',
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];

    public static $ufList_withoutAll = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];

    public static $ufListId = [
        1 => 'AC',
        2 => 'AL',
        3 => 'AM',
        4 => 'AP',
        5 => 'BA',
        6 => 'CE',
        7 => 'DF',
        8 => 'ES',
        9 => 'GO',
        10 => 'MA',
        11 => 'MT',
        12 => 'MS',
        13 => 'MG',
        14 => 'PA',
        15 => 'PB',
        16 => 'PR',
        17 => 'PE',
        18 => 'PI',
        19 => 'RJ',
        20 => 'RN',
        21 => 'RO',
        22 => 'RS',
        23 => 'RR',
        24 => 'SC',
        25 => 'SE',
        26 => 'SP',
        27 => 'TO'
    ];

    public static $mesList = [
        1 => 'Janeiro',
        2 => 'Favereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    ];

    public static $catList = [
        'ALL' => 'Todos',
        'CD' => 'CD - Cirurgião Dentista',
        'EPAO' => 'EPAO - Entidade Prestadora de Assistência Odontológica',
        'TPD' => 'TPD - Técnico em Prótese Dentária',
        'LB' => 'LB - Laboratório de Prótese Dentária',
        'TSB' => 'TSB - Técnico em Saúde Bucal',
        'ASB' => 'ASB - Auxiliar em Saúde Bucal',
        'APD' => 'APD - Auxiliar de Prótese Dentária',
        'ECIPO' => 'ECIPO - Empresa que Comercializa e/ou Industrializa Produto Odontológico'
    ];

    public static $catListPf = [
        'ALL' => 'Todos',
        'CD' => 'CD - Cirurgião Dentista',
        'TPD' => 'TPD - Técnico em Prótese Dentária',
        'TSB' => 'TSB - Técnico em Saúde Bucal',
        'ASB' => 'ASB - Auxiliar em Saúde Bucal',
        'APD' => 'APD - Auxiliar de Prótese Dentária'
    ];

    public static $catListFE = [
        'ALL' => 'Todos',
        'CD' => 'CD - Cirurgião Dentista',
        'TPD' => 'TPD - Técnico em Prótese Dentária',
    ];

    public static $catListPj = [
        'ALL' => 'Todos',
        'EPAO' => 'EPAO - Entidade Prestadora de Assistência Odontológica',
        'LB' => 'LB - Laboratório de Prótese Dentária',       
        'ECIPO' => 'ECIPO - Empresa que Comercializa e/ou Industrializa Produto Odontológico'
    ];

    public static $catListCursos = [
        'ALL' => 'Todos',
        'ODONTOLOGIA' => 'ODONTOLOGIA',
        'AUXILIAR EM SAÚDE BUCAL' => 'AUXILIAR EM SAÚDE BUCAL',
        'TECNICO EM SAÚDE BUCAL' => 'TECNICO EM SAÚDE BUCAL',
        'AUXILIAR DE PRÓTESE DENTÁRIA' => 'AUXILIAR DE PRÓTESE DENTÁRIA',
        'TECNICO EM PRÓTESE DENTÁRIA' => 'TECNICO EM PRÓTESE DENTÁRIA'
    ];

    public static $services = [
        'CI' => 'Consulta Integrada',
        'CE' =>  [
            'CE' => 'Consulta Estatística',
            'CE1' => 'CRO x Categoria x População x Sexo',
            'CE2' => 'CRO x Categoria x Ano de Registro',
            'CE3' => 'CRO x Categoria x Faixa Etária',
            'CE4' => 'Especialidade x CRO x Sexo',
            'CE5' => 'CRO x Especialidade x Faixa Etária',
            'CE6' => 'Municipio x Inscritos',
            'CE7' => 'CFO ID Emitidas',
            'CE8' => 'CFO ID Consulta',

        ],
        'CA' => [
            'CA' => 'Consulta de Auditoria',
            'CA1' => 'CPF/CNPJ duplicados',
            'CA2' => 'CPF/CNPJ inválidos',
            'CA3' => 'Pré-cadastros com número de inscrição',
            'CA4' => 'Cadastros provisórios vencidos',
            'CA5' => 'Ativo em mais de um CRO ',
            'CA6' => 'Secundária sem origem ativa ',
            'CA7' => 'Empresas ativas sem responsável técnico',
            'CA8' => 'Filial ativa sem a respectiva matriz',
            'CA9' => 'RT em mais de uma empresa',
            'CA10' => 'Empresas Isentas',
            'CA11' => 'Profissionais sem data de colação',
            'CA12' => 'Profissionais ativos sem e-mail',
            'CA13' => 'Inscritos sem data de inscrição',
            'CA14' => 'Usuários do CRO',
            'CA15' => 'CPF duplicado mesma categoria',
            'CA16' => 'Total de identidades únicas emitidas',
            'CA17' => 'Total de identidades emitidas consolidado por CRO',
            'CA18' => 'Desativados por caducidade ou cancelamento ex-oficio',
            'CA19' => 'Profissionais ativos com mais de 80 anos',

        ],
        'CF' => [
            'CF' => 'Consulta de Fiscalização',
            'CF1' => 'Estatísticas de Fiscalizações',
            'CF2' => 'Estatísticas de Fiscalizações sem Inscrição',

        ],
        'CS' => 'Consulta SIGESP',
        'RE' => [
            'RE1' => 'Relatório de Adimplência',
            'RE2' => 'Relatório de Tarifas',
            'RE3' => 'Relatório LAI/LGPD',
        ],
    ];

    public static function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);
        if (strlen($cpf) != 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    public static function getLastUpdate(): array
    {
        $script =   "SELECT * FROM CFO_CWS.dbo.vw_ultima_atualizacao_bd
                    where CRO = 'CFO/BR'";

        try {
            $db = Database3::getInstance();
            $con = $db->getConnection();

            $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $con->prepare($script);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_NUM);
            $arr_result = $stmt->fetchAll();
            $stmt = null;

            // Error handling
        } catch (PDOException $e) {
            die("Falha ao conectar ao banco de dados: " . $e->getMessage());
        }

        return $arr_result[0];
    }

    public static function formatarTelefone($numero) {
        $numero = preg_replace('/[^0-9]/', '', $numero);
    
        if (strlen($numero) == 11) {
            return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 5) . '-' . substr($numero, 7);
        } elseif (strlen($numero) == 10) {
            return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 4) . '-' . substr($numero, 6);
        } else {
            return $numero;
        }
    }

    public static function getLabels(){
        try{

            $db = Database1::getInstance();
            $conn = $db->getConnection();

            $query = "SELECT * FROM tbl_labels";

            
        }catch(Exception $e){
            die("Erro ao retornar os dados: " . $e->getMessage());
        }
    }

    /**
     * Verifica se o usuário tem permissão para acessar consultas RFB
     * Verifica CRacesso na tabela tbl_acessos
     */
    public static function temPermissaoRFB()
    {
        try {
            // Se não estiver logado, não tem permissão
            if (!Session::get('id')) {
                return false;
            }

            // Grupos administrativos têm acesso total
            if (Session::get('grupo') == 0 || Session::get('grupo') == 1) {
                return true;
            }

            // Buscar permissão do usuário
            $db = Database1::getInstance();
            $con = $db->getConnection();
            
            $grupo = Session::get('grupo') != 0 && Session::get('grupo') != 1 ? 'CRO' : 'CFO';
            $subgrupo = Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo');
            
            $query = "SELECT CRacesso FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
            $stmt = $con->prepare($query);
            $stmt->execute([':grupo' => $grupo, ':subgrupo' => $subgrupo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row && isset($row['CRacesso']) && $row['CRacesso'] == true;
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão RFB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se o usuário tem permissão para consultar/selecionar na Consulta RFB
     * Verifica CRselect na tabela tbl_acessos
     */
    public static function temPermissaoRFBSelect()
    {
        try {
            // Se não estiver logado, não tem permissão
            if (!Session::get('id')) {
                return false;
            }

            // Grupos administrativos têm acesso total
            if (Session::get('grupo') == 0 || Session::get('grupo') == 1) {
                return true;
            }

            // Buscar permissão do usuário
            $db = Database1::getInstance();
            $con = $db->getConnection();
            
            $grupo = Session::get('grupo') != 0 && Session::get('grupo') != 1 ? 'CRO' : 'CFO';
            $subgrupo = Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo');
            
            $query = "SELECT CRselect FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
            $stmt = $con->prepare($query);
            $stmt->execute([':grupo' => $grupo, ':subgrupo' => $subgrupo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row && isset($row['CRselect']) && $row['CRselect'] == true;
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão RFB Select: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se o usuário tem permissão para acessar o Gerenciamento RFB
     * Verifica CR1acesso na tabela tbl_acessos
     */
    public static function temPermissaoGerenciarRFB()
    {
        try {
            // Se não estiver logado, não tem permissão
            if (!Session::get('id')) {
                return false;
            }

            // Grupos administrativos têm acesso total
            if (Session::get('grupo') == 0 || Session::get('grupo') == 1) {
                return true;
            }

            // Buscar permissão do usuário
            $db = Database1::getInstance();
            $con = $db->getConnection();
            
            $grupo = Session::get('grupo') != 0 && Session::get('grupo') != 1 ? 'CRO' : 'CFO';
            $subgrupo = Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo');
            
            $query = "SELECT CR1acesso FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
            $stmt = $con->prepare($query);
            $stmt->execute([':grupo' => $grupo, ':subgrupo' => $subgrupo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row && isset($row['CR1acesso']) && $row['CR1acesso'] == true;
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão Gerenciar RFB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se o usuário tem permissão para consultar/selecionar no Gerenciamento RFB
     * Verifica CR1select na tabela tbl_acessos
     */
    public static function temPermissaoGerenciarRFBSelect()
    {
        try {
            // Se não estiver logado, não tem permissão
            if (!Session::get('id')) {
                return false;
            }

            // Grupos administrativos têm acesso total
            if (Session::get('grupo') == 0 || Session::get('grupo') == 1) {
                return true;
            }

            // Buscar permissão do usuário
            $db = Database1::getInstance();
            $con = $db->getConnection();
            
            $grupo = Session::get('grupo') != 0 && Session::get('grupo') != 1 ? 'CRO' : 'CFO';
            $subgrupo = Helper::$subAcessList[Session::get('subgrupo')] ?? Session::get('subgrupo');
            
            $query = "SELECT CR1select FROM tbl_acessos WHERE grupo = :grupo AND subgrupo = :subgrupo";
            $stmt = $con->prepare($query);
            $stmt->execute([':grupo' => $grupo, ':subgrupo' => $subgrupo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row && isset($row['CR1select']) && $row['CR1select'] == true;
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão Gerenciar RFB Select: " . $e->getMessage());
            return false;
        }
    }
}