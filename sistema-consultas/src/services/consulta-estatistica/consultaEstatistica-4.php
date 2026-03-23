<?php

/* 
Not used  
*/

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && (isset($row['CE4acesso']) && $row['CE4acesso'] == false)) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-auditoria';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - Inscritos x CRO x Especialidade x Sexo';
?>

<div class="col-md-8 offset-md-2 mb-4">
<h6 class="card-title mb-2">Consultar profissionais - Especialidade x CRO x Sexo</h6>
    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cro">Selecione o Estado:</label>
                <select id="cro" name="cro" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option style="font-weight: bold;" disabled><b>País:</b></option>
                    <option value="BR">Brasil</option>
                    <option style="font-weight: bold;" disabled><b>Estados:</b></option>
                    <?php
                      // Validação de Acessso as UFs 
                      if (Session::get('grupo') === 0 || (isset($row['CE4select']) && $row['CE4select'] == true)) {
                        foreach(Helper::$ufList as $val => $value) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                        }            
                      } else {
                        foreach(Helper::$ufList as $val => $value) {
                          if ($users->CheckGroupUf() == $val) {
                            $selected = (!empty($inputPost['cro']) && $inputPost['cro'] == $val) ? 'selected' : '';
                            echo "<option value='$val' $selected>$value</option>";
                          }
                        }   
                      }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="especialidade">Selecione a Especialidade:</label>
                <select id="especialidade" name="especialidade" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todas</option>
                    <option value="ACUPUNTURA">Acupuntura</option>
                    <option value="CIRURGIA E TRAUMATOLOGIA BUCO MAXILO FACIAIS">Cirurgia e Traumatologia Bucomaxilofacial</option>
                    <option value="DENTÍSTICA">Dentística</option>
                    <option value="DENTISTICA RESTAURADORA">Denstística Restauradora</option>
                    <option value="DISFUNCAO TEMPOROMANDIBULAR E DOR OROFACIAL">Disfunção Temporomandibular e Dor Orafacial</option>
                    <option value="ENDODONTIA">Endodontia</option>
                    <option value="ESTOMATOLOGIA">Estomatologia</option>
                    <option value="HARMONIZAÇÃO OROFACIAL">Harmonização Orofacial</option>
                    <option value="HOMEOPATIA">Homeopatia</option>
                    <option value="IMPLANTODONTIA">Implantodontia</option>
                    <option value="ODONTOGERIATRIA">Odontogeriatria</option>
                    <option value="ODONTOLOGIA DO ESPORTE">Odontologia do Esporte</option>
                    <option value="ODONTOLOGIA DO TRABALHO">Odontologia do Trabalho</option>
                    <option value="ODONTOLOGIA EM SAUDE COLETIVA">Odontologia em Saúde Coletiva</option>
                    <option value="ODONTOLOGIA LEGAL">Odontologia Legal</option>
                    <option value="ODONTOLOGIA PARA PACIENTES COM NECESSIDADES ESPECIAIS">Odontologia Para Pacientes Com Necessidades Especiais</option>
                    <option value="ODONTOPEDIATRIA">Odontopediatria</option>
                    <option value="ORTODONTIA">Ortodontia</option>
                    <option value="ORTODONTIA E ORTOPEDIA FACIAL">Ortopedia e Ortopedia Facial</option>
                    <option value="ORTOPEDIA FUNCIONAL DOS MAXILARES">Ortopedia Funcional dos Maxilares</option>
                    <option value="PATOLOGIA BUCAL">Patologia Bucal</option>
                    <option value="PATOLOGIA ORAL E MAXILO FACIAL">Patologia Oral e Maxilofacial</option>
                    <option value="PERIODONTIA">Periodontia</option>
                    <option value="PRÓTESE BUCO MAXILO FACIAL">Prótese Bucomaxilofacial</option>
                    <option value="PRÓTESE DENTÁRIA">Prótese Dentária</option>
                    <option value="RADIOLOGIA">Radiologia</option>
                    <option value="RADIOLOGIA ODONTOLOGICA E IMAGINOLOGIA">Radiolgia Odontológica e Imaginologia</option>
                    <option value="SAUDE COLETIVA">Saúde Coletiva</option>
                    <option value="SAÚDE COLETIVA E DA FAMÍLIA">Saúde Coletiva e da Família</option>
                </select>
            </div>
        </div>
        <button type="submit" name="especialidade_cro" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php if (isset($inputPost["especialidade_cro"])) { 

    $croCondition = "";
    $croParam = null;
    if ($inputPost['cro'] === 'ALL' || $inputPost['cro'] === null) {
        $croCondition = "WHERE CRO IS NOT NULL";
    } else {
        if (!array_key_exists($inputPost["cro"], Helper::$ufList)) {
            echo "<div class='alert alert-danger mt-3'><b>Erro!</b> CRO inválido.</div>";
            return;
        }
        $croCondition = "WHERE CRO = :cro";
        $croParam = $inputPost["cro"];
    }

    if ($inputPost['especialidade'] === 'ALL' || $inputPost['especialidade'] === null) {
        $especialidadeTable = '';
    } else {
        $especialidadeTable = "AND especializacao LIKE '%" . $inputPost["especialidade"] . "%'";
    }

    try {
        $db = Database3::getInstance();
        $con = $db->getConnection();
    
        $query = "SELECT * FROM CFO_CWS.dbo.vw_Cons_Dados_Basicos_Especialidades_Sexo_Somados_CRO $croCondition $especialidadeTable";
        $stmt = $con->prepare($query);
        if ($croParam !== null) { $stmt->bindValue(':cro', $croParam); }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        error_log("Erro consulta estatistica: " . $error->getMessage());
        echo "<div class='alert alert-danger mt-3'><b>Erro!</b> Falha ao executar a consulta.</div>";
        $result = [];
    }

    $total_fem = array_sum(array_column($result, 'FEMININO'));
    $total_mas = array_sum(array_column($result, 'MASCULINO'));
    $total_geral = array_sum(array_column($result, 'TOTAL_GERAL'));
?>

<?php if (!empty($result)) { ?>
    <div class="row justify-content-end mr-1">
        <form action="ExcelDownload" method="post">
            <input type="hidden" name="tituloConsulta" value="<?= $tituloConsulta ?>">
            <input type="hidden" name="dadosConsulta" value="<?= htmlspecialchars(json_encode($result)); ?>">
            <button type="submit" name="ExcelDownload" class="btn btn-md btn-success">Excel</button>
        </form>
    </div>
<?php } ?>

<div class="row mt-4">
    <div class="col table-responsive">
        <table id="tabelaConsultas" class="table table-sm table-bordered table-striped table-hover mt-4 mb-4">
            <thead>
            <tr>
                <th scope="col">Especialidade</th>
                <th scope="col">CRO</th>
                <th scope="col">Feminino</th>
                <th scope="col">Masculino</th>
                <th scope="col">Total Geral</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['especializacao']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . number_format($row['FEMININO'], 0, ',', '.')  . "</td>";
                    echo "<td>" . number_format($row['MASCULINO'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['TOTAL_GERAL'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
                if ($inputPost['cro'] === 'ALL' || $inputPost['cro'] === null || $inputPost['especialidade'] === 'ALL' || $inputPost['especialidade'] === null) {
                    echo "<tr>";
                    echo "<td>TOTAL</td>";
                    echo "<td>" . htmlspecialchars($row['CRO']) . "</td>";
                    echo "<td>" . number_format($total_fem, 0, ',', '.')  . "</td>";
                    echo "<td>" . number_format($total_mas, 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($total_geral, 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>
