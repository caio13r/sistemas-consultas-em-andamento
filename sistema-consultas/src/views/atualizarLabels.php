<?php
    use Cfo\SisConsultas\lib\Labels;

    $instance = new Labels();
    $labels = $instance->cacheLabelsRedis();

    if ($labels) {
        echo "<link rel='stylesheet' href='../assets/css/styles.css'>
            <div classs='container p-5'>
                <div class='row no-gutters'>
                <div class='col-lg-6 col-md-12 m-auto'>
                    <div class='alert alert-success m-4 fade show' role='alert'>
                    <h4 class='alert-heading mt-2 mb-4'>Labels do sistema atualizadas!</h4>
                        <p>
                        <i>Você será redirecionado em instantes.</i>
                        <meta http-equiv='refresh' content='2;url=/index'>
                        </p>
                    </div>
                </div>
            </div>
            </div>";
    }else {
        echo "<link rel='stylesheet' href='../assets/css/styles.css'>
            <div classs='container p-5'>
                <div class='row no-gutters'>
                <div class='col-lg-6 col-md-12 m-auto'>
                    <div class='alert alert-danger m-4 fade show' role='alert'>
                    <h4 class='alert-heading mt-2 mb-4'>Labels do sistema não atualizadas!</h4>
                        <p>
                        Ocorreu um erro ao tentar atualizar as labels do sistema.<br>
                        <i>Você será redirecionado em instantes.</i>
                        <meta http-equiv='refresh' content='2;url=/index'>
                        </p>
                    </div>
                </div>
            </div>
            </div>";
    }
?>      