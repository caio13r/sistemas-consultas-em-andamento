<?php
    use Cfo\SisConsultas\lib\Session;
    use Cfo\SisConsultas\database\Database3;
    use Cfo\SisConsultas\lib\Helper;

    Session::CheckSession();

    if (Session::get('grupo') != 0 && $row['TC'.$tipoConsulta.'acesso'] == false) {
        echo "<script language='javascript'>
        window.alert(' Vocês não tem permissão para acessar essa página.')
        window.location.href='tabelas-centralizadas';
        </script>";
        exit;
    }

    $tituloConsulta;

    foreach($labelsTC as $key => $label) {
        if ($key == $tipoConsulta) {
            $tituloConsulta = $label;
        }
    }

    $db = Database3::getInstance();
    $con = $db->getConnection();
    
?>        

        <div class="col-md-8 offset-md-2">
            <h4><?=$tituloConsulta?></h4>
            <form action="" method="post">
                <div class="form-group col-md">
                    <label for="curso">Selecione o Curso:</label>
                    <select id="curso" name="curso" class="form-control" required>
                        <?php
                            foreach (Helper::$catListCursos as $val => $value) {
                                $selected = (!empty($inputPost['curso']) && $inputPost['curso'] == $val) ? 'selected' : '';
                                echo "<option value='$val' $selected>$value</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="col-md">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1" value='1' onclick="showInput(1)" checked>
                            <label class="form-check-label" for="flexRadioDefault1">Pesquisar por Nome (Razão Social ou Nome Fantasia)</label>
                        </div>
                    </div>
                </div>

                <div id="nomeInput" class="form-group col-md-12 mt-3">
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite o Nome (Razão Social ou Nome Fantasia)" value="<?= $inputPost["nome"] ?>">
                </div>
                <button type="submit" name="submit" class="btn btn-primary mt-1 mb-3">Pesquisar</button>
            </form>

        </div>


<?php
    if (isset($inputPost["submit"])) {
        
        $erro = false;
        $result;
        $cursoWhere;
        $nomeWhere;

        if (!empty($inputPost["curso"]) && $inputPost["curso"] != "ALL") {
            $cursoWhere = "CLFA.[Cursos] LIKE '%{$inputPost['curso']}%'";
        } else {
            $cursoWhere = "CLFA.[Cursos] IS NOT NULL";
        }

        if (!empty($inputPost["nome"])) {
            $nomeWhere = "(CLFA.[Razao_Social] LIKE '%{$inputPost['nome']}%' OR CLFA.[Nome_Fantasia] LIKE '%{$inputPost['nome']}%')";
        } else {
            $nomeWhere = "CLFA.[Razao_Social] IS NOT NULL";
        }

        if ($erro) {
            echo "<script language='javascript'>
                    window.alert('$erro')
                    window.location.href='tabelas-centralizadas';
                    </script>";
            exit;
        }


        try {

            $sql = "
                SELECT
                    CLFA.[Regional],
                    CLFA.[Ativo],
                    CLFA.[Razao_Social],
                    CLFA.[Nome_Fantasia],
                    CLFA.[CNPJ],
                    CLFA.[Inscricao_Estadual],
                    CLFA.[Sigla],

                    CLFA.[Natureza_Juridica],
                    CLFA.[Codigo],
                    CLFA.[Codigo_Integracao_Federal],
                    CLFA.[Codigo_IE],
                    CLFA.[Reitor],
                    CLFA.[Cursos],
                    CLFA.[Especialidades],
                    CLFA.[Campus],
                    CLFA.[Coordenadores_qtd],

                    --CLFA.[Cidade],
                    --CLFA.[Estado],
                    --CLFA.[Bairro],
                    --CLFA.[Numero],
                    --CLFA.[Complemento],
                    --CLFA.[CEP],
                    --CLFA.[UF],
                    --CLFA.[Email],
                    --CLFA.[Telefone],

                    CLFA.[Observacao],
                    CLFA.[Coordenadores]
                FROM CFO_CWS.dbo.vw_Cons_Listagem_Formacoes_Academicas_IES AS CLFA
                WHERE
                    $cursoWhere
                    AND $nomeWhere
                ORDER BY CLFA.[Razao_Social] ASC
                ";
            
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
        } catch (PDOException $error) {
            echo "<script language='javascript'>
                    window.alert('Erro ao buscar os dados: " . $error->getMessage() . "')
                    window.location.href='tabelas-centralizadas';
                    </script>";
            exit;
        }

        if (count($result) > 0) {
?>

    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value='<?= json_encode($result, JSON_UNESCAPED_UNICODE); ?>'>
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>

    <div class="row mt-4">
        <div class="col">
            <h3>Conselheiros</h3>
            <table id="tabelaFormacao" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
                <thead>
                <tr>
                    <th>CFO</th>
                    <th>Ativo</th>
                    <th>Razão Social</th>
                    <th>Nome Fantasia</th>
                    <th>CNPJ</th>
                    <th>#</th>
                </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($result as $row) {
                        echo "<tr>";
                        echo "<td>" . $row['Regional'] . "</td>";
                        echo "<td>" . $row['Ativo'] . "</td>";
                        echo "<td>" . $row['Razao_Social'] . "</td>";
                        echo "<td>" . $row['Nome_Fantasia'] . "</td>";
                        echo "<td>" . $row['CNPJ'] . "</td>";
                        echo "<td><a href='tabelas-centralizadas?codigo=" . $row['Codigo_Integracao_Federal'] . "#cardEmpresa'><button type='button' class='btn btn-secondary btn-sm'><b>Mais Informações</b></button></a></td>";
                        echo "</tr>";
                    }
                ?>
                </tbody>
            </table>
        </div>

    <!-- Inicialização do DataTables com idioma Português -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelaFormacao').DataTable({
                order: [],
                "iDisplayLength": 50,
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            });
        });
    </script>

<?php
        } else {
            echo "<script language='javascript'>
                    window.alert('Nenhum resultado encontrado.')
                    window.location.href='tabelas-centralizadas?tipoConsulta=$tipoConsulta';
                    </script>";
            exit;
        }
    }
?>
