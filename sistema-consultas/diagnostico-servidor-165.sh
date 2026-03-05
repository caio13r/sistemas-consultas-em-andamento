#!/bin/bash

# Script de Diagnóstico - Servidor 192.168.161.165
# API Identity Porta 8082

echo "=========================================="
echo "🔍 DIAGNÓSTICO SERVIDOR 192.168.161.165"
echo "=========================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Verificar Docker
echo "1️⃣ Verificando Docker..."
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓ Docker instalado${NC}"
    docker --version
    
    echo ""
    echo "Containers rodando:"
    docker ps
    
    echo ""
    echo "Todos os containers:"
    docker ps -a
    
    echo ""
    echo "Containers usando porta 8082:"
    docker ps --format "table {{.Names}}\t{{.Ports}}" | grep 8082 || echo -e "${YELLOW}Nenhum container encontrado na porta 8082${NC}"
else
    echo -e "${RED}✗ Docker não instalado${NC}"
fi

echo ""
echo "=========================================="

# 2. Verificar Docker Compose
echo "2️⃣ Verificando Docker Compose..."
if command -v docker-compose &> /dev/null; then
    echo -e "${GREEN}✓ Docker Compose instalado${NC}"
    docker-compose --version
elif docker compose version &> /dev/null; then
    echo -e "${GREEN}✓ Docker Compose (v2) instalado${NC}"
    docker compose version
else
    echo -e "${YELLOW}⚠ Docker Compose não encontrado${NC}"
fi

echo ""
echo "Procurando arquivos docker-compose.yml..."
find /home /opt /var/www -name "docker-compose.yml" -type f 2>/dev/null | head -5

echo ""
echo "=========================================="

# 3. Verificar serviços systemd
echo "3️⃣ Verificando serviços systemd..."
echo "Serviços relacionados a identity/api:"
systemctl list-units --type=service --all | grep -i identity || echo -e "${YELLOW}Nenhum serviço 'identity' encontrado${NC}"
systemctl list-units --type=service --all | grep -i api | head -5 || echo -e "${YELLOW}Nenhum serviço 'api' encontrado${NC}"

echo ""
echo "=========================================="

# 4. Verificar porta 8082
echo "4️⃣ Verificando porta 8082..."
echo "Processos usando a porta 8082:"
if command -v lsof &> /dev/null; then
    sudo lsof -i :8082 2>/dev/null || echo -e "${YELLOW}Nenhum processo encontrado na porta 8082${NC}"
elif command -v netstat &> /dev/null; then
    sudo netstat -tulpn | grep 8082 || echo -e "${YELLOW}Nenhum processo encontrado na porta 8082${NC}"
elif command -v ss &> /dev/null; then
    sudo ss -tulpn | grep 8082 || echo -e "${YELLOW}Nenhum processo encontrado na porta 8082${NC}"
else
    echo -e "${RED}✗ Ferramentas de rede não disponíveis${NC}"
fi

echo ""
echo "=========================================="

# 5. Verificar espaço em disco
echo "5️⃣ Verificando espaço em disco..."
df -h | grep -E "Filesystem|/$|/var|/home"

echo ""
if command -v docker &> /dev/null; then
    echo "Espaço usado por Docker:"
    docker system df
fi

echo ""
echo "=========================================="

# 6. Verificar aplicações Node.js/Python/Java
echo "6️⃣ Verificando aplicações..."
echo "Processos Node.js:"
ps aux | grep -i node | grep -v grep | head -3 || echo -e "${YELLOW}Nenhum processo Node.js encontrado${NC}"

echo ""
echo "Processos Python:"
ps aux | grep -i python | grep -v grep | head -3 || echo -e "${YELLOW}Nenhum processo Python encontrado${NC}"

echo ""
echo "Processos Java:"
ps aux | grep -i java | grep -v grep | head -3 || echo -e "${YELLOW}Nenhum processo Java encontrado${NC}"

echo ""
echo "=========================================="

# 7. Teste de conectividade
echo "7️⃣ Testando conectividade na porta 8082..."
if command -v curl &> /dev/null; then
    echo "Testando http://localhost:8082..."
    curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" --connect-timeout 5 http://localhost:8082 || echo -e "${RED}✗ Não foi possível conectar${NC}"
else
    echo -e "${YELLOW}⚠ curl não instalado${NC}"
fi

echo ""
echo "=========================================="

# 8. Resumo e recomendações
echo "8️⃣ RESUMO E RECOMENDAÇÕES"
echo ""
echo "Para subir a API na porta 8082, você pode precisar:"
echo ""
echo "Se usar Docker Compose:"
echo "  cd /caminho/do/projeto"
echo "  docker-compose up -d"
echo "  # OU"
echo "  docker compose up -d"
echo ""
echo "Se usar serviço systemd:"
echo "  sudo systemctl start nome-do-servico"
echo "  sudo systemctl enable nome-do-servico"
echo ""
echo "Se rodar diretamente:"
echo "  cd /caminho/da/aplicacao"
echo "  npm start  # Node.js"
echo "  # OU"
echo "  python3 app.py  # Python"
echo "  # OU"
echo "  java -jar app.jar  # Java"
echo ""
echo "=========================================="
echo "✅ Diagnóstico concluído!"
echo ""
