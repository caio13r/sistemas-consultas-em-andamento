<?php

namespace Cfo\SisConsultas\services\relatorios\classes;

class QueryHelper
{
    public static $uf_list = [
        1 => 'Todos',
        2 => 'AC',
        3 => 'AL',
        4 => 'AM',
        5 => 'AP',
        6 => 'BA',
        7 => 'CE',
        8 => 'DF',
        9 => 'ES',
        10 => 'GO',
        11 => 'MA',
        12 => 'MT',
        13 => 'MS',
        14 => 'MG',
        15 => 'PA',
        16 => 'PB',
        17 => 'PR',
        18 => 'PE',
        19 => 'PI',
        20 => 'RJ',
        21 => 'RN',
        22 => 'RO',
        23 => 'RS',
        24 => 'RR',
        25 => 'SC',
        26 => 'SE',
        27 => 'SP',
        28 => 'TO'
    ];

    public static $mes_extenso = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
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

    /**
     * 🔥 Monta a query para um UF específico, substituindo @uf e @cro_uf
     */
    public static function buildQuery(string $uf, string $path = 'script_baixas'): string
    {
        $filePath = realpath(dirname(__FILE__, 2)) . "/script/split_{$path}.sql";

        if (!file_exists($filePath)) {
            die("Arquivo SQL não encontrado: {$filePath}");
        }

        $script = file_get_contents($filePath);

        // Substituição dos placeholders
        $script = str_replace('@uf', strtoupper($uf), $script);
        $script = str_replace('@cro_uf', strtolower($uf), $script);

        // Caso seja 'Todos'
        if (strtoupper($uf) === 'TODOS' || strtoupper($uf) === 'ALL') {
            $script = str_replace("= 'Todos'", "<> 'Todos'", $script);
        }

        return $script;
    }

    /**
     * 🔥 Monta UNION das queries por array de UFs
     */
    public static function BuildQuerybyArray(array $ufs, string $path = 'script_baixas'): string
    {
        $script = '';

        if (empty($ufs)) {
            $ufs = array_values(self::$uf_list);
            array_shift($ufs); // Remove 'Todos'
        }

        foreach ($ufs as $index => $uf) {
            if ($index === 0) {
                $script = self::buildQuery($uf, $path);
            } else {
                $script .= ' UNION ' . self::buildQuery($uf, $path);
            }
        }

        return $script;
    }

    /**
     * 🔗 Alias da função BuildQuerybyArray
     */
    public static function BuildQuerybyUF(array $uf_list, string $path = 'script_baixas'): string
    {
        return self::BuildQuerybyArray($uf_list, $path);
    }

    // 🔧 Demais funções auxiliares permanecem iguais, seguem abaixo:

    public static function dataRefactoring(array $data, array $new_order): array
    {
        $result = [];
        foreach ($data as $row) {
            $aux = [];
            foreach ($new_order as $item) {
                $aux[] = ($item === '-') ? '-' : ($row[$item] ?? '');
            }
            $result[] = $aux;
        }
        return $result;
    }

    public static function setDataHorizontal(array $data, array $new_data, string $insert_row): array
    {
        $result = [];

        if ($insert_row === 'total') {
            $insert_row = count($data) - 1;
            foreach ($new_data as $i => $item) {
                if ($item === 'sum') {
                    $new_data[$i] = self::getSum($data, $i);
                }
            }
        } else {
            $insert_row = max(0, (int) $insert_row - 1);
        }

        foreach ($data as $idx => $row) {
            $result[] = $row;
            if ($insert_row === $idx) {
                $result[] = $new_data;
            }
        }

        return $result;
    }

    public static function setDataVertical(array $data, array $new_data, string $op_type = ''): array
    {
        $result = [];

        foreach ($data as $idx => $row) {
            $aux = $row;

            if ($op_type === '') {
                $aux[$new_data[0]] = (string) ($aux[$new_data[0]] ?? '') . $new_data[1];
            } else {
                foreach ($aux as $i => $val) {
                    if ($val === $op_type) {
                        $aux[$i] = $new_data[$idx] ?? $val;
                    }
                }
            }
            $result[] = $aux;
        }

        return $result;
    }

    public static function getSum(array $data, int $column): float
    {
        return array_reduce($data, function ($carry, $item) use ($column) {
            return $carry + (float) ($item[$column] ?? 0);
        }, 0);
    }

    public static function getDate(string $date, string $pattern = '/([0-9]{4})-([0-9]{2})-([0-9]{2})/'): array
    {
        preg_match($pattern, $date, $result);
        return array_slice($result, 1);
    }

    public static function getDateEnd(array $date): string
    {
        $year = (int) $date[0];
        $month = (int) $date[1];
        $day = (int) $date[2];

        if ($month >= 12) {
            $year += 1;
            $month = 1;
        } else {
            $month += 1;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public static function transformToInt(array $arr): array
    {
        $arr[3] = floatval($arr[3] ?? 0);
        $arr[4] = floatval(str_replace(',', '.', preg_replace('/[^0-9,]/', '', $arr[4] ?? '0')));
        return $arr;
    }

    public static function combineData(array $dataEmail, array $dataPhone, string $key): array
    {
        $combinedData = [];
        $phoneMap = [];

        foreach ($dataPhone as $phone) {
            $phoneMap[$phone[$key]] = $phone;
        }

        foreach ($dataEmail as $email) {
            $combined = $email;
            if (isset($phoneMap[$email[$key]])) {
                $combined = array_merge($combined, $phoneMap[$email[$key]]);
            }
            $combinedData[] = $combined;
        }

        return $combinedData;
    }

    public static function connectionViewDelegado(array $filters): array
    {
        $uf = $filters['uf'] ?? 'ALL';

        $sqlEmail = "
            SELECT 
                CpfCnpj, 
                profissional, 
                Email, 
                CRO, 
                CATEGORIA, 
                INSCRICAO
            FROM CFO_CWS.dbo.vw_Delegado_Eleitor_Email
            WHERE (:uf = 'ALL' OR CRO = :uf)
        ";

        $sqlPhone = "
            SELECT 
                CpfCnpj, 
                telefone 
            FROM CFO_CWS.dbo.vw_Delegado_Eleitor_telefone
            WHERE (:uf = 'ALL' OR CRO = :uf)
        ";

        $dataEmail = Connection::conn_Sqlsrv('email', $sqlEmail, ['uf' => $uf]);
        $dataPhone = Connection::conn_Sqlsrv('phone', $sqlPhone, ['uf' => $uf]);

        return self::combineData($dataEmail, $dataPhone, 'CpfCnpj');
    }
}
