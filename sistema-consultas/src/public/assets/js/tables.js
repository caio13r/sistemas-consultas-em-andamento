// constante de data
const hoje = new Date()
const dia = hoje.getDate().toString().padStart(2,'0')
const mes = String(hoje.getMonth() + 1).padStart(2,'0')
const ano = hoje.getFullYear()
const dataAtual = `${dia}/${mes}/${ano}`

// Tabela users crud
$(document).ready(function() {
    $('#users').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25
    });
});

// Tabela Consulta Integrada
$(document).ready(function() {
    $('#consultaintegrada').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});

// Tabela Consulta Integrada
$(document).ready(function() {
    $('#tabelaConsultas').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});

// Tabela Consulta Integrada
$(document).ready(function() {
    $('#tabelaConsultasNoOrder').DataTable({
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});

$(document).ready(function() {
    $('#consultaSigesp').DataTable({
        order: [[1, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            className: 'btn btn-success btn-md mr-1 rounded',
            messageTop: dataAtual + ' - SIGESP - Consulta de cursos cadastrados'
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - SIGESP - Consulta de cursos cadastrados'
        }
        ]
    });
});




$(document).ready(function() {
    $('#cpfcpnjinativos').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta CPF/CNPJ Inativos'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta CPF/CNPJ Inativos'
        }
        ]
    });
});

$(document).ready(function() {
    $('#precadastrosvencidos').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        }
        ]
    });
});

// Consulta Pré-cadastros vencidos com inscrição
$(document).ready(function() {
    $('#precadastrosvencidosinscricao').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4 p-2'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        }
        ]
    });
});

// Consulta Cadastros Provisórios Vencidos
$(document).ready(function() {
    $('#cadastrosprovisorios').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Pré-cadastros Vencidos'
        }
        ]
    });
});

// Consulta Cadastros Provisórios Vencidos
$(document).ready(function() {
    $('#provisoriosvencidos').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Cadastros Provisórios Vencidos'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Cadastros Provisórios Vencidos'
        }
        ]
    });
});

// Consulta Profissionais Principal mais de 1 CRO'
$(document).ready(function() {
    $('#principalmaisum').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta – Ativo em mais de um CRO '
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta – Ativo em mais de um CRO '
        }
        ]
    });
});

// Consulta CPF/CNPJ em duplicidade
$(document).ready(function() {
    $('#cnpj_cpf_duplicidade').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta – CPF/CNPJ em duplicidade'
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta – CPF/CNPJ em duplicidade'
        }
        ]
    });
});

// Tabela Consulta Especialidae x CRO
$(document).ready(function() {
    $('#especialidadeCro').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Integrada de Profissionais'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta Integrada de Profissionais'
        }
        ]
    });
});

// Tabela Consulta Especialidade x CRO x Sexo
$(document).ready(function() {
    $('#especialidade_cro_sexo').DataTable({
        order: [[1, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Especialidade x CRO'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Especialidade x CRO'
        }
        ]
    });
});

// Tabela Consulta Especialidade x CRO x Sexo
$(document).ready(function() {
    $('#especialidade_municipio').DataTable({
        order: [[1, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Sexo x Especialidade x Municipio'
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Sexo x Especialidade x Municipio'
        }
        ]
    });
});

// Tabela Consulta Inscritos x CRO
$(document).ready(function() {
    $('#inscritos_cro').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 30,
        dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Inscritos x CRO',
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4 ]
            }
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Inscritos x CRO',
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4 ]
            }
        }
        ]
    });
});

// Tabela Consulta Sexo x CRO
$(document).ready(function() {
    $('#sexo_cro').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 30,
        dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Categoria x Sexo x CRO',
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Categoria x Sexo x CRO',
        }
        ]
    });
});

// Tabela Consulta Faixa Etária
$(document).ready(function() {
    $('#consulta_faixaetaria').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: CRO x Categoria x Faixa Etária x Sexo',
        },
        {
            extend: 'pdfHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: CRO x Categoria x Faixa Etária x Sexo',
        }
        ]
    });
});


// Tabela Consulta Faixa Etária
$(document).ready(function() {
    $('#secundario_ativo').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de profissionais com cadastastros scundários sem origem ativa',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de profissionais com cadastastros scundários sem origem ativa',
        }
        ]
    });
});

// Tabela Consulta Faixa Etária
$(document).ready(function() {
    $('#filial_matriz').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de filiais ativas sem a respectiva matriz',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de filiais ativas sem a respectiva matriz',
        }
        ]
    });
});

// RT em mais de uma empresa
$(document).ready(function() {
    $('#rtemmaisdeumaempresa').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de RT em mais de uma empresa',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de RT em mais de uma empresa',
        }
        ]
    });
});


// Tabela Consulta Faixa Etária
$(document).ready(function() {
    $('#empresasemrt').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de empresas ativas sem responsável técnico',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            messageTop: dataAtual + ' - Dados da consulta de empresas ativas sem responsável técnico',
        }
        ]
    });
});


// Tabela Consulta CRO CATERGORIA POPULAÇÃO SEXO
$(document).ready(function() {
    $('#cro-cat-populacao').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-4 btn-md'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: CRO x Categoria x População x Sexo',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: Categoria x Sexo x CRO',
        },
        {
            text: 'Gráficos',
            className: 'btn btn-primary btn-md mr-1 rounded',
            action: function() {
              myFunction();
            }
        },
        {
            text: 'Botão',
            action: function () {
                // Coloque o código HTML personalizado aqui
                alert('Botão personalizado clicado!');
            }
        },
        {
            text: 'PowerBI',
            className: 'btn btn-primary btn-md mr-1 rounded',
            action: function() {
              myFunction2();
            }
        },
        ]
    });
    function myFunction() {
        document.getElementById('tableResult').style.display = 'none';
        document.getElementById('graficResult').style.display = 'block';
        document.getElementById('powerBI').style.display = 'none';
    }
    function myFunction2() {
        document.getElementById('tableResult').style.display = 'none';
        document.getElementById('graficResult').style.display = 'none';
        document.getElementById('powerBI').style.display = 'block';
    }
    // Função para fazer o download do gráfico

});

function downloadChart() {
    var canvas = document.getElementById('myChart');
    var url = canvas.toDataURL('image/png');
    var link = document.createElement('a');
    link.href = url;
    link.download = 'chart.png';
    link.click();
}


// Tabela Consulta CRO CATERGORIA POPULAÇÃO SEXO
$(document).ready(function() {
    $('#fiscalizacaoseminc').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4 btn-md'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta de Fiscalizações sem Inscrições',
        },
        {
            extend: 'pdfHtml5',
            orientation: 'landscape',
            className: 'btn btn-primary btn-md mr-1 rounded',
            messageTop: dataAtual + ' - Dados da Consulta de Fiscalizações sem Inscrições',
        },
        {
            text: 'PowerBI',
            className: 'btn btn-primary btn-md mr-1 rounded',
            action: function() {
              myFunction();
            }
        },
        ]
    });
    function myFunction() {
        document.getElementById('tableResult').style.display = 'none';
        document.getElementById('powerBI').style.display = 'block';
    }
    // Função para fazer o download do gráfico

});

// Tabela Consulta CFO ID Emitidas
$(document).ready(function() {
    $('#cro-cat-cfoid').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 100,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: CFO ID Emitidas',
        },
        ]
    });
});

// Tabela Consulta CFO ID Consulta
$(document).ready(function() {
    $('#cro-cat-cfoid-consulta').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 100,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais: CFO ID Consulta',
        },
        ]
    });
});

// Tabela Empresas Isentas
$(document).ready(function() {
    $('#empresas-isentas').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Empresas Isentas',
        },
        ]
    });
});

// Tabela Profissionais sem data de colação
$(document).ready(function() {
    $('#profissionais-semdatacolacao').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais sem data de colação',
        },
        ]
    });
});

// Tabela Profissionais ativos sem e-mail
$(document).ready(function() {
    $('#profissionais-ativossememail').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais ativos sem e-mail',
        },
        ]
    });
});

// Tabela Profissionais ativos sem data de inscrição
$(document).ready(function() {
    $('#profissionais-ativossemdatainsc').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Profissionais sem data de inscrição',
        },
        ]
    });
});

// Tabela Usuários CRO
$(document).ready(function() {
    $('#usuariosCro').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de Usuários do CRO',
        },
        ]
    });
});

// Tabela CPF duplicado na mesma categoria
$(document).ready(function() {
    $('#cpfduplicadomesmacat').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de CPF duplicado na mesma categoria',
        },
        ]
    });
});

// Tabela de fiscalizações
$(document).ready(function() {
    $('#fiscalizacoes').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
        {
            extend: 'excelHtml5',
            messageTop: dataAtual + ' - Dados da Consulta de CPF duplicado na mesma categoria',
            customizeData: function(data) {
                for (var i = 0; i < data.body.length; i++) {
                    for (var j = 0; j < data.body[i].length; j++) {
                        // Verifica se o valor pode ser convertido em número
                        var parsedValue = parseFloat(data.body[i][j]);
                        if (!isNaN(parsedValue)) {
                            data.body[i][j] = parsedValue.toFixed(3);
                        }
                    }
                }
            }
        },
        ]
    });
});

// Tabela Evolução CFO ID
$(document).ready(function() {
    $('#evolucaoCfoId').DataTable({
        order: [],
        "iDisplayLength": 25,
        dom: "<'row'<'col-sm-4'B><'col-sm-4 text-center'l><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: []
    });
});

// Tabela Prescricao 2
$(document).ready(function() {
    $('#tabelaPrescricao2').DataTable({
        "iDisplayLength": 50,
        "order": [[5, "desc"]], // Ordena pela sexta coluna (índice 5) em ordem decrescente
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        "columnDefs": [
            {
                "targets": 5, // Índice da coluna "Data"
                "render": function(data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        // Converte a data de DD-MM-YYYY para YYYY-MM-DD para ordenação
                        var parts = data.split('-');
                        return parts[2] + '-' + parts[1] + '-' + parts[0]; // Converte para YYYY-MM-DD
                    }
                    return data; // Mantém o formato original para exibição
                }
            }
        ],
    });
});

// Tabela Prescricao 3
$(document).ready(function() {
    $('#tabelaPrescricao3').DataTable({
        "iDisplayLength": 50,
        "order": [[5, "desc"]], // Ordena pela sexta coluna (índice 5) em ordem decrescente
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        "columnDefs": [
            {
                "targets": 5, // Índice da coluna "Data"
                "render": function(data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        // Converte a data de DD-MM-YYYY HH:MM para YYYY-MM-DD HH:MM para ordenação
                        var dateTimeParts = data.split(' '); // Divide a data e o horário
                        var dateParts = dateTimeParts[0].split('-'); // Divide a data em DD, MM, YYYY
                        var time = dateTimeParts[1]; // Captura a parte do horário

                        // Retorna no formato YYYY-MM-DD HH:MM
                        return dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0] + ' ' + time;
                    }
                    return data; // Mantém o formato original para exibição
                }
            }
        ],
    });
});

// Tabela Prescricao 5
$(document).ready(function() {
    $.fn.dataTable.moment = function (format, locale) {
        var types = $.fn.dataTable.ext.type;

        // Adicionar detecção de tipo
        types.detect.unshift(function (d) {
            return moment(d, format, locale, true).isValid() ?
                'moment-' + format :
                null;
        });

        // Adicionar método de ordenação - use um inteiro para a ordenação
        types.order['moment-' + format + '-pre'] = function (d) {
            return moment(d, format, locale, true).unix();
        };
    };

    // Usar o formato de data correto 'DD-MM-YYYY HH:MM'
    $.fn.dataTable.moment('DD-MM-YYYY HH:mm');

    $('#tabelaPrescricao5').DataTable({
        order: [[6, 'desc']],  // Ordenar pela 7ª coluna (índice 6)
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-6'i><'col-sm-6'p>>"
    });
});

$(document).ready(function() {
    $('#tabelaEstatistica8').DataTable({
        order: [[1, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});

$(document).ready(function() {
    $('#consultaEstatistica10').DataTable({
        order: [[0, 'asc']],
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});

$(document).ready(function() {
    $('#consultaEstatistica13').DataTable({
        "iDisplayLength": 50,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
    });
});


// consultas gerais sem ordenação
$(document).ready(function() {
    $('#consultaGerais1').DataTable({
        "iDisplayLength": 50,
        "ordering": false,
        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
        }
    });
});