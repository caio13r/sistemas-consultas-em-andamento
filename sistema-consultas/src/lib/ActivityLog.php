<?php

namespace Cfo\SisConsultas\lib;

use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;
use PDO;

class ActivityLog
{
    /**
     * Registra uma ação no log de atividades
     */
    private static function inserirLog($dados)
    {
        try {
            $db = Database1::getInstance();
            $con = $db->getConnection();

            $sql = "INSERT INTO tbl_logs_atividade
                    (usuario_id, usuario_nome, usuario_email, usuario_grupo, usuario_subgrupo,
                     tipo_acao, rota_acessada, metodo_http, ip_origem, user_agent, sessao_id)
                    VALUES
                    (:usuario_id, :usuario_nome, :usuario_email, :usuario_grupo, :usuario_subgrupo,
                     :tipo_acao, :rota_acessada, :metodo_http, :ip_origem, :user_agent, :sessao_id)";

            $stmt = $con->prepare($sql);
            $stmt->bindValue(':usuario_id', $dados['usuario_id']);
            $stmt->bindValue(':usuario_nome', $dados['usuario_nome']);
            $stmt->bindValue(':usuario_email', $dados['usuario_email']);
            $stmt->bindValue(':usuario_grupo', $dados['usuario_grupo']);
            $stmt->bindValue(':usuario_subgrupo', $dados['usuario_subgrupo']);
            $stmt->bindValue(':tipo_acao', $dados['tipo_acao']);
            $stmt->bindValue(':rota_acessada', $dados['rota_acessada']);
            $stmt->bindValue(':metodo_http', $dados['metodo_http']);
            $stmt->bindValue(':ip_origem', $dados['ip_origem']);
            $stmt->bindValue(':user_agent', $dados['user_agent']);
            $stmt->bindValue(':sessao_id', $dados['sessao_id']);
            $stmt->execute();
        } catch (\Exception $e) {
            error_log("ActivityLog Error: " . $e->getMessage());
        }
    }

    /**
     * Obtém o IP real do usuário
     */
    private static function getIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Resolve o nome do grupo a partir do ID numérico
     */
    private static function resolverGrupo($grupo)
    {
        return Helper::$acessList[$grupo] ?? 'Desconhecido';
    }

    /**
     * Resolve o nome do subgrupo a partir do ID numérico
     */
    private static function resolverSubgrupo($subgrupo)
    {
        return Helper::$subAcessList[$subgrupo] ?? 'Desconhecido';
    }

    /**
     * Registra login do usuário
     * Chamado após autenticação bem-sucedida em Users.php
     */
    public static function registrarLogin($userId, $userName, $userEmail, $grupo, $subgrupo)
    {
        self::inserirLog([
            'usuario_id' => $userId,
            'usuario_nome' => $userName,
            'usuario_email' => $userEmail,
            'usuario_grupo' => self::resolverGrupo($grupo),
            'usuario_subgrupo' => self::resolverSubgrupo($subgrupo),
            'tipo_acao' => 'login',
            'rota_acessada' => '/login',
            'metodo_http' => 'POST',
            'ip_origem' => self::getIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'sessao_id' => session_id(),
        ]);
    }

    /**
     * Registra logout do usuário
     * Chamado antes de destruir a sessão em header.php
     */
    public static function registrarLogout()
    {
        if (Session::get('id') === false) {
            return;
        }

        self::inserirLog([
            'usuario_id' => Session::get('id'),
            'usuario_nome' => Session::get('name'),
            'usuario_email' => Session::get('email'),
            'usuario_grupo' => self::resolverGrupo(Session::get('grupo')),
            'usuario_subgrupo' => self::resolverSubgrupo(Session::get('subgrupo')),
            'tipo_acao' => 'logout',
            'rota_acessada' => '/logout',
            'metodo_http' => 'GET',
            'ip_origem' => self::getIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'sessao_id' => session_id(),
        ]);
    }

    /**
     * Registra acesso a uma página
     * Chamado automaticamente no header.php para cada requisição
     */
    public static function registrarAcesso()
    {
        if (Session::get('id') === false) {
            return;
        }

        $rota = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? 'unknown';

        // Não registrar acessos a assets estáticos ou requisições AJAX de logs
        if (strpos($rota, '/assets/') !== false || $rota === '/logs') {
            return;
        }

        self::inserirLog([
            'usuario_id' => Session::get('id'),
            'usuario_nome' => Session::get('name'),
            'usuario_email' => Session::get('email'),
            'usuario_grupo' => self::resolverGrupo(Session::get('grupo')),
            'usuario_subgrupo' => self::resolverSubgrupo(Session::get('subgrupo')),
            'tipo_acao' => 'acesso_pagina',
            'rota_acessada' => $rota,
            'metodo_http' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'ip_origem' => self::getIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'sessao_id' => session_id(),
        ]);
    }

    /**
     * Busca logs com filtros para a página de administração
     */
    public static function buscarLogs($filtros = [])
    {
        try {
            $db = Database1::getInstance();
            $con = $db->getConnection();

            $where = [];
            $params = [];

            if (!empty($filtros['usuario_id'])) {
                $where[] = "usuario_id = :usuario_id";
                $params[':usuario_id'] = $filtros['usuario_id'];
            }

            if (!empty($filtros['tipo_acao'])) {
                $where[] = "tipo_acao = :tipo_acao";
                $params[':tipo_acao'] = $filtros['tipo_acao'];
            }

            if (!empty($filtros['data_inicio'])) {
                $where[] = "data_hora >= :data_inicio";
                $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
            }

            if (!empty($filtros['data_fim'])) {
                $where[] = "data_hora <= :data_fim";
                $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
            }

            if (!empty($filtros['rota'])) {
                $where[] = "rota_acessada LIKE :rota";
                $params[':rota'] = '%' . $filtros['rota'] . '%';
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT * FROM tbl_logs_atividade $whereClause ORDER BY data_hora DESC LIMIT 5000";

            $stmt = $con->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ActivityLog buscarLogs Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca todos os usuários que possuem logs (para o filtro select)
     */
    public static function buscarUsuariosComLogs()
    {
        try {
            $db = Database1::getInstance();
            $con = $db->getConnection();

            $sql = "SELECT DISTINCT usuario_id, usuario_nome, usuario_email
                    FROM tbl_logs_atividade
                    ORDER BY usuario_nome ASC";

            $stmt = $con->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ActivityLog buscarUsuarios Error: " . $e->getMessage());
            return [];
        }
    }
}
