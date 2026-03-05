<?php 
use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\database\Database1;
use Cfo\SisConsultas\database\Database2;
use Cfo\SisConsultas\database\Database3;
use Cfo\SisConsultas\lib\Helper;

Session::CheckSession();

if (Session::get('grupo') != 0 && $row['CE6acesso'] == false) {
    echo "<script language='javascript'>
    window.alert('Você não tem permissão para acessar essa página.')
    window.location.href='consulta-estatistica';
    </script>";
    exit;
}

$tituloConsulta = 'Estatísticas - Inscritos x Sexo x Especialidade x Munincipio';
?>

<?php 
    try {
        $db = Database2::getInstance();
        $con = $db->getConnection();

        $query = "SELECT DISTINCT enderecocorrespondencia_municipio FROM WSCFO.siscaf_webservice WHERE enderecocorrespondencia_municipio IS NOT NULL ORDER BY enderecocorrespondencia_municipio ASC";
        $stmt = $con->prepare($query);
        // $stmt->bindValue(':cro', "{$inputPost["cro"]}", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOexception $error) {
        die("Erro ao retornar os dados: " . $error->getMessage());
    }
?>

<div class="col-md-10 offset-md-1 mb-4">
<h6 class="mb-2">Consultar profissionais - Sexo x Especialidade x Município</h6>

    <form action="" method="post">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="sexo">Selecione o Sexo:</label>
                <select id="sexo" name="sexo" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todos</option>
                    <option value="F">Feminino</option>
                    <option value="M">Masculino</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="especialidade">Selecione a Especialidade:</label>
                <select id="especialidade" name="especialidade" class="form-control">
                    <option disabled selected value>Selecione</option>
                    <option value="ALL">Todas</option>
                    <option value="ACUPUNTURA">Acupuntura</option>
                    <option value="CIRURGIA E TRAUMATOLOGIA BUCO MAXILO FACIAIS">Cirurgia e Traumatologia Bucomaxilofacial</option>
                    <option value="DENTISTICA">Dentística</option>
                    <option value="DISFUNCAO TEMPOROMANDIBULAR E DOR OROFACIAL">Disfunção Temporomandibular e Dor Orafacial</option>
                    <option value="ENDODONTIA">Endodontia</option>
                    <option value="ESTOMATOLOGIA">Estomatologia</option>
                    <option value="HARMONIZACAO OROFACIAL">Harmonização Orofacial</option>
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
                    <option value="ORTOPEDIA FUNCIONAL DOS MAXILARES">Ortopedia Funcional dos Maxilares</option>
                    <option value="PATOLOGIA ORAL E MAXILO FACIAL">Patologia Oral e Maxilofacial</option>
                    <option value="PERIODONTIA">Periodontia</option>
                    <option value="PROTESE BUCO MAXILO FACIAL">Prótese Bucomaxilofacial</option>
                    <option value="PROTESE DENTARIA">Prótese Dentária</option>
                    <option value="RADIOLOGIA ODONTOLOGICA E IMAGINOLOGIA">Radiolgia Odontológica e Imaginologia</option>
                    <option value="SAUDE COLETIVA">Saúde Coletiva</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="municipio">Selecione o Município :</label>
                <select id="municipio" name="municipio" class="form-control" required>
                    <option disabled selected value>Selecione</option>
                    <?php
                        foreach ($result as $row) {
                            echo "<option value='{$row['enderecocorrespondencia_municipio']}'>{$row['enderecocorrespondencia_municipio']}</option>";
                        }
                    ?>
                </select>
                <span class="mt-1" style="font-size: 80%; color: red;">É obrigatório selecionar o campo de município.</span>
            </div>
        </div>
    <button type="submit" name="submit" class="btn btn-primary">Pesquisar</button>
    </form>
</div>

<?php if (isset($inputPost["submit"])) { 

if ($inputPost['sexo'] === 'ALL' || $inputPost['sexo'] === null) {
    $sexoTable = '';
} else {
    $sexoTable = "AND sexo = '{$inputPost["sexo"]}'";
}

if ($inputPost['especialidade'] === 'ALL' || $inputPost['especialidade'] === null) {
    $especialidadeTable = '';
} else {
    $especialidadeTable = "AND especialidades LIKE '%" . $inputPost["especialidade"] . "%'";
}

try {
    $db = Database2::getInstance();
    $con = $db->getConnection();

    $query = "SELECT * FROM WSCFO.siscaf_webservice WHERE enderecocorrespondencia_municipio = '{$inputPost['municipio']}' $sexoTable $especialidadeTable COLLATE utf8_general_ci";
    $stmt = $con->prepare($query);
    // $stmt->bindValue(':cro', "{$inputPost["cro"]}", PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOexception $error) {
    die("Erro ao retornar os dados: " . $error->getMessage());
}
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
                <th scope="col">Nome</th>
                <th scope="col">Sexo</th>
                <th scope="col">CRO</th>
                <th scope="col">Municipio</th>
                <th scope="col">Inscrição</th>
                <th scope="col">Situação</th>
                <th scope="col">Especialidade</th>
            </tr>
            </thead>
            <tbody>
            <?php
                foreach ($result as $row) {
                    echo "<tr>";
                    echo "<td>" . $row['nomerazaosocial'] . "</td>";
                    echo "<td>" . $row['sexo'] . "</td>";
                    echo "<td>" . $row['cro'] . "</td>";
                    echo "<td>" . $row['enderecocorrespondencia_municipio'] . "</td>";
                    echo "<td>" . $row['inscricao'] . "</td>";
                    echo "<td>" . $row['situacao'] . "</td>";
                    echo "<td>" . $row['especialidades'] . "</td>";
                    echo "</tr>";
                }
            ?>
            </tbody>
        </table>
    </div>  
</div>

<?php } ?>

