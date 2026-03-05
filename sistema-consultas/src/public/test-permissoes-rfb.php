<?php
// Diagnóstico de Permissões RFB
require_once realpath(dirname(__FILE__, 2)) . '/config/config.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Permissões RFB</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-shield-alt"></i> Diagnóstico de Permissões RFB</h4>
        </div>
        <div class="card-body">

            <h5>Informações da Sessão Atual:</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="200">ID do Usuário:</th>
                    <td><?= Session::get('id') ?? '<span class="text-danger">NÃO DEFINIDO</span>' ?></td>
                </tr>
                <tr>
                    <th>Nome:</th>
                    <td><?= Session::get('name') ?? '<span class="text-danger">NÃO DEFINIDO</span>' ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?= Session::get('email') ?? '<span class="text-danger">NÃO DEFINIDO</span>' ?></td>
                </tr>
                <tr>
                    <th>Grupo:</th>
                    <td><?= Session::get('grupo') ?? '<span class="text-danger">NÃO DEFINIDO</span>' ?></td>
                </tr>
                <tr>
                    <th>Subgrupo:</th>
                    <td><?= Session::get('subgrupo') ?? '<span class="text-danger">NÃO DEFINIDO</span>' ?></td>
                </tr>
            </table>

            <hr>

            <h5>Permissões RFB:</h5>
            <table class="table table-bordered">
                <tr>
                    <th width="300">Acesso a Consultas RFB:</th>
                    <td>
                        <?php if (Helper::temPermissaoRFB()): ?>
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle"></i> SIM - Você tem acesso!
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-6">
                                <i class="fas fa-times-circle"></i> NÃO - Acesso negado
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Acesso a Gerenciamento RFB:</th>
                    <td>
                        <?php if (Helper::temPermissaoGerenciarRFB()): ?>
                            <span class="badge bg-success fs-6">
                                <i class="fas fa-check-circle"></i> SIM - Você tem acesso!
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning fs-6">
                                <i class="fas fa-times-circle"></i> NÃO - Somente administradores
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <hr>

            <h5>Regras de Acesso:</h5>
            <div class="alert alert-info">
                <h6><i class="fas fa-search"></i> Consultas RFB:</h6>
                <ul>
                    <li>Usuários de CROs regionais (CRO-AC, CRO-AL, etc.)</li>
                    <li>Email específico: <strong>joao.dias@cfo.org.br</strong></li>
                </ul>
            </div>

            <div class="alert alert-warning">
                <h6><i class="fas fa-chart-bar"></i> Gerenciamento RFB:</h6>
                <ul>
                    <li>Email específico: <strong>joao.dias@cfo.org.br</strong></li>
                    <li>Grupo CFO + Subgrupo Administrador</li>
                    <li>Grupo CFO + Subgrupo TI</li>
                </ul>
            </div>

            <hr>

            <div class="text-center">
                <a href="/consulta-rfb" class="btn btn-primary btn-lg">
                    <i class="fas fa-file-invoice"></i> Ir para Consulta RFB
                </a>
                <a href="/index" class="btn btn-secondary btn-lg">
                    <i class="fas fa-home"></i> Voltar ao Início
                </a>
            </div>

        </div>
    </div>
</div>
</body>
</html>
