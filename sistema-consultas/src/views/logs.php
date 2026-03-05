<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\lib\ActivityLog;
use Cfo\SisConsultas\database\Database1;

Session::CheckSession();
Session::CheckAdmin();

// Buscar filtros
$filtros = [];
if (!empty($inputPost['usuario_id'])) {
    $filtros['usuario_id'] = $inputPost['usuario_id'];
}
if (!empty($inputPost['tipo_acao'])) {
    $filtros['tipo_acao'] = $inputPost['tipo_acao'];
}
if (!empty($inputPost['data_inicio'])) {
    $filtros['data_inicio'] = $inputPost['data_inicio'];
}
if (!empty($inputPost['data_fim'])) {
    $filtros['data_fim'] = $inputPost['data_fim'];
}
if (!empty($inputPost['rota'])) {
    $filtros['rota'] = $inputPost['rota'];
}

// Se nenhum filtro, mostrar logs do dia atual
if (empty($filtros)) {
    $filtros['data_inicio'] = date('Y-m-d');
    $filtros['data_fim'] = date('Y-m-d');
}

$logs = ActivityLog::buscarLogs($filtros);
$usuariosComLogs = ActivityLog::buscarUsuariosComLogs();
?>

<div class="container-fluid">

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-clipboard-list mr-2 mt-2"></i>Logs de Atividade</h5>
        </div>
        <div class="card-body">

            <!-- Filtros -->
            <form action="" method="post" class="mb-4">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="usuario_id">Usuário:</label>
                        <select id="usuario_id" name="usuario_id" class="form-control select2-logs">
                            <option value="">Todos</option>
                            <?php foreach ($usuariosComLogs as $u) { ?>
                                <option value="<?= $u['usuario_id'] ?>"
                                    <?= (!empty($filtros['usuario_id']) && $filtros['usuario_id'] == $u['usuario_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['usuario_nome']) ?> (<?= htmlspecialchars($u['usuario_email']) ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="tipo_acao">Tipo de Ação:</label>
                        <select id="tipo_acao" name="tipo_acao" class="form-control">
                            <option value="">Todos</option>
                            <option value="login" <?= (!empty($filtros['tipo_acao']) && $filtros['tipo_acao'] == 'login') ? 'selected' : '' ?>>Login</option>
                            <option value="logout" <?= (!empty($filtros['tipo_acao']) && $filtros['tipo_acao'] == 'logout') ? 'selected' : '' ?>>Logout</option>
                            <option value="acesso_pagina" <?= (!empty($filtros['tipo_acao']) && $filtros['tipo_acao'] == 'acesso_pagina') ? 'selected' : '' ?>>Acesso a Página</option>
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="data_inicio">Data Início:</label>
                        <input type="date" id="data_inicio" name="data_inicio" class="form-control"
                            value="<?= !empty($filtros['data_inicio']) ? $filtros['data_inicio'] : date('Y-m-d') ?>">
                    </div>

                    <div class="form-group col-md-2">
                        <label for="data_fim">Data Fim:</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control"
                            value="<?= !empty($filtros['data_fim']) ? $filtros['data_fim'] : date('Y-m-d') ?>">
                    </div>

                    <div class="form-group col-md-2">
                        <label for="rota">Página/Rota:</label>
                        <input type="text" id="rota" name="rota" class="form-control" placeholder="Ex: /consulta-rfb"
                            value="<?= !empty($filtros['rota']) ? htmlspecialchars($filtros['rota']) : '' ?>">
                    </div>

                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Resumo -->
            <?php
            $totalLogins = 0;
            $totalLogouts = 0;
            $totalAcessos = 0;
            foreach ($logs as $log) {
                if ($log['tipo_acao'] === 'login') $totalLogins++;
                elseif ($log['tipo_acao'] === 'logout') $totalLogouts++;
                else $totalAcessos++;
            }
            ?>
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow-sm h-100 py-2">
                        <div class="card-body py-1">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Registros</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($logs) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-success shadow-sm h-100 py-2">
                        <div class="card-body py-1">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Logins</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalLogins ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-warning shadow-sm h-100 py-2">
                        <div class="card-body py-1">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Logouts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalLogouts ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-info shadow-sm h-100 py-2">
                        <div class="card-body py-1">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Acessos a Páginas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalAcessos ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Logs -->
            <div class="table-responsive">
                <table id="tabelaLogs" class="table table-sm table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Data/Hora</th>
                            <th class="text-center">Usuário</th>
                            <th class="text-center">Grupo</th>
                            <th class="text-center">Subgrupo</th>
                            <th class="text-center">Ação</th>
                            <th class="text-center">Página</th>
                            <th class="text-center">Método</th>
                            <th class="text-center">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)) {
                            $i = 0;
                            foreach ($logs as $log) {
                                $i++;
                                // Badge de cor por tipo de ação
                                $badgeClass = 'badge-secondary';
                                $acaoLabel = $log['tipo_acao'];
                                if ($log['tipo_acao'] === 'login') {
                                    $badgeClass = 'badge-success';
                                    $acaoLabel = 'Login';
                                } elseif ($log['tipo_acao'] === 'logout') {
                                    $badgeClass = 'badge-warning';
                                    $acaoLabel = 'Logout';
                                } elseif ($log['tipo_acao'] === 'acesso_pagina') {
                                    $badgeClass = 'badge-info';
                                    $acaoLabel = 'Acesso';
                                }

                                $dataFormatada = date('d/m/Y H:i:s', strtotime($log['data_hora']));
                        ?>
                            <tr class="text-center">
                                <td><small><?= $i ?></small></td>
                                <td><small><?= $dataFormatada ?></small></td>
                                <td><small><?= htmlspecialchars($log['usuario_nome']) ?></small></td>
                                <td><span class="badge badge-dark"><?= htmlspecialchars($log['usuario_grupo']) ?></span></td>
                                <td><span class="badge badge-dark"><?= htmlspecialchars($log['usuario_subgrupo']) ?></span></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= $acaoLabel ?></span></td>
                                <td><small><code><?= htmlspecialchars($log['rota_acessada']) ?></code></small></td>
                                <td><small><?= htmlspecialchars($log['metodo_http']) ?></small></td>
                                <td><small><?= htmlspecialchars($log['ip_origem']) ?></small></td>
                            </tr>
                        <?php }
                        } else { ?>
                            <tr class="text-center">
                                <td colspan="9">Nenhum log encontrado para os filtros selecionados.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#tabelaLogs').DataTable({
                "pageLength": 50,
                "order": [[1, "desc"]],
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
                }
            });
        }

        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2-logs').select2({
                placeholder: "Selecione um usuário",
                allowClear: true
            });
        }
    });
</script>

<?php
require_once INC_PATH . '/footer.php';
?>
