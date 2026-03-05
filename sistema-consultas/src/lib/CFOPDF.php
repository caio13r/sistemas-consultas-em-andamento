<?php

namespace Cfo\SisConsultas\lib;

use TCPDF;

/**
 * Classe para geração de PDFs profissionais do CFO
 * Usa layout institucional com cabeçalho, rodapé e marca d'água
 */
class CFOPDF extends TCPDF
{
    private $consultaTipo;
    private $consultaData;
    private $consultaUsuario;

    public function __construct($tipo = 'CPF', $usuario = '', $orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format, true, 'UTF-8', false);

        $this->consultaTipo = $tipo;
        $this->consultaData = date('d/m/Y H:i:s');
        $this->consultaUsuario = $usuario;

        // Configurações do PDF
        $this->SetCreator('Sistema CFO');
        $this->SetAuthor('Conselho Federal de Odontologia');
        $this->SetTitle('Consulta RFB - ' . $tipo);
        $this->SetSubject('Relatório de Consulta na Receita Federal do Brasil');
        $this->SetKeywords('CFO, RFB, Receita Federal, ' . $tipo);

        // Margens: esquerda, topo, direita
        $this->SetMargins(15, 45, 15);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(TRUE, 25);

        // Fonte padrão
        $this->SetFont('helvetica', '', 10);
    }

    /**
     * Cabeçalho do PDF
     */
    public function Header()
    {
        // Logo do CFO (se existir)
        $logoPath = realpath(__DIR__ . '/../public/assets/img/logocfo.png');
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 30, 0, 'PNG');
        }

        // Título principal
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(141, 15, 18); // Vermelho CFO
        $this->SetXY(50, 12);
        $this->Cell(0, 8, 'CONSELHO FEDERAL DE ODONTOLOGIA', 0, 1, 'L');

        // Subtítulo
        $this->SetFont('helvetica', '', 11);
        $this->SetTextColor(80, 80, 80);
        $this->SetX(50);
        $this->Cell(0, 6, 'Consulta à Receita Federal do Brasil', 0, 1, 'L');

        // Linha separadora
        $this->SetDrawColor(141, 15, 18);
        $this->SetLineWidth(0.5);
        $this->Line(15, 32, 195, 32);

        // Informações do documento
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(15, 34);
        $this->Cell(90, 5, 'Tipo: Consulta ' . $this->consultaTipo, 0, 0, 'L');
        $this->Cell(90, 5, 'Data: ' . $this->consultaData, 0, 1, 'R');

        // Resetar cores
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Rodapé do PDF
     */
    public function Footer()
    {
        $this->SetY(-20);

        // Linha separadora
        $this->SetDrawColor(141, 15, 18);
        $this->SetLineWidth(0.3);
        $this->Line(15, $this->GetY(), 195, $this->GetY());

        // Informações do rodapé
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 100, 100);

        // Usuário e paginação
        $txt = 'Usuário: ' . $this->consultaUsuario . ' | Sistema de Consultas CFO';
        $this->Cell(0, 5, $txt, 0, 0, 'L');
        $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');

        // Aviso LGPD
        $this->Ln(4);
        $this->SetFont('helvetica', 'I', 6);
        $this->SetTextColor(150, 0, 0);
        $this->MultiCell(0, 3, 'DOCUMENTO CONFIDENCIAL - Dados protegidos pela LGPD (Lei 13.709/2018). Uso restrito e auditado. Distribuição não autorizada é proibida.', 0, 'C');

        // Resetar cores
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Adiciona seção com título
     */
    public function AddSection($titulo, $icone = '')
    {
        $this->Ln(3);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(141, 15, 18);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 8, ($icone ? $icone . ' ' : '') . $titulo, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    /**
     * Adiciona campo com label e valor
     */
    public function AddField($label, $valor, $width = 0, $bold = false)
    {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(80, 80, 80);
        $this->Cell($width, 6, $label . ':', 0, 0, 'L');

        $this->SetFont('helvetica', $bold ? 'B' : '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, $valor, 0, 1, 'L');
    }

    /**
     * Adiciona tabela de dados
     */
    public function AddTable($headers, $data, $widths)
    {
        // Cabeçalho da tabela
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(141, 15, 18);
        $this->SetTextColor(255, 255, 255);

        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->Ln();

        // Dados da tabela
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(0, 0, 0);
        $fill = false;

        foreach ($data as $row) {
            $this->SetFillColor(245, 245, 245);
            foreach ($row as $i => $cell) {
                $this->Cell($widths[$i], 6, $cell, 1, 0, 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
    }

    /**
     * Adiciona alerta/aviso
     */
    public function AddAlert($mensagem, $tipo = 'info')
    {
        $this->Ln(3);

        // Definir cores conforme tipo
        $cores = [
            'success' => [40, 167, 69],
            'info' => [23, 162, 184],
            'warning' => [255, 193, 7],
            'danger' => [220, 53, 69]
        ];

        $cor = $cores[$tipo] ?? $cores['info'];

        $this->SetFillColor($cor[0], $cor[1], $cor[2], 0.1);
        $this->SetDrawColor($cor[0], $cor[1], $cor[2]);
        $this->SetLineWidth(0.5);

        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor($cor[0], $cor[1], $cor[2]);

        $this->MultiCell(0, 5, $mensagem, 1, 'L', true);

        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
}
