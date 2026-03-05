#!/bin/bash

# Script para testar endpoints do servidor 165 e verificar se retornam data_criacao
# Uso: ./testar-endpoints-165.sh

echo "=========================================="
echo "🔍 TESTE DE ENDPOINTS - SERVIDOR 165"
echo "=========================================="
echo ""

# Carregar variáveis do .env se existir
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Configurações
API_BASE_URL="${API_IDENTITY_URL:-http://192.168.161.165:8082}"

# Aceitar token como parâmetro ou usar variável de ambiente
if [ -n "$1" ]; then
    API_TOKEN="$1"
elif [ -n "$API_TOKEN" ]; then
    # Já está definido
    :
else
    echo "❌ ERRO: API_TOKEN não configurado!"
    echo ""
    echo "Uso:"
    echo "  $0 [TOKEN]"
    echo ""
    echo "Ou configure a variável de ambiente:"
    echo "  export API_TOKEN=seu_token_aqui"
    echo ""
    echo "Ou adicione no arquivo .env:"
    echo "  API_TOKEN=seu_token_aqui"
    echo ""
    exit 1
fi

echo "📍 Servidor: $API_BASE_URL"
echo "🔑 Token: ${API_TOKEN:0:10}... (primeiros 10 caracteres)"
echo ""

# Teste 1: Consulta por CPF
echo "=========================================="
echo "1️⃣ TESTE: Consulta por CPF"
echo "=========================================="
echo ""

CPF_TESTE="06672679642"
echo "CPF de teste: $CPF_TESTE"
echo ""

RESPONSE_CPF=$(curl -s -X GET "$API_BASE_URL/api/consulta/identidade/cpf?token=$API_TOKEN&cpf=$CPF_TESTE" -w "\nHTTP_CODE:%{http_code}")

HTTP_CODE=$(echo "$RESPONSE_CPF" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE_CPF" | sed '/HTTP_CODE:/d')

echo "📊 Status HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Requisição bem-sucedida!"
    echo ""
    echo "📋 Estrutura da resposta:"
    echo "$BODY" | python3 << 'PYTHON_SCRIPT'
import sys, json
try:
    data = json.load(sys.stdin)
    
    # Identificar estrutura
    if 'identidade' in data:
        item = data['identidade']
        print("📦 Estrutura: objeto 'identidade'")
    elif 'list' in data and len(data['list']) > 0:
        item = data['list'][0]
        print("📦 Estrutura: array 'list' (primeiro item)")
    else:
        print("⚠️ Estrutura não reconhecida")
        print(json.dumps(data, indent=2, ensure_ascii=False))
        sys.exit(0)
    
    print("")
    print("📊 Campos disponíveis:")
    for key in sorted(item.keys()):
        value = item[key]
        if isinstance(value, str) and len(value) > 50:
            value_preview = value[:50] + "..."
        elif isinstance(value, list):
            value_preview = f"array[{len(value)}]"
        elif isinstance(value, dict):
            value_preview = "object{}"
        else:
            value_preview = str(value)
        print(f"  • {key:30} = {value_preview}")
    
    print("")
    print("🔍 Verificando data_criacao:")
    
    # Verificar na raiz
    if 'data_criacao' in item:
        print(f"  ✅ data_criacao encontrado na raiz: {item['data_criacao']}")
    else:
        print("  ❌ data_criacao NÃO encontrado na raiz")
    
    # Verificar dentro de foto (se for objeto)
    if 'foto' in item and isinstance(item['foto'], dict):
        if 'data_criacao' in item['foto']:
            print(f"  ✅ data_criacao encontrado dentro de 'foto': {item['foto']['data_criacao']}")
        else:
            print("  ❌ data_criacao NÃO encontrado dentro de 'foto'")
            print(f"     Campos dentro de 'foto': {list(item['foto'].keys())}")
    
    # Verificar outros campos relacionados a data
    print("")
    print("🔍 Campos relacionados a data encontrados:")
    campos_data = []
    for key in item.keys():
        key_lower = key.lower()
        if any(termo in key_lower for termo in ['data', 'criacao', 'created', 'date', 'upload', 'insercao']):
            campos_data.append((key, item[key]))
    
    if campos_data:
        for campo, valor in campos_data:
            print(f"  ✅ {campo}: {valor}")
    else:
        print("  ❌ Nenhum campo relacionado a data encontrado")
    
    print("")
    print("🖼️ Informações sobre foto:")
    if 'foto' in item:
        foto = item['foto']
        if isinstance(foto, str):
            print(f"  ✅ Campo 'foto' existe (string, tamanho: {len(foto)} chars)")
        elif isinstance(foto, dict):
            print(f"  ✅ Campo 'foto' existe (objeto)")
            print(f"     Campos: {list(foto.keys())}")
        else:
            print(f"  ⚠️ Campo 'foto' existe mas tipo desconhecido: {type(foto)}")
    else:
        print("  ❌ Campo 'foto' não encontrado")
        
except json.JSONDecodeError as e:
    print(f"❌ Erro ao decodificar JSON: {e}")
    print("Resposta bruta (primeiros 500 chars):")
    print(sys.stdin.read()[:500])
except Exception as e:
    print(f"❌ Erro: {e}")
    import traceback
    traceback.print_exc()
PYTHON_SCRIPT
else
    echo "❌ Erro na requisição!"
    echo ""
    echo "Resposta:"
    echo "$BODY"
fi

echo ""
echo ""

# Teste 2: Consulta por Nome
echo "=========================================="
echo "2️⃣ TESTE: Consulta por Nome"
echo "=========================================="
echo ""

NOME_TESTE="HUMBERTO FRANCO BUENO"
echo "Nome de teste: $NOME_TESTE"
echo ""

RESPONSE_NAME=$(curl -s -X GET "$API_BASE_URL/api/consulta/identidade/nome?token=$API_TOKEN&name=$(echo $NOME_TESTE | sed 's/ /%20/g')&page_number=1&page_amount=1" -w "\nHTTP_CODE:%{http_code}")

HTTP_CODE=$(echo "$RESPONSE_NAME" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE_NAME" | sed '/HTTP_CODE:/d')

echo "📊 Status HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Requisição bem-sucedida!"
    echo ""
    echo "$BODY" | python3 << 'PYTHON_SCRIPT'
import sys, json
try:
    data = json.load(sys.stdin)
    if 'list' in data and len(data['list']) > 0:
        item = data['list'][0]
        print("📦 Estrutura: array 'list' (primeiro item)")
        print("")
        print("🔍 Verificando data_criacao:")
        if 'data_criacao' in item:
            print(f"  ✅ data_criacao encontrado: {item['data_criacao']}")
        else:
            print("  ❌ data_criacao NÃO encontrado")
            print("")
            print("  Campos disponíveis:")
            for key in sorted(item.keys()):
                print(f"    - {key}")
    else:
        print("⚠️ Nenhum resultado encontrado ou estrutura diferente")
        print(json.dumps(data, indent=2, ensure_ascii=False))
except Exception as e:
    print(f"Erro: {e}")
PYTHON_SCRIPT
else
    echo "❌ Erro na requisição!"
    echo ""
    echo "Resposta:"
    echo "$BODY"
fi

echo ""
echo ""

# Teste 3: Verificar se precisa chamar servidor de foto
echo "=========================================="
echo "3️⃣ TESTE: Verificar servidor de foto (192.168.161.128)"
echo "=========================================="
echo ""

echo "Testando se o servidor de foto retorna data_criacao..."
echo ""

FOTO_SERVER_URL="http://192.168.161.128"
FOTO_SERVER_USUARIO="CFO"
FOTO_SERVER_CHAVE="f04fc70d6769cab53e799947ba7b11890883ba26d0a217880ecc5972a77f3f51"

PAYLOAD=$(cat <<EOF
{
  "cpf": "$CPF_TESTE",
  "credencial": {
    "usuario": "$FOTO_SERVER_USUARIO",
    "chave": "$FOTO_SERVER_CHAVE"
  }
}
EOF
)

RESPONSE_FOTO=$(curl -s -X POST "$FOTO_SERVER_URL/service/v1/consulta/solicitacao/cpf" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD" \
  -w "\nHTTP_CODE:%{http_code}")

HTTP_CODE=$(echo "$RESPONSE_FOTO" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE_FOTO" | sed '/HTTP_CODE:/d')

echo "📊 Status HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Servidor de foto acessível!"
    echo ""
    echo "$BODY" | python3 << 'PYTHON_SCRIPT'
import sys, json
try:
    data = json.load(sys.stdin)
    if 'resultado' in data and len(data['resultado']) > 0:
        foto = data['resultado'][-1]  # Última foto (mais recente)
        print("📦 Estrutura: array 'resultado' (último item)")
        print("")
        print("🔍 Verificando data_criacao:")
        if 'data_criacao' in foto:
            print(f"  ✅ data_criacao encontrado: {foto['data_criacao']}")
        else:
            print("  ❌ data_criacao NÃO encontrado")
            print("")
            print("  Campos disponíveis:")
            for key in sorted(foto.keys()):
                print(f"    - {key}")
    else:
        print("⚠️ Nenhum resultado encontrado")
except Exception as e:
    print(f"Erro: {e}")
PYTHON_SCRIPT
else
    echo "❌ Servidor de foto não acessível ou erro na requisição"
    echo ""
    echo "Resposta:"
    echo "$BODY"
fi

echo ""
echo "=========================================="
echo "✅ TESTES CONCLUÍDOS"
echo "=========================================="
echo ""
echo "📝 Resumo:"
echo "1. Verifique se o servidor 165 retorna data_criacao"
echo "2. Se não retornar, precisamos fazer chamada adicional ao servidor de foto"
echo "3. O servidor de foto (128) retorna data_criacao conforme documentado"
echo ""
