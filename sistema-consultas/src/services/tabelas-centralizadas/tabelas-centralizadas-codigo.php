<?php

    use Cfo\SisConsultas\lib\Session;
    use Cfo\SisConsultas\database\Database3;
    use Cfo\SisConsultas\lib\Helper;

    Session::CheckSession();

    $db = Database3::getInstance();
    $con = $db->getConnection();

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
        FROM 
            CFO_CWS.dbo.vw_Cons_Listagem_Formacoes_Academicas_IES AS CLFA
        WHERE 
            CLFA.[Codigo_Integracao_Federal] = :codigo
    ";

    $stmt = $con->prepare($sql);
    $stmt->bindValue(':codigo', $_GET["codigo"]);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) > 0) {
?>

        <div class="card shadow mb-4">
          <a href="#collapseCard-1" class="d-block card-header py-3" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="collapseCard-1">
            <h6 class="m-0 font-weight-bold" style="color: #8D0F12;">Dados da Empresa</h6>
          </a>
          <div class="collapse show" id="collapseCard-1">
            <div class="card-body">
              <?php
              if (!empty($result)) {
                foreach ($result as $row) {
              ?>
                  <div class="row">
                    <div class="col-md-3">
                      <label class="font-weight-bold">Ativo:</label>
                      <p><?= $row['Ativo'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Razão Social:</label>
                      <p><?= $row['Razao_Social'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Nome Fantasia:</label>
                      <p><?= $row['Nome_Fantasia'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">CNPJ:</label>
                      <p><?= $row['CNPJ'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Inscrição Estadual:</label>
                      <p><?= $row['Inscricao_Estadual'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Sigla:</label>
                      <p><?= $row['Sigla'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Natureza Juridica:</label>
                      <p><?= $row['Natureza_Juridica'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Código:</label>
                      <p><?= $row['Codigo'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Código Integração Federal:</label>
                      <p><?= $row['Codigo_Integracao_Federal'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Código IE:</label>
                      <p><?= $row['Codigo_IE'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Reitor:</label>
                      <p><?= $row['Reitor'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Cursos:</label>
                      <p><?= $row['Cursos'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Especialidades:</label>
                      <p><?= $row['Especialidades'] ?></p>
                    </div>
                    <div class="col-md-3">
                      <label class="font-weight-bold">Observação:</label>
                      <p><?= $row['Observacao'] ?></p>
                    </div>
                  </div>
              <?php
                }
              } else {
                echo 'Não há dados do profissional cadastrados.';
              }
              ?>
            </div>
          </div>
        </div>
        <a href='tabelas-centralizadas?tipoConsulta=1'>
          <button style='width: 200px;' type='button' class='btn btn-secondary'><b>Voltar para pesquisa</b></button>
        </a>

<?php } ?>