<?php
require_once INC_PATH . '/header.php';

use Cfo\SisConsultas\lib\Session;
use Cfo\SisConsultas\services\relatorios\classes\Connection;
use Cfo\SisConsultas\lib\Helper;
use Cfo\SisConsultas\services\relatorios\classes\QueryHelper;

// conecta com o banco
Session::CheckSession();

?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var selectField = document.getElementById('f_portallai');

        selectField.addEventListener('change', function() {
            if (selectField.value !== '0') {
                $('#f_sitesolu').prop('disabled', true);
            } else {
                $('#f_sitesolu').prop('disabled', false);
                $('#f_sitesolu').prop('required', true);
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        var selectField = document.getElementById('f_autlgpd');

        selectField.addEventListener('change', function() {
            if (selectField.value !== '1') {
                $('#f_explgpd').prop('disabled', true);
            } else {
                $('#f_explgpd').prop('disabled', false);
                $('#f_explgpd').prop('required', true);
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        var selectField = document.getElementById('f_autlgpd');

        selectField.addEventListener('change', function() {
            if (selectField.value !== '1') {
                $('#f_arealgpd').prop('disabled', true);
            } else {
                $('#f_arealgpd').prop('disabled', false);
                $('#f_arealgpd').prop('required', true);
            }
        });
    });
    window.addEventListener('load', function() {
        setTimeout(fecharAlerta, 5000);
    });
</script>
<script type="text/javascript">
    function checkForm() {
        var inputs = document.getElementsByClassName('required');
        var len = inputs.length;
        var valid = true;
        for (var i = 0; i < len; i++) {
            if (!inputs[i].value) {
                valid = false;
            }
        }
        if (!valid) {
            alert('Por favor, preencha todos os campos.');
            return false;
        } else {
            return true;
        }
    }

    function fMasc(objeto, mascara) {
        obj = objeto
        masc = mascara
        setTimeout("fMascEx()", 1)
    }

    function fMascEx() {
        obj.value = masc(obj.value)
    }

    function mPort(port) {
        port = port.replace(/\D/g, "")
        port = port.replace(/^(\d{2})(\d)/, "$1/$2")
        return port
    }

    function mYear(year) {
        year = year.replace(/\D/g, "")
        return year
    }

    function fecharAlerta() {
        elemento = document.querySelector(".alert")
        if (elemento) {
            elemento.classList.add('oculto');
            elemento.addEventListener('transitionend', function() {
                elemento.style.display = 'none';
            });
        }
    }
</script>

<style>
    .alert {
        transition: opacity 0.5s ease;
    }

    .oculto {
        opacity: 0;
    }
</style>
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-file mr-2 mt-2"></i>LAI - Lei de Acesso a Informação</h5>
        </div>
        <div class="card-body">
            <a href="/relatorio-lai?id=1"><button type="button" class="btn mb-3 <?= ($_GET['id'] == 1) ? 'btn-primary' : 'btn-secondary' ?>">Perguntas/UF</button></a>
            <a href="/relatorio-lai?id=2"><button type="button" class="btn mb-3 <?= ($_GET['id'] == 2) ? 'btn-primary' : 'btn-secondary' ?>">Respostas/UF</button></a>
            <a href="/relatorio-lai?id=3"><button type="button" class="btn mb-3 <?= ($_GET['id'] == 3) ? 'btn-primary' : 'btn-secondary' ?>">Geral</button></a>
            <a href="?id=4"><button type="button" class="btn mb-3 <?= (Session::get("grupo") == 0) ? ' ' : 'd-none' ?> <?= ($_GET['id'] == 4) ? 'btn-primary' : 'btn-secondary' ?>">Formulario LAI</button></a>
            <a href="/relatorio-lai?id=5"><button type="button" class="btn mb-3 <?= ($_GET['id'] == 5) ? 'btn-primary' : 'btn-secondary' ?>">Dashboard Lai</button></a>

            <div class="alert alert-warning" onload="javascript: setTimeout(fecharAlerta, 5);" role="alert">Por favor, só preencher a solicitação abaixo as INSTITUIÇÕES DE ENSINO que possuem vínculo explicito com à instituição de ensino para a qual solicita acesso. A solicitação abaixo deverá vir acompanhada de ofício (anexado), feito em papel timbrado da Instituição, assinado pelo diretor e/ou responsável pelos cursos de pós-graduação. O ofício poderá ter sido assinada digitalmente, por algum software de assinatura eletrônica.</div>

            <?php
            if (count($inputPost) > 0) {
                $dados = $inputPost;
                $erros = [];

                if (trim($dados['f_autlai']) === "") {
                    $erros["f_autlai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_vinculolai']) === "") {
                    $erros["f_vinculolai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_portlai']) === "") {
                    $erros["f_portlai"] = "Este campo é obrigatório.";
                }
                if (trim($dados['f_sitelai']) === "") {
                    $erros["f_sitelai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_cargolai']) === "") {
                    $erros["f_cargolai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_aptolai']) === "") {
                    $erros["f_aptolai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_aptoautlai']) === "") {
                    $erros["f_aptoautlai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_portallai']) === "") {
                    $erros["f_portallai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_anolai']) === "") {
                    $erros["f_anolai"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_lgpd']) === "") {
                    $erros["f_lgpd"] =  "Este campo é obrigatório.";
                }
                if (trim($dados['f_autlgpd']) === "") {
                    $erros["f_autlgpd"] =  "Este campo é obrigatório.";
                }

                if (!count($erros)) {
                    $grupo = Session::get('grupo');
                    $uf = QueryHelper::$uf_list[$grupo + 1] == "Todos" ? 'CFO' : QueryHelper::$uf_list[$grupo + 1];
                    $email = Session::get('email');

                    $sql = "SELECT * from db_lai.resposta
                            where email = 'gabriel.martins@cfo.org.br'
                            and id_questionario = '2'";
                    $arr_result = Connection::conn_mysql($sql);

                    if (!$arr_result){
                        $sql = "INSERT INTO db_lai.resposta
                        (id_questionario, uf, email, ano)
                        VALUES(2, '{$uf}', '{$email}', '2023')";
                        $arr_result = Connection::conn_mysql($sql);

                        $sql = "INSERT INTO db_lai.respostas_form_lai
                        (id_questionario, uf, email, ano, f_autlai, f_portlai, f_cargolai, f_vinculolai, f_aptoautlai, f_aptolai, f_sitelai, f_portallai, f_sitesolu, f_anolai, f_lgpd, f_autlgpd, f_arealgpd, f_explgpd)
                        VALUES(2, '{$uf}', '{$email}', '2023', '{$dados['f_autlai']}', '{$dados['f_portlai']}', '{$dados['f_cargolai']}', '{$dados['f_vinculolai']}', '{$dados['f_aptoautlai']}', '{$dados['f_aptolai']}', '{$dados['f_sitelai']}', '{$dados['f_portallai']}', '{$dados['f_sitesolu']}', '{$dados['f_anolai']}', '{$dados['f_lgpd']}', '{$dados['f_autlgpd']}', '{$dados['f_arealgpd']}', '{$dados['f_explgpd']}');";

                        try {
                            $arr_result = Connection::conn_mysql($sql);
                            $msg = "<script language='javascript'>
                                    window.alert('Resposta enviada com sucesso.')
                                    window.location.href='index';
                                </script>";
                            echo $msg;
                        } catch (Exception $e) {
                            die("Falha ao conectar ao banco de dados: " . $e->getMessage());
                        }
                    }else{
                        $msg = "<script language='javascript'>
                        window.alert('Você já respondeu este formulario!')
                        window.location.href='index';
                        </script>";
                        echo $msg;
                    }
                }
            }
            ?>


            <div class="col-md-12 mt-2 mb-2">
                <form action="#" method="POST" enctype="multipart/form-data" onsubmit="return checkForm()">
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="f_autlai">Qual o nome da pessoa que foi nomeada autoridade LAI no CRO?</label>
                            <input type="text" id="f_autlai" name="f_autlai" minlength="5" maxlength="200" class="form-control <?= $erros['f_autlai'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_autlai"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_autlai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="f_portlai">Qual a portaria que nomeou a autoridade LAI (número e ano)?</label>
                            <input type="text" id="f_portlai" name="f_portlai" maxlength="7" onkeydown="javascript: fMasc(this, mPort);" class="form-control <?= $erros['f_portlai'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_portlai"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_portlai"] ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="f_cargolai">Qual o cargo da pessoa que é autoridade LAI no CRO?</label>
                            <input type="text" id="f_cargolai" name="f_cargolai" maxlength="50" class="form-control <?= $erros['f_cargolai'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_cargolai"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_cargolai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="f_vinculolai">Qual o tipo de vínculo empregatício da autoridade LAI com o CRO?</label>
                            <input type="text" id="f_vinculolai" name="f_vinculolai" maxlength="300" class="form-control <?= $erros['f_vinculolai'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_vinculolai"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_vinculolai"] ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="f_aptoautlai">A Autoridade LAI no CRO se sente apta para tal função?</label>
                            <select id="f_aptoautlai" name="f_aptoautlai" class="form-control <?= $erros['f_aptoautlai'] ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_aptoautlai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="f_aptolai">A Autoridade LAI no CRO foi capacitada para isso?</label>
                            <select id="f_aptolai" name="f_aptolai" class="form-control <?= $erros['f_aptolai'] ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_aptolai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="f_sitelai">Está publicada no site do CRO?</label>
                            <select id="f_sitelai" name="f_sitelai" class="form-control <?= $erros['f_sitelai'] ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_sitelai"] ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="f_portallai">O Portal da Transparência do CRO é próprio ou utiliza uma solução de terceiros?</label>
                            <select id="f_portallai" name="f_portallai" class="form-control <?= $erros['f_portallai'] ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="1">Próprio</option>
                                <option value="0">Solução de Terceiros</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_portallai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="f_sitesolu">Se utilizada uma solução de terceiros, qual é essa solução?</label>
                            <input disabled type="text" id="f_sitesolu" name="f_sitesolu" maxlength="50" class="form-control <?= $erros['f_sitesolu'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_sitesolu"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_sitesolu"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="f_anolai">O Portal da Transparência contém dados a partir de qual ano?</label>
                            <input type="text" id="f_anolai" name="f_anolai" minlength="4" maxlength="4" onkeydown="javascript: fMasc(this, mYear);" class="form-control <?= $erros['f_anolai'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_anolai"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_anolai"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-4 ">
                            <label for="f_lgpd">Em que grau o seu CRO já está se adequando à LGPD?</label>
                            <select id="f_lgpd" name="f_lgpd" class="form-control <?= $erros['f_lgpd'] ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Selecione</option>
                                <option value="5">Totalmente adequado</option>
                                <option value="4">Razoavelmente adequado</option>
                                <option value="3">Pouco adequado</option>
                                <option value="2">Em processo de contratação de empresa</option>
                                <option value="1">Não iniciamos a adequação</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_lgpd"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="f_autlgpd">Foi oficialmente definido no CRO um responsável pela LGPD?</label>
                            <select id="f_autlgpd" name="f_autlgpd" class="form-control <?= $erros['f_autlgpd'] ? 'is-invalid' : '' ?>" required>
                                <option disabled selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_autlgpd"] ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="f_arealgpd">Se alguém foi definido como responsável, essa pessoa é de qual área dentro do CRO?</label>
                            <input type="text" id="f_arealgpd" name="f_arealgpd" maxlength="30" disabled class="form-control <?= $erros['f_arealgpd'] ? 'is-invalid' : '' ?>" value="<?= $dados["f_arealgpd"]; ?>">
                            <div class="invalid-feedback">
                                <?= $erros["f_arealgpd"] ?>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="f_explgpd">Se alguém foi definido como responsável, essa pessoa tem alguma experiência na área?</label>
                            <select id="f_explgpd" name="f_explgpd" disabled class="form-control <?= $erros['f_explgpd'] ? 'is-invalid' : '' ?>">
                                <option value="" disabled selected>Selecione</option>
                                <option value="Sim">Sim</option>
                                <option value="Não">Não</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= $erros["f_explgpd"] ?>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary col-md-2 offset-md-5" style="margin-top: +10px">Enviar</button>
                </form>
            </div>

        </div>
    </div>
</div>
</div>
<?php
require_once INC_PATH .'/footer.php';
?>