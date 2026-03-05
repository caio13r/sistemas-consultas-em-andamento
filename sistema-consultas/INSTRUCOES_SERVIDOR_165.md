# 🚀 Instruções - Servidor 192.168.161.165 (API Identity Porta 8082)

Este documento contém as instruções para configurar e iniciar a API de Identidade no servidor `192.168.161.165` na porta `8082`.

---

## 📋 O que precisa ser feito no servidor 192.168.161.165

### ⚠️ IMPORTANTE
A API de Identidade que roda na porta **8082** é um serviço **separado** do sistema de consultas. 
Você precisa verificar se há um container Docker ou um serviço específico rodando essa API.

---

## 1️⃣ Verificar o que está rodando no servidor

### Passo 1: Conectar no servidor
```bash
ssh usuario@192.168.161.165
# OU acesse via console/VNC conforme sua configuração
```

### Passo 2: Verificar containers Docker rodando
```bash
# Ver todos os containers (rodando e parados)
docker ps -a

# Ver apenas containers rodando
docker ps

# Ver containers usando a porta 8082
docker ps | grep 8082
```

### Passo 3: Verificar serviços systemd
```bash
# Listar serviços relacionados a identity/api
systemctl list-units --type=service | grep -i identity
systemctl list-units --type=service | grep -i api
systemctl list-units --type=service | grep 8082
```

### Passo 4: Verificar processos na porta 8082
```bash
# Ver o que está usando a porta 8082
sudo netstat -tulpn | grep 8082
# OU
sudo ss -tulpn | grep 8082
# OU
sudo lsof -i :8082
```

---

## 2️⃣ Cenários Possíveis

### 🔵 Cenário A: API roda em Container Docker

Se a API estiver em um container Docker, você precisa:

#### 2.1 Verificar se há docker-compose.yml no servidor
```bash
# Procurar arquivos docker-compose
find / -name "docker-compose.yml" -type f 2>/dev/null
find /home -name "docker-compose.yml" -type f 2>/dev/null
find /opt -name "docker-compose.yml" -type f 2>/dev/null
```

#### 2.2 Se encontrar docker-compose.yml, verificar configuração
```bash
# Navegar até o diretório
cd /caminho/do/docker-compose.yml

# Ver conteúdo
cat docker-compose.yml

# Verificar se há serviço na porta 8082
grep -A 10 "8082" docker-compose.yml
```

#### 2.3 Subir os containers
```bash
# Se estiver usando docker-compose
docker-compose up -d

# OU se for docker-compose v2
docker compose up -d

# Verificar logs
docker-compose logs -f
# OU
docker compose logs -f
```

#### 2.4 Verificar se o container subiu corretamente
```bash
# Ver containers rodando
docker ps

# Ver logs do container da API
docker logs nome-do-container-api-identity

# Verificar se a porta 8082 está exposta
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep 8082
```

---

### 🟢 Cenário B: API roda como serviço systemd

Se a API estiver configurada como serviço systemd:

#### 2.1 Verificar status do serviço
```bash
# Procurar serviços relacionados
systemctl list-units --all | grep -i identity
systemctl list-units --all | grep -i api

# Verificar status de um serviço específico (exemplo)
sudo systemctl status api-identity
sudo systemctl status identity-api
sudo systemctl status identity-service
```

#### 2.2 Iniciar o serviço
```bash
# Iniciar o serviço
sudo systemctl start nome-do-servico

# Habilitar para iniciar automaticamente
sudo systemctl enable nome-do-servico

# Verificar status
sudo systemctl status nome-do-servico

# Ver logs
sudo journalctl -u nome-do-servico -f
```

---

### 🟡 Cenário C: API roda diretamente (sem container/serviço)

Se a API rodar diretamente como processo:

#### 2.1 Procurar scripts de inicialização
```bash
# Procurar scripts de start/run
find / -name "*start*" -name "*identity*" -type f 2>/dev/null
find /home -name "*run*" -name "*api*" -type f 2>/dev/null
find /opt -name "*start*" -name "*8082*" -type f 2>/dev/null
```

#### 2.2 Procurar aplicação Node.js/Python/Java
```bash
# Se for Node.js
ps aux | grep node
which node
node --version

# Se for Python
ps aux | grep python
which python3
python3 --version

# Se for Java
ps aux | grep java
which java
java -version
```

#### 2.3 Executar manualmente (se necessário)
```bash
# Exemplo para Node.js
cd /caminho/da/aplicacao
npm install  # se necessário
npm start    # ou node app.js

# Exemplo para Python
cd /caminho/da/aplicacao
python3 app.py
# OU
gunicorn app:app --bind 0.0.0.0:8082

# Exemplo para Java
cd /caminho/da/aplicacao
java -jar api-identity.jar --server.port=8082
```

---

## 3️⃣ Verificar Espaço em Disco

Você mencionou que limpou e colocou mais espaço. Vamos verificar:

```bash
# Ver espaço em disco
df -h

# Ver espaço usado por containers Docker
docker system df

# Limpar containers/images não utilizados (se necessário)
docker system prune -a --volumes

# Verificar logs grandes
du -sh /var/log/*
du -sh /var/lib/docker/*
```

---

## 4️⃣ Checklist de Verificação

Execute este checklist após subir o serviço:

- [ ] Container/serviço está rodando
- [ ] Porta 8082 está aberta e escutando
- [ ] API responde a requisições HTTP
- [ ] Token da API está configurado corretamente
- [ ] Banco de dados está acessível (se aplicável)
- [ ] Logs não mostram erros críticos
- [ ] Espaço em disco suficiente

---

## 5️⃣ Comandos de Teste Rápido

Após subir o serviço, teste rapidamente:

```bash
# Teste 1: Verificar se a porta está aberta
curl -v http://192.168.161.165:8082

# Teste 2: Testar endpoint da API (substitua SEU_TOKEN)
curl "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=teste"

# Teste 3: Ver logs em tempo real
docker logs -f nome-do-container
# OU
sudo journalctl -u nome-do-servico -f
```

---

## 6️⃣ Troubleshooting

### ❌ Erro: "Port 8082 is already in use"
**Solução**: Algo já está usando a porta. Verifique:
```bash
sudo lsof -i :8082
sudo kill -9 PID_DO_PROCESSO  # se necessário
```

### ❌ Erro: "Cannot connect to Docker daemon"
**Solução**: Docker não está rodando
```bash
sudo systemctl start docker
sudo systemctl enable docker
```

### ❌ Erro: "No space left on device"
**Solução**: Limpar espaço
```bash
# Limpar logs do Docker
sudo journalctl --vacuum-time=3d

# Limpar containers/images não usados
docker system prune -a

# Verificar e limpar volumes grandes
du -sh /var/lib/docker/volumes/*
```

### ❌ Erro: "Permission denied"
**Solução**: Verificar permissões
```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER
# Fazer logout e login novamente
```

---

## 7️⃣ Informações Úteis

### Localizações comuns de configuração:
- Docker Compose: `/opt/api-identity/`, `/home/usuario/api-identity/`, `/var/www/api-identity/`
- Serviços systemd: `/etc/systemd/system/`
- Logs: `/var/log/`, `~/.pm2/logs/` (se usar PM2)
- Configurações: `.env`, `config.yml`, `application.properties`

### Comandos úteis:
```bash
# Ver todos os containers Docker
docker ps -a

# Ver logs de um container
docker logs nome-container

# Entrar no container
docker exec -it nome-container bash

# Reiniciar container
docker restart nome-container

# Parar container
docker stop nome-container

# Iniciar container
docker start nome-container
```

---

## 📝 Próximos Passos

1. ✅ Conectar no servidor 192.168.161.165
2. ✅ Identificar como a API está configurada (Docker/Systemd/Direto)
3. ✅ Subir o serviço/container
4. ✅ Verificar se a porta 8082 está respondendo
5. ✅ Testar a API com curl
6. ✅ Verificar logs para erros
7. ✅ Testar do sistema de consultas

---

## 🔗 Referências

- Documento de testes: `TESTE_API_IDENTITY_165.md`
- Arquivo corrigido: `src/services/consulta-identidade/consultaIdentidade-1.php`
