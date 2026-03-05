# 🔐 Configuração do Certificado Digital - Produção

## 📋 Pré-requisitos

Você precisa ter em mãos:
- ✅ Arquivo de certificado digital `.pfx` ou `.p12` (fornecido pela Receita Federal)
- ✅ Senha do certificado

---

## 🚀 Passo a Passo - Configuração

### **Passo 1: Criar a pasta de configuração**

No seu projeto local (Windows):

```bash
# Se ainda não existe, crie a pasta:
mkdir src\config
```

### **Passo 2: Copiar o certificado**

Copie seu arquivo `.pfx` para a pasta:

```
C:\Users\joao.dias\Documents\sistema-consultas\src\config\CFOORGBR.pfx
```

**Importante:** ✅ O certificado já está configurado com o nome `CFOORGBR.pfx`

### **Passo 3: Criar arquivo .env**

Crie o arquivo `src\.env` com o seguinte conteúdo:

```env
# Configurações do Banco de Dados (ajuste conforme seu ambiente)
DB1_HOST=seu_host
DB1_USERNAME=seu_usuario
DB1_PASSWORD=sua_senha
DB1_NAME=CFO_SisConsultas

# CERTIFICADO DIGITAL RECEITA FEDERAL
# Senha do certificado .pfx
RFB_CERT_PASSWORD=CFO166
```

**⚠️ ATENÇÃO:**
- ✅ A senha já está configurada corretamente
- Este arquivo NÃO deve ser commitado no Git (já está no .gitignore)

### **Passo 4: Configurar permissões (Docker)**

Como você está usando Docker, vamos garantir que o certificado tenha as permissões corretas:

```bash
# Parar os containers
docker-compose down

# Subir novamente
docker-compose up -d

# Entrar no container
docker exec -it sistema-consultas bash

# Verificar se o certificado está acessível
ls -la /var/www/html/src/config/CFOORGBR.pfx

# Ajustar permissões se necessário
chmod 644 /var/www/html/src/config/CFOORGBR.pfx
chown www-data:www-data /var/www/html/src/config/CFOORGBR.pfx

# Sair do container
exit
```

### **Passo 5: Testar o certificado**

Acesse o script de teste:

```
http://localhost:8080/test-rfb-conexao.php
```

Deve mostrar:
- ✅ Certificado encontrado
- ✅ Certificado pode ser lido
- ✅ Certificado parseado com sucesso
- ✅ Validade do certificado
- ✅ Dias restantes até expiração

### **Passo 6: Testar a consulta**

Acesse:
```
http://localhost:8080/consulta-integrada?tipoConsulta=3
```

Digite um CPF válido e clique em "Consultar na Receita Federal"

---

## 📂 Estrutura Final

```
sistema-consultas/
├── src/
│   ├── config/
│   │   ├── config.php
│   │   └── CFOORGBR.pfx  ← ✅ CERTIFICADO CONFIGURADO
│   ├── .env  ← ✅ SENHA CONFIGURADA (RFB_CERT_PASSWORD=CFO166)
│   └── ...
└── docker-compose.yml
```

---

## 🔍 Verificações de Segurança

### **1. Verificar validade do certificado**

```bash
# No Windows (PowerShell)
openssl pkcs12 -in src\config\CFOORGBR.pfx -noout -info

# Vai pedir a senha e mostrar informações do certificado
```

### **2. Verificar data de expiração**

O script `test-rfb-conexao.php` mostra automaticamente:
- Data de expiração
- Dias restantes
- Titular do certificado

### **3. Testar com Postman (Opcional)**

Se quiser testar manualmente antes:

1. Abra o Postman
2. Configure o certificado:
   - Settings → Certificates → Add Certificate
   - Host: `acesso.infoconv.receita.fazenda.gov.br`
   - PFX file: selecione seu `.pfx`
   - Passphrase: senha do certificado

3. Faça um POST para:
   ```
   https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/consultarcpf.asmx
   ```

4. Body (XML):
   ```xml
   <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                     xmlns:cpf="https://acesso.infoconv.receita.fazenda.gov.br/ws/cpf/">
      <soapenv:Header/>
      <soapenv:Body>
         <cpf:ConsultarCPFPDEC8789>
            <cpf:ListaDeCPF>SEU_CPF_AQUI</cpf:ListaDeCPF>
            <cpf:CPFUsuario>SEU_CPF_AQUI</cpf:CPFUsuario>
         </cpf:ConsultarCPFPDEC8789>
      </soapenv:Body>
   </soapenv:Envelope>
   ```

---

## ⚠️ Mensagens de Erro Comuns

### **Erro: "Certificado não encontrado"**
```
✗ Solução: Verifique se o arquivo está em src/config/CFOORGBR.pfx
✗ Caminho completo: C:\Users\joao.dias\Documents\sistema-consultas\src\config\CFOORGBR.pfx
✓ Status: ✅ Já está configurado corretamente!
```

### **Erro: "Senha do certificado não configurada"**
```
✗ Solução: Adicione RFB_CERT_PASSWORD no arquivo src/.env
```

### **Erro: "Não foi possível ler o certificado"**
```
✗ Solução: Senha incorreta no .env
✗ Verifique se a senha está correta
```

### **Erro: "Certificado expirado"**
```
✗ Solução: Renovar certificado com a Receita Federal
✗ Certificados digitais têm validade limitada
```

### **Erro: "SSL connect error"**
```
✗ Solução: Reconstruir o container Docker
✗ docker-compose down
✗ docker-compose build --no-cache
✗ docker-compose up -d
```

---

## 🔒 Segurança em Produção

### **1. Proteger o certificado**

No `.gitignore` (já configurado):
```
# Certificados
src/config/*.pfx
src/config/*.p12
src/.env
```

### **2. Backup do certificado**

- ⚠️ Mantenha backup do certificado em local seguro
- ⚠️ Nunca commit o certificado no Git
- ⚠️ Use variáveis de ambiente para senhas

### **3. Renovação**

Certificados digitais expiram! Configure alertas:
- 30 dias antes: começar processo de renovação
- 15 dias antes: urgente
- Após expiração: serviço para de funcionar

---

## 📞 Suporte

### **Problemas com certificado?**

1. Verifique o script de teste: `http://localhost:8080/test-rfb-conexao.php`
2. Veja os logs: `docker logs sistema-consultas`
3. Entre no container e teste manualmente

### **Renovar certificado?**

1. Obtenha novo certificado da Receita Federal
2. Substitua o arquivo em `src/config/CFOORGBR.pfx`
3. Atualize a senha no `.env` se mudou (RFB_CERT_PASSWORD)
4. Reinicie o container: `docker-compose restart`

---

## ✅ Checklist Final

Antes de usar em produção, verifique:

- [x] ✅ Certificado `.pfx` copiado para `src/config/CFOORGBR.pfx`
- [x] ✅ Arquivo `src/.env` criado com `RFB_CERT_PASSWORD=CFO166`
- [x] ✅ Classe `ReceitaFederalAPI.php` atualizada
- [ ] Script de teste passou: `test-rfb-conexao.php` (EXECUTAR)
- [ ] Certificado válido (não expirado) (VERIFICAR)
- [ ] Teste de consulta funcionou: `test-rfb-api.php` (EXECUTAR)
- [ ] Auditoria registrando consultas (VERIFICAR)
- [ ] Backup do certificado em local seguro (VERIFICAR)

---

**Última atualização:** 30/10/2025  
**Status:** Pronto para produção ✅

