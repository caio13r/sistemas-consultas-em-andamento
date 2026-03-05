# 🧪 Teste de Conectividade - API Identity (192.168.161.165:8082)

Este documento contém comandos e testes para verificar a conectividade e funcionalidade da API de Identidade no servidor `192.168.161.165:8082`.

## 📋 Pré-requisitos

- Acesso ao servidor `192.168.161.165`
- Token da API configurado
- Ferramentas: `curl`, `ping`, `telnet` ou `nc` (netcat)

---

## 1️⃣ Testes Básicos de Conectividade

### Teste 1: Ping no Servidor
```bash
ping -c 4 192.168.161.165
```
**Resultado esperado**: Respostas do servidor (se não estiver bloqueando ICMP)

### Teste 2: Verificar Porta 8082
```bash
# Usando telnet
telnet 192.168.161.165 8082

# OU usando nc (netcat)
nc -zv 192.168.161.165 8082

# OU usando curl para verificar se a porta está aberta
curl -v --connect-timeout 5 http://192.168.161.165:8082
```
**Resultado esperado**: Conexão estabelecida ou resposta HTTP

### Teste 3: Verificar se o Serviço está Rodando
```bash
# No servidor 192.168.161.165, verificar processos na porta 8082
sudo netstat -tulpn | grep 8082
# OU
sudo ss -tulpn | grep 8082
# OU
sudo lsof -i :8082
```

---

## 2️⃣ Testes da API de Identidade

### Configuração
Substitua `SEU_TOKEN_AQUI` pelo token real da API.

### Teste 4: Consulta por Nome
```bash
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN_AQUI&name=HUMBERTO%20FRANCO%20BUENO" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -v \
  --connect-timeout 10 \
  --max-time 30
```

### Teste 5: Consulta por CPF
```bash
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=SEU_TOKEN_AQUI&cpf=00000000000" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -v \
  --connect-timeout 10 \
  --max-time 30
```

### Teste 6: Consulta por AR
```bash
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/ar?token=SEU_TOKEN_AQUI&ar=123456789" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -v \
  --connect-timeout 10 \
  --max-time 30
```

### Teste 7: Verificar Endpoint de Health/Status (se existir)
```bash
curl -X GET "http://192.168.161.165:8082/health" \
  -v \
  --connect-timeout 5

# OU
curl -X GET "http://192.168.161.165:8082/api/status" \
  -v \
  --connect-timeout 5

# OU
curl -X GET "http://192.168.161.165:8082/" \
  -v \
  --connect-timeout 5
```

---

## 3️⃣ Testes de Diagnóstico Detalhado

### Teste 8: Verificar Headers da Resposta
```bash
curl -I "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN_AQUI&name=teste" \
  --connect-timeout 10 \
  --max-time 30
```

### Teste 9: Teste com Verbose (Debug Completo)
```bash
curl -v "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN_AQUI&name=HUMBERTO%20FRANCO%20BUENO" \
  --connect-timeout 10 \
  --max-time 30 \
  2>&1 | tee curl_output.log
```

### Teste 10: Verificar DNS/Resolução
```bash
# Verificar se o IP resolve corretamente
nslookup 192.168.161.165
# OU
host 192.168.161.165
```

---

## 4️⃣ Testes de Firewall e Rede

### Teste 11: Verificar Rota até o Servidor
```bash
traceroute 192.168.161.165
# OU
tracepath 192.168.161.165
```

### Teste 12: Verificar se Firewall está Bloqueando
```bash
# No servidor 192.168.161.165, verificar regras de firewall
sudo iptables -L -n | grep 8082
# OU (se usar firewalld)
sudo firewall-cmd --list-all | grep 8082
# OU (se usar ufw)
sudo ufw status | grep 8082
```

---

## 5️⃣ Testes do Sistema de Consultas

### Teste 13: Teste via PHP (no servidor do sistema)
```bash
# Criar arquivo de teste temporário
cat > /tmp/test_api_identity.php << 'EOF'
<?php
$token = 'SEU_TOKEN_AQUI';
$apiUrl = 'http://192.168.161.165:8082/api/consulta/identidade/nome?token=' . urlencode($token) . '&name=' . urlencode('HUMBERTO FRANCO BUENO');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json'
    ],
    CURLOPT_USERAGENT => 'SistemaConsultas/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: " . ($curlError ?: 'Nenhum') . "\n";
echo "cURL Errno: $curlErrno\n";
echo "Response: " . substr($response, 0, 500) . "\n";
EOF

# Executar o teste
php /tmp/test_api_identity.php
```

---

## 6️⃣ Checklist de Verificação

Marque conforme verificar:

- [ ] Servidor 192.168.161.165 está acessível via ping
- [ ] Porta 8082 está aberta e aceitando conexões
- [ ] Serviço está rodando na porta 8082
- [ ] Firewall não está bloqueando a porta 8082
- [ ] API responde a requisições GET
- [ ] Token da API está correto e válido
- [ ] Endpoints `/api/consulta/identidade/*` estão funcionando
- [ ] Resposta JSON está sendo retornada corretamente
- [ ] Não há erros de timeout
- [ ] Não há erros de "Connection refused"

---

## 7️⃣ Possíveis Problemas e Soluções

### ❌ Erro: "Connection refused"
**Causa**: Servidor não está escutando na porta 8082 ou serviço não está rodando.

**Soluções**:
1. Verificar se o serviço está rodando: `sudo systemctl status nome-do-servico`
2. Iniciar o serviço: `sudo systemctl start nome-do-servico`
3. Verificar logs do serviço: `sudo journalctl -u nome-do-servico -f`

### ❌ Erro: "Connection timed out"
**Causa**: Firewall bloqueando ou servidor não acessível na rede.

**Soluções**:
1. Verificar regras de firewall
2. Verificar se o servidor está na mesma rede
3. Verificar roteamento de rede

### ❌ Erro: "Failed to resolve"
**Causa**: Problema de DNS ou IP incorreto.

**Soluções**:
1. Verificar se o IP está correto
2. Usar IP diretamente ao invés de hostname

### ❌ Erro: HTTP 401 (Unauthorized)
**Causa**: Token inválido ou expirado.

**Soluções**:
1. Verificar se o token está correto
2. Gerar novo token se necessário

### ❌ Erro: HTTP 500 (Internal Server Error)
**Causa**: Erro interno no servidor da API.

**Soluções**:
1. Verificar logs do servidor da API
2. Verificar se o banco de dados está acessível
3. Verificar recursos do servidor (memória, CPU)

---

## 8️⃣ Comandos Úteis para Debug

### Ver logs em tempo real (se aplicável)
```bash
# No servidor 192.168.161.165
sudo tail -f /var/log/api-identity.log
# OU
sudo journalctl -u api-identity -f
```

### Verificar uso de recursos
```bash
# No servidor 192.168.161.165
top
# OU
htop
```

### Verificar conexões ativas
```bash
# No servidor 192.168.161.165
sudo netstat -an | grep 8082
# OU
sudo ss -an | grep 8082
```

---

## 📝 Notas

- Substitua `SEU_TOKEN_AQUI` pelo token real em todos os comandos
- Os timeouts podem ser ajustados conforme necessário
- Salve os outputs dos testes para análise posterior
- Se a API usar HTTPS, altere `http://` para `https://` nos comandos

---

## 🔗 Referências

- Arquivo corrigido: `src/services/consulta-identidade/consultaIdentidade-1.php`
- Variável de ambiente: `API_IDENTITY_URL` no arquivo `.env`
- Token da API: `API_TOKEN` no arquivo `.env`
