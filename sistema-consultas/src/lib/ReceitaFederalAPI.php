<?php

namespace Cfo\SisConsultas\lib;

use SoapClient;
use SoapFault;
use Exception;

/**
 * Classe para integração com a API SOAP da Receita Federal
 * Consulta dados de CPF através do serviço ConsultarCPFPDEC8789
 */
class ReceitaFederalAPI
{
    private $wsdl;
    private $certPath;
    private $certPassword;
    private $timeout;

    public function __construct()
    {
        // Carregar variáveis de ambiente do arquivo .env
        $this->loadEnv();

        // URL do WSDL da Receita Federal
        $this->wsdl = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx?WSDL';

        // Caminho do certificado - usar realpath para caminho absoluto
        // IMPORTANTE: Usar PFX direto (comprovado funcionando no teste)
        $configPath = realpath(__DIR__ . '/../config');
        $pfxPath = $configPath . '/CFOORGBR.pfx';

        $this->certPath = $pfxPath;

        // Senha do certificado (armazenar em variável de ambiente)
        // Nota: arquivo .pem pode ser gerado sem senha (nodes) ou com senha
        $this->certPassword = $_ENV['RFB_CERT_PASSWORD'] ?? '';

        // Timeout de 30 segundos
        $this->timeout = 30;
        
        // Verificar se OpenSSL está disponível
        if (!extension_loaded('openssl')) {
            error_log("AVISO: Extensão OpenSSL não está carregada");
        }
        
        // Verificar se SOAP está disponível
        if (!extension_loaded('soap')) {
            error_log("ERRO: Extensão SOAP não está carregada");
        }
    }

    /**
     * Consultar CPF na Receita Federal usando perfil CPF 4-WS
     * Método: ConsultarCPFPDEC8789_SC (Sistema Convenente)
     * Retorna TODOS os campos da API
     * 
     * @param string $cpf CPF a ser consultado (apenas números)
     * @param string $cpfUsuario CPF do usuário que está consultando
     * @return array Retorna array com TODOS os dados ou informações de erro
     */
    public function consultarCPF($cpf, $cpfUsuario)
    {
        $startTime = microtime(true);
        
        try {
            // Remove formatação do CPF
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $cpfUsuarioLimpo = preg_replace('/[^0-9]/', '', $cpfUsuario);
            
            // Validar CPF
            if (strlen($cpfLimpo) != 11) {
                throw new Exception("CPF inválido");
            }

            // Verificar certificado
            if (!file_exists($this->certPath)) {
                throw new Exception("Certificado não encontrado: {$this->certPath}");
            }
            
            if (empty($this->certPassword)) {
                throw new Exception("Senha do certificado não configurada no .env");
            }

            // COPIAR EXATAMENTE DO test-rfb-pfx.php (linhas 62-81)
            // Converter PFX para PEM
            $pfxContent = file_get_contents($this->certPath);
            $certs = [];

            if (!openssl_pkcs12_read($pfxContent, $certs, $this->certPassword)) {
                $opensslError = openssl_error_string();
                throw new Exception("Erro ao ler certificado PFX. Senha configurada: " . (empty($this->certPassword) ? 'VAZIA' : 'Sim (' . strlen($this->certPassword) . ' chars)') . " | OpenSSL: " . ($opensslError ?: 'Nenhum erro reportado'));
            }

            // Criar PEM temporário (IGUAL ao teste que funciona)
            $pemTemp = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
            $pemContent = $certs['cert'] . "\n" . $certs['pkey'] . "\n";
            file_put_contents($pemTemp, $pemContent);

            // WSDL local (cache) - OBRIGATÓRIO - usar realpath
            $configPath = realpath(__DIR__ . '/../config');
            $wsdlLocal = $configPath . '/rfb_wsdl_cache.xml';

            if (!file_exists($wsdlLocal)) {
                @unlink($pemTemp);
                throw new Exception("Cache WSDL não encontrado em: $wsdlLocal. Execute: http://localhost:8080/test-rfb-pfx.php");
            }

            // COPIAR EXATAMENTE DO test-rfb-pfx.php (linhas 152-173)
            // Contexto SSL (IGUAL ao teste)
            $context = stream_context_create([
                'ssl' => [
                    'local_cert' => $pemTemp,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ],
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'PHP-SOAP/' . PHP_VERSION
                ]
            ]);

            // Opções SoapClient (IGUAL ao teste)
            $options = [
                'trace' => 1,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'stream_context' => $context,
                'soap_version' => SOAP_1_1,
                'encoding' => 'UTF-8',
                'connection_timeout' => 30
            ];

            // Criar SoapClient com WSDL local
            $client = new SoapClient($wsdlLocal, $options);

            // Parâmetros da consulta usando perfil CPF 4-WS (Sistema Convenente)
            // Sistema Convenente: CFO (conforme especificação)
            $params = [
                'ListaDeCPF' => $cpfLimpo,
                'CPFUsuario' => $cpfUsuarioLimpo,
                'SistemaConvenente' => 'CFO'
            ];

            // Fazer a consulta usando perfil CPF 4-WS (ConsultarCPFPDEC8789_SC)
            $response = $client->ConsultarCPFPDEC8789_SC($params);

            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            @unlink($pemTemp);

            // Processar resposta do perfil CPF 4-WS (Sistema Convenente)
            if (isset($response->ConsultarCPFPDEC8789_SCResult->PessoaPerfilDEC8789)) {
                $pessoa = $response->ConsultarCPFPDEC8789_SCResult->PessoaPerfilDEC8789;

                // Verificar se há erro na resposta
                $erro = !empty($pessoa->Erro) ? (string)$pessoa->Erro : null;

                // Se houver erro na API da RFB, retornar como falha
                if ($erro) {
                    // Mensagem de erro específica
                    $mensagemErro = $erro;

                    // Detectar erro de IP não habilitado
                    if (stripos($erro, 'Endereço IP não habilitado') !== false ||
                        stripos($erro, 'ACS - Erro 04') !== false ||
                        stripos($erro, 'Acesso negado') !== false) {
                        $mensagemErro = "⚠️ ERRO DE ACESSO: O endereço IP do servidor não está habilitado na Receita Federal.\n\n";
                        $mensagemErro .= "Para resolver:\n";
                        $mensagemErro .= "1. Identifique o IP público do servidor (IP usado para acessar a RFB)\n";
                        $mensagemErro .= "2. Solicite liberação do IP no Portal InfoConv da Receita Federal\n";
                        $mensagemErro .= "3. Aguarde aprovação (pode levar alguns dias úteis)\n\n";
                        $mensagemErro .= "Erro original: " . $erro;
                    }

                    return [
                        'sucesso' => false,
                        'tempo_resposta_ms' => $tempoResposta,
                        'erro' => $mensagemErro,
                        'erro_rfb' => $erro,
                        'tipo' => 'CPF'
                    ];
                }

                // Verificar se CPF está vazio (resposta inválida)
                if (empty($pessoa->CPF)) {
                    return [
                        'sucesso' => false,
                        'tempo_resposta_ms' => $tempoResposta,
                        'erro' => "Resposta da RFB não contém dados do CPF. Possível erro de configuração ou CPF inválido.",
                        'tipo' => 'CPF'
                    ];
                }

                // Retornar TODOS os campos da API
                return [
                    'sucesso' => true,
                    'tempo_resposta_ms' => $tempoResposta,
                    
                    // Dados Pessoais
                    'cpf' => (string)($pessoa->CPF ?? ''),
                    'nome' => (string)($pessoa->Nome ?? ''),
                    'nome_mae' => (string)($pessoa->NomeMae ?? ''),
                    'data_nascimento' => (string)($pessoa->DataNascimento ?? ''),
                    'sexo' => (string)($pessoa->Sexo ?? ''),
                    'sexo_descricao' => $this->getSexo((string)($pessoa->Sexo ?? '')),
                    
                    // Situação Cadastral
                    'situacao_cadastral_codigo' => (string)($pessoa->SituacaoCadastral ?? ''),
                    'situacao_cadastral' => $this->getSituacaoCadastral((string)($pessoa->SituacaoCadastral ?? '')),
                    
                    // Residência
                    'residente_exterior' => (string)($pessoa->ResidenteExterior ?? ''),
                    'codigo_pais_exterior' => (string)($pessoa->CodigoPaisExterior ?? ''),
                    'nome_pais_exterior' => (string)($pessoa->NomePaisExterior ?? ''),
                    
                    // Nacionalidade
                    'estrangeiro' => (string)($pessoa->Estrangeiro ?? ''),
                    'cod_pais_nacionalidade' => (string)($pessoa->CodPaisNacionalidade ?? ''),
                    'nome_pais_nacionalidade' => (string)($pessoa->NomePaisNacionalidade ?? ''),
                    
                    // Naturalidade
                    'codigo_municipio_naturalidade' => (string)($pessoa->CodigoMunicipioNaturalidade ?? ''),
                    'nome_municipio_naturalidade' => (string)($pessoa->NomeMunicipioNaturalidade ?? ''),
                    'uf_municipio_naturalidade' => (string)($pessoa->UFMunicipioNaturalidade ?? ''),
                    
                    // Ocupação
                    'natureza_ocupacao' => (string)($pessoa->NaturezaOcupacao ?? ''),
                    'natureza_ocupacao_descricao' => $this->getNaturezaOcupacao((string)($pessoa->NaturezaOcupacao ?? '')),
                    'ocupacao_principal' => (string)($pessoa->OcupacaoPrincipal ?? ''),
                    'ocupacao_principal_descricao' => $this->getOcupacaoPrincipal((string)($pessoa->OcupacaoPrincipal ?? '')),
                    'exercicio_ocupacao' => (string)($pessoa->ExercicioOcupacao ?? ''),
                    'exercicio_ocupacao_descricao' => $this->getExercicioOcupacao((string)($pessoa->ExercicioOcupacao ?? '')),
                    
                    // Endereço
                    'tipo_logradouro' => (string)($pessoa->TipoLogradouro ?? ''),
                    'logradouro' => (string)($pessoa->Logradouro ?? ''),
                    'numero_logradouro' => (string)($pessoa->NumeroLogradouro ?? ''),
                    'complemento' => (string)($pessoa->Complemento ?? ''),
                    'bairro' => (string)($pessoa->Bairro ?? ''),
                    'cep' => (string)($pessoa->CEP ?? ''),
                    'uf' => (string)($pessoa->UF ?? ''),
                    'codigo_municipio' => (string)($pessoa->CodigoMunicipio ?? ''),
                    'municipio' => (string)($pessoa->Municipio ?? ''),
                    
                    // Contato
                    'ddd' => (string)($pessoa->DDD ?? ''),
                    'telefone' => (string)($pessoa->Telefone ?? ''),
                    
                    // Administrativo
                    'unidade_administrativa' => (string)($pessoa->UnidadeAdministrativa ?? ''),
                    'data_inscricao' => (string)($pessoa->DataInscricao ?? ''),
                    'data_atualizacao' => (string)($pessoa->DataAtualizacao ?? ''),
                    
                    // Óbito
                    'ano_obito' => (string)($pessoa->AnoObito ?? '0000'),
                    
                    // Erro RFB
                    'erro_rfb' => $erro,
                    
                    // XML Completo
                    'xml_completo' => $client->__getLastResponse()
                ];
            }

            throw new Exception("Resposta inválida da Receita Federal");

        } catch (SoapFault $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            if (isset($pemTemp) && file_exists($pemTemp)) {
                @unlink($pemTemp);
            }
            
            return [
                'sucesso' => false,
                'tempo_resposta_ms' => $tempoResposta,
                'erro' => 'Erro SOAP: ' . $e->getMessage()
            ];
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            if (isset($pemTemp) && file_exists($pemTemp)) {
                @unlink($pemTemp);
            }
            
            return [
                'sucesso' => false,
                'tempo_resposta_ms' => $tempoResposta,
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Consultar CNPJ na Receita Federal
     * Usa o perfil 7-WS do InfoConv
     * 
     * @param string $cnpj CNPJ a ser consultado (apenas números)
     * @param string $cpfUsuario CPF do usuário que está consultando
     * @return array Retorna array com todos os dados ou informações de erro
     */
    public function consultarCNPJ($cnpj, $cpfUsuario)
    {
        $startTime = microtime(true);
        
        try {
            // Remove formatação do CNPJ
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
            $cpfUsuarioLimpo = preg_replace('/[^0-9]/', '', $cpfUsuario);
            
            // Validar CNPJ (14 dígitos)
            if (strlen($cnpjLimpo) != 14) {
                throw new Exception("CNPJ inválido - deve ter 14 dígitos");
            }

            // Verificar certificado
            if (!file_exists($this->certPath)) {
                throw new Exception("Certificado não encontrado: {$this->certPath}");
            }
            
            if (empty($this->certPassword)) {
                throw new Exception("Senha do certificado não configurada no .env");
            }

            // Converter PFX para PEM (IGUAL ao CPF)
            $pfxContent = file_get_contents($this->certPath);
            $certs = [];
            
            if (!openssl_pkcs12_read($pfxContent, $certs, $this->certPassword)) {
                throw new Exception("Erro ao ler certificado PFX. Verifique a senha no .env");
            }

            // Criar PEM temporário
            $pemTemp = tempnam(sys_get_temp_dir(), 'cert_') . '.pem';
            $pemContent = $certs['cert'] . "\n" . $certs['pkey'] . "\n";
            file_put_contents($pemTemp, $pemContent);

            // WSDL CNPJ - criar cache específico para CNPJ
            $configPath = realpath(__DIR__ . '/../config');
            $wsdlUrl = 'https://acesso.infoconv.receita.fazenda.gov.br/ws/cnpj/consultarcnpj.asmx?WSDL';
            $wsdlLocal = $configPath . '/rfb_cnpj_wsdl_cache.xml';
            
            // Se não existe cache CNPJ, baixar
            if (!file_exists($wsdlLocal)) {
                $context = stream_context_create([
                    'ssl' => [
                        'local_cert' => $pemTemp,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ],
                    'http' => [
                        'timeout' => 30,
                        'user_agent' => 'PHP/' . PHP_VERSION
                    ]
                ]);
                
                $wsdlContent = @file_get_contents($wsdlUrl, false, $context);
                if ($wsdlContent) {
                    file_put_contents($wsdlLocal, $wsdlContent);
                } else {
                    @unlink($pemTemp);
                    throw new Exception("Não foi possível baixar WSDL do CNPJ. Execute: http://localhost:8080/test-rfb-pfx.php primeiro");
                }
            }

            // Contexto SSL
            $context = stream_context_create([
                'ssl' => [
                    'local_cert' => $pemTemp,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ],
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'PHP-SOAP/' . PHP_VERSION
                ]
            ]);

            // Opções SoapClient
            $options = [
                'trace' => 1,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'stream_context' => $context,
                'soap_version' => SOAP_1_1,
                'encoding' => 'UTF-8',
                'connection_timeout' => 30
            ];

            // Criar SoapClient com WSDL local
            $client = new SoapClient($wsdlLocal, $options);

            // Parâmetros da consulta usando perfil P7_SC (7-WS)
            // Sistema Convenente: CFO (conforme especificação)
            $params = [
                'CNPJ' => $cnpjLimpo,
                'CPFUsuario' => $cpfUsuarioLimpo,
                'SistemaConvenente' => 'CFO'
            ];

            // Fazer a consulta usando perfil P7_SC
            $response = $client->ConsultarCNPJP7_SC($params);

            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            @unlink($pemTemp);

            // Processar resposta do perfil P7_SC
            if (isset($response->ConsultarCNPJP7_SCResult->CNPJPerfil7)) {
                $pj = $response->ConsultarCNPJP7_SCResult->CNPJPerfil7;

                // Verificar se há erro na resposta
                $erro = !empty($pj->Erro) ? (string)$pj->Erro : null;

                // Se houver erro na API da RFB, retornar como falha
                if ($erro) {
                    // Mensagem de erro específica
                    $mensagemErro = $erro;

                    // Detectar erro de IP não habilitado
                    if (stripos($erro, 'Endereço IP não habilitado') !== false ||
                        stripos($erro, 'ACS - Erro 04') !== false ||
                        stripos($erro, 'Acesso negado') !== false) {
                        $mensagemErro = "⚠️ ERRO DE ACESSO: O endereço IP do servidor não está habilitado na Receita Federal.\n\n";
                        $mensagemErro .= "Para resolver:\n";
                        $mensagemErro .= "1. Identifique o IP público do servidor (IP usado para acessar a RFB)\n";
                        $mensagemErro .= "2. Solicite liberação do IP no Portal InfoConv da Receita Federal\n";
                        $mensagemErro .= "3. Aguarde aprovação (pode levar alguns dias úteis)\n\n";
                        $mensagemErro .= "Erro original: " . $erro;
                    }

                    return [
                        'sucesso' => false,
                        'tempo_resposta_ms' => $tempoResposta,
                        'erro' => $mensagemErro,
                        'erro_rfb' => $erro,
                        'tipo' => 'CNPJ'
                    ];
                }

                // Verificar se CNPJ está vazio (resposta inválida)
                if (empty($pj->CNPJ)) {
                    return [
                        'sucesso' => false,
                        'tempo_resposta_ms' => $tempoResposta,
                        'erro' => "Resposta da RFB não contém dados do CNPJ. Possível erro de configuração ou CNPJ inválido.",
                        'tipo' => 'CNPJ'
                    ];
                }

                // Mapear Quadro Societário do XML
                $qsa = [];
                if (isset($pj->Sociedade->SocioPerfil7)) {
                    $socios = $pj->Sociedade->SocioPerfil7;
                    // Se for apenas um sócio, converter para array
                    if (!is_array($socios)) {
                        $socios = [$socios];
                    }
                    foreach ($socios as $socio) {
                        $qsa[] = [
                            'tipo' => (string)($socio->Tipo ?? ''),
                            'nome' => (string)($socio->Nome ?? ''),
                            'documento' => (string)($socio->Numero ?? ''),
                            'qualificacao' => (string)($socio->Qualificacao ?? ''),
                            'qualificacao_descricao' => $this->getQualificacaoSocio((string)($socio->Qualificacao ?? '')),
                            'pais_origem' => (string)($socio->NomePaisOrigem ?? '')
                        ];
                    }
                }

                // Mapear CNAEs Secundários
                $cnaesSecundarios = [];
                if (isset($pj->CNAESecundario->string)) {
                    $cnaes = $pj->CNAESecundario->string;
                    if (!is_array($cnaes)) {
                        $cnaes = [$cnaes];
                    }
                    $cnaesSecundarios = array_map('strval', $cnaes);
                }

                // Retornar TODOS os dados da Pessoa Jurídica (Perfil P7_SC)
                return [
                    'sucesso' => true,
                    'tempo_resposta_ms' => $tempoResposta,
                    'tipo' => 'CNPJ',

                    // Dados básicos
                    'cnpj' => (string)($pj->CNPJ ?? ''),
                    'estabelecimento' => (string)($pj->Estabelecimento ?? ''),
                    'razao_social' => (string)($pj->NomeEmpresarial ?? ''),
                    'nome_fantasia' => (string)($pj->NomeFantasia ?? ''),

                    // Situação Cadastral
                    'situacao_cadastral_codigo' => (string)($pj->SituacaoCadastral ?? ''),
                    'situacao_cadastral' => $this->getSituacaoCadastralCNPJ((string)($pj->SituacaoCadastral ?? '')),
                    'data_situacao_cadastral' => (string)($pj->DataSituacaoCadastral ?? ''),
                    'motivo_situacao_cadastral_codigo' => (string)($pj->MotivoSituacao ?? ''),
                    'motivo_situacao_cadastral' => $this->getMotivoSituacaoCadastral((string)($pj->MotivoSituacao ?? '')),

                    // Endereço
                    'tipo_logradouro' => (string)($pj->TipoLogradouro ?? ''),
                    'logradouro' => (string)($pj->Logradouro ?? ''),
                    'numero_logradouro' => (string)($pj->NumeroLogradouro ?? ''),
                    'complemento' => (string)($pj->Complemento ?? ''),
                    'bairro' => (string)($pj->Bairro ?? ''),
                    'cep' => (string)($pj->CEP ?? ''),
                    'uf' => (string)($pj->UF ?? ''),
                    'codigo_municipio' => (string)($pj->CodigoMunicipio ?? ''),
                    'municipio' => (string)($pj->NomeMunicipio ?? ''),
                    'referencia' => (string)($pj->Referencia ?? ''),

                    // Contato
                    'ddd' => (string)($pj->DDD1 ?? ''),
                    'telefone' => (string)($pj->Telefone1 ?? ''),
                    'ddd2' => (string)($pj->DDD2 ?? ''),
                    'telefone2' => (string)($pj->Telefone2 ?? ''),
                    'email' => (string)($pj->Email ?? ''),

                    // Natureza Jurídica
                    'codigo_natureza_juridica' => (string)($pj->NaturezaJuridica ?? ''),
                    'natureza_juridica' => $this->getNaturezaJuridica((string)($pj->NaturezaJuridica ?? '')),

                    // Administrativo
                    'data_abertura' => (string)($pj->DataAbertura ?? ''),

                    // CNAE Principal
                    'cnae_fiscal' => (string)($pj->CNAEPrincipal ?? ''),
                    'descricao_cnae_fiscal' => '', // Perfil 7 não retorna descrição

                    // CNAEs Secundários
                    'cnaes_secundarios' => $cnaesSecundarios,

                    // Quadro de Sócios e Administradores (QSA)
                    'qsa' => $qsa,

                    // Responsável Legal
                    'cpf_responsavel' => (string)($pj->CPFResponsavel ?? ''),
                    'nome_responsavel' => (string)($pj->NomeResponsavel ?? ''),

                    // Capital Social (em centavos, dividir por 100)
                    'capital_social' => !empty($pj->CapitalSocial) ? ((float)$pj->CapitalSocial / 100) : 0,

                    // Porte da Empresa
                    'porte' => (string)($pj->Porte ?? ''),
                    'descricao_porte' => $this->getPorteEmpresa((string)($pj->Porte ?? '')),

                    // Opção pelo Simples Nacional
                    'opcao_simples' => (string)($pj->OpcaoSimples ?? ''),

                    // Opção pelo MEI
                    'opcao_mei' => (string)($pj->OpcaoSIMEI ?? ''),

                    // Situação Especial
                    'situacao_especial' => (string)($pj->SituacaoEspecial ?? ''),
                    'data_situacao_especial' => (string)($pj->DataSituacaoEspecial ?? ''),

                    // Exterior
                    'cidade_exterior' => (string)($pj->CidadeExterior ?? ''),
                    'codigo_pais' => (string)($pj->CodigoPais ?? ''),
                    'nome_pais' => (string)($pj->NomePais ?? ''),

                    // Erro RFB
                    'erro_rfb' => $erro,

                    // XML Completo para debug/auditoria
                    'xml_completo' => $client->__getLastResponse()
                ];
            }

            throw new Exception("Resposta inválida da Receita Federal para CNPJ");

        } catch (SoapFault $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            if (isset($pemTemp) && file_exists($pemTemp)) {
                @unlink($pemTemp);
            }
            
            return [
                'sucesso' => false,
                'tempo_resposta_ms' => $tempoResposta,
                'tipo' => 'CNPJ',
                'erro' => 'Erro SOAP: ' . $e->getMessage()
            ];
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $tempoResposta = round(($endTime - $startTime) * 1000);

            // Limpar PEM temporário
            if (isset($pemTemp) && file_exists($pemTemp)) {
                @unlink($pemTemp);
            }
            
            return [
                'sucesso' => false,
                'tempo_resposta_ms' => $tempoResposta,
                'tipo' => 'CNPJ',
                'erro' => $e->getMessage()
            ];
        }
    }

    /**
     * Traduz código de situação cadastral CNPJ
     */
    private function getSituacaoCadastralCNPJ($codigo)
    {
        $situacoes = [
            '01' => 'Nula',
            '02' => 'Ativa',
            '03' => 'Suspensa',
            '04' => 'Inapta',
            '08' => 'Baixada'
        ];
        return $situacoes[$codigo] ?? 'Desconhecida';
    }

    /**
     * Traduz código de motivo da situação cadastral
     */
    private function getMotivoSituacaoCadastral($codigo)
    {
        $motivos = [
            '01' => 'Encerramento/Extinção voluntária',
            '02' => 'Fusão',
            '03' => 'Cisão total',
            '04' => 'Incorporação',
            '05' => 'Óbito do titular',
            '06' => 'Baixada de ofício',
            '07' => 'Transferência de inscrição',
            '08' => 'Outros motivos'
        ];
        return $motivos[$codigo] ?? 'Desconhecido';
    }

    /**
     * Traduz código de porte da empresa
     */
    private function getPorteEmpresa($codigo)
    {
        $portes = [
            '00' => 'Não Informado',
            '01' => 'Micro Empresa',
            '03' => 'Empresa de Pequeno Porte',
            '05' => 'Demais'
        ];
        return $portes[$codigo] ?? $codigo;
    }

    /**
     * Traduz código de qualificação do sócio
     */
    private function getQualificacaoSocio($codigo)
    {
        $qualificacoes = [
            '05' => 'Administrador',
            '08' => 'Conselheiro de Administração',
            '10' => 'Diretor',
            '16' => 'Presidente',
            '17' => 'Procurador',
            '20' => 'Sociedade Consorciada',
            '21' => 'Sociedade Filiada',
            '22' => 'Sócio',
            '23' => 'Sócio Capitalista',
            '24' => 'Sócio Comanditado',
            '25' => 'Sócio Comanditário',
            '26' => 'Sócio de Indústria',
            '28' => 'Sócio-Gerente',
            '29' => 'Sócio Incapaz ou Relativamente Incapaz',
            '30' => 'Sócio Menor (Assistido/Representado)',
            '31' => 'Sócio Ostensivo',
            '37' => 'Sócio Pessoa Jurídica Domiciliado no Exterior',
            '38' => 'Sócio Pessoa Física Residente ou Domiciliado no Exterior',
            '47' => 'Sócio Pessoa Física Residente no Brasil',
            '48' => 'Sócio Pessoa Jurídica Domiciliado no Brasil',
            '49' => 'Sócio-Administrador',
            '52' => 'Sócio com Capital',
            '53' => 'Sócio sem Capital',
            '54' => 'Fundador',
            '55' => 'Sócio Comanditado Residente no Exterior',
            '56' => 'Sócio Comanditário Pessoa Física Residente no Exterior',
            '57' => 'Sócio Comanditário Pessoa Jurídica Domiciliado no Exterior',
            '58' => 'Sócio Comanditário Incapaz',
            '59' => 'Produtor Rural',
            '63' => 'Cotas em Tesouraria',
            '65' => 'Titular Pessoa Física Residente ou Domiciliado no Brasil',
            '66' => 'Titular Pessoa Física Residente ou Domiciliado no Exterior',
            '70' => 'Administrador Judicial',
            '71' => 'Liquidante',
            '72' => 'Interventor',
            '73' => 'Síndico'
        ];
        return $qualificacoes[$codigo] ?? "Código $codigo";
    }

    /**
     * Traduz código de natureza jurídica (principais)
     */
    private function getNaturezaJuridica($codigo)
    {
        $naturezas = [
            '2062' => 'Sociedade Empresária Limitada',
            '2011' => 'Empresa Individual de Responsabilidade Limitada (EIRELI)',
            '2135' => 'Sociedade Anônima Fechada',
            '2305' => 'Sociedade Anônima Aberta',
            '2240' => 'Sociedade Simples Limitada',
            '2232' => 'Sociedade Simples Pura',
            '1015' => 'Órgão Público Autônomo',
            '1023' => 'Autarquia Federal',
            '1031' => 'Autarquia Estadual ou do Distrito Federal',
            '1040' => 'Autarquia Municipal',
            '1074' => 'Fundação Pública',
            '1120' => 'Órgão Público do Poder Executivo Federal',
            '1163' => 'Órgão Público do Poder Legislativo Federal',
            '1198' => 'Órgão Público do Poder Judiciário Federal',
            '3034' => 'Serviço Social Autônomo',
            '3999' => 'Associação Privada',
            '4014' => 'Empresa Individual',
            '2143' => 'Empresário Individual'
        ];
        return $naturezas[$codigo] ?? "Natureza $codigo";
    }

    /**
     * Carrega variáveis de ambiente do arquivo .env
     */
    private function loadEnv()
    {
        $basePath = realpath(__DIR__ . '/..');
        $envFile = $basePath . '/.env';

        if (!file_exists($envFile)) {
            error_log("AVISO: Arquivo .env não encontrado em: {$envFile}");
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignora comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Processa linhas no formato KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Define a variável de ambiente
                if (!empty($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    /**
     * Traduz código de situação cadastral
     */
    private function getSituacaoCadastral($codigo)
    {
        $situacoes = [
            '0' => 'Regular',
            '2' => 'Suspensa',
            '3' => 'Cancelada',
            '4' => 'Nula',
            '5' => 'Pendente de Regularização',
            '8' => 'Titular Falecido',
            '9' => 'Cancelada por Multiplicidade'
        ];
        return $situacoes[$codigo] ?? 'Desconhecida';
    }

    /**
     * Traduz código de sexo
     */
    private function getSexo($codigo)
    {
        $sexos = [
            '1' => 'Masculino',
            '2' => 'Feminino',
            '0' => 'Não Informado'
        ];
        return $sexos[$codigo] ?? 'Não informado';
    }

    /**
     * Traduz código de natureza da ocupação
     * Se já vier descrição, retorna como está
     */
    private function getNaturezaOcupacao($codigo)
    {
        if (empty($codigo)) {
            return '';
        }
        // Se já contém texto descritivo (não é apenas código numérico), retornar como está
        if (strlen($codigo) > 10 || preg_match('/[a-zA-Z]/', $codigo)) {
            return $codigo;
        }
        // Se for código numérico, a API geralmente já retorna descrição, mas se não retornar, retornar código
        return $codigo;
    }

    /**
     * Traduz código de ocupação principal (CBO)
     * Se já vier descrição, retorna como está
     */
    private function getOcupacaoPrincipal($codigo)
    {
        if (empty($codigo)) {
            return '';
        }
        // Se já contém texto descritivo (não é apenas código numérico), retornar como está
        if (strlen($codigo) > 10 || preg_match('/[a-zA-Z]/', $codigo)) {
            return $codigo;
        }
        // Se for código numérico, a API geralmente já retorna descrição
        // Se não retornar, retornar código (idealmente deveria buscar descrição CBO)
        return $codigo;
    }

    /**
     * Traduz código de exercício da ocupação
     * Se já vier descrição, retorna como está
     */
    private function getExercicioOcupacao($codigo)
    {
        if (empty($codigo)) {
            return '';
        }
        // Se já contém texto descritivo (não é apenas código numérico), retornar como está
        if (strlen($codigo) > 10 || preg_match('/[a-zA-Z]/', $codigo)) {
            return $codigo;
        }
        // Se for código numérico, a API geralmente já retorna descrição
        return $codigo;
    }

    /**
     * Verifica se o certificado está válido
     */
    public function verificarCertificado()
    {
        if (!file_exists($this->certPath)) {
            return [
                'valido' => false,
                'mensagem' => 'Certificado não encontrado no servidor'
            ];
        }

        $certData = openssl_pkcs12_read(file_get_contents($this->certPath), $cert, $this->certPassword);
        
        if (!$certData) {
            return [
                'valido' => false,
                'mensagem' => 'Não foi possível ler o certificado'
            ];
        }

        $certInfo = openssl_x509_parse($cert['cert']);
        $dataExpiracao = $certInfo['validTo_time_t'];
        
        if (time() > $dataExpiracao) {
            return [
                'valido' => false,
                'mensagem' => 'Certificado expirado',
                'data_expiracao' => date('d/m/Y', $dataExpiracao)
            ];
        }

        $diasRestantes = floor(($dataExpiracao - time()) / 86400);

        return [
            'valido' => true,
            'mensagem' => 'Certificado válido',
            'data_expiracao' => date('d/m/Y', $dataExpiracao),
            'dias_restantes' => $diasRestantes,
            'titular' => $certInfo['subject']['CN'] ?? 'Não identificado'
        ];
    }
}

