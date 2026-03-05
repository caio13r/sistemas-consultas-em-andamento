# 🔍 Verificação de Endpoints - Servidor 192.168.161.165:8082

Este documento contém instruções para verificar quais endpoints estão disponíveis no servidor 192.168.161.165:8082 e se eles retornam o campo `data_criacao`.

---

## 📋 O que verificar

Precisamos confirmar se a API de identidade no servidor 165 retorna o campo `data_criacao` da foto nas respostas dos endpoints de consulta.

---

## 1️⃣ Verificar Endpoints Disponíveis

### Conecte no servidor 165:
```bash
ssh usuario@192.168.161.165
```

### Verificar documentação da API (se houver):
```bash
# Procurar documentação
find /opt /home /var/www -name "*api*" -name "*doc*" -type f 2>/dev/null
find /opt /home /var/www -name "*README*" -name "*API*" -type f 2>/dev/null
find /opt /home /var/www -name "*.md" -type f 2>/dev/null | grep -i api
```

### Verificar código da API (se tiver acesso):
```bash
# Procurar arquivos da API
find /opt /home /var/www -name "*Service.php" -type f 2>/dev/null
find /opt /home /var/www -name "*api*" -name "*.php" -type f 2>/dev/null | head -20
find /opt /home /var/www -name "*identity*" -name "*.php" -type f 2>/dev/null
```

---

## 2️⃣ Testar Endpoints da API

### Teste 1: Consulta por Nome
```bash
# Substitua SEU_TOKEN pelo token real
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/nome?token=SEU_TOKEN&name=HUMBERTO%20FRANCO%20BUENO&page_number=1&page_amount=1" \
  -H "Accept: application/json" \
  -v | python3 -m json.tool
```

**O que verificar:**
- ✅ A resposta contém o campo `data_criacao`?
- ✅ A resposta contém o campo `foto`?
- ✅ Qual é a estrutura completa da resposta?

### Teste 2: Consulta por CPF
```bash
# Substitua SEU_TOKEN e o CPF
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=SEU_TOKEN&cpf=06672679642" \
  -H "Accept: application/json" \
  -v | python3 -m json.tool
```

**O que verificar:**
- ✅ A resposta contém `data_criacao`?
- ✅ Onde está localizado o campo (no objeto principal ou dentro de `foto`)?

### Teste 3: Consulta por AR
```bash
# Substitua SEU_TOKEN e o AR
curl -X GET "http://192.168.161.165:8082/api/consulta/identidade/ar?token=SEU_TOKEN&ar=YA304590779BR" \
  -H "Accept: application/json" \
  -v | python3 -m json.tool
```

---

## 3️⃣ Analisar Estrutura da Resposta

### Script para extrair campos disponíveis:
```bash
#!/bin/bash

TOKEN="SEU_TOKEN_AQUI"
CPF="06672679642"  # CPF de exemplo

echo "=== Testando endpoint por CPF ==="
RESPONSE=$(curl -s -X GET "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=$TOKEN&cpf=$CPF")

echo ""
echo "=== Campos disponíveis na resposta ==="
echo "$RESPONSE" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    if 'identidade' in data:
        item = data['identidade']
        print('Campos disponíveis:')
        for key in item.keys():
            print(f'  - {key}')
        print('')
        print('Estrutura completa:')
        print(json.dumps(item, indent=2, ensure_ascii=False))
    elif 'list' in data:
        if len(data['list']) > 0:
            item = data['list'][0]
            print('Campos disponíveis:')
            for key in item.keys():
                print(f'  - {key}')
            print('')
            print('Estrutura completa (primeiro item):')
            print(json.dumps(item, indent=2, ensure_ascii=False))
except Exception as e:
    print(f'Erro: {e}')
    print('Resposta bruta:')
    print(sys.stdin.read())
"

echo ""
echo "=== Verificando campo data_criacao ==="
echo "$RESPONSE" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    item = data.get('identidade') or (data.get('list', [{}])[0] if data.get('list') else {})
    
    # Verificar diferentes possibilidades
    campos_data = []
    for key in item.keys():
        if 'data' in key.lower() or 'criacao' in key.lower() or 'created' in key.lower():
            campos_data.append(f'{key}: {item[key]}')
    
    if campos_data:
        print('Campos relacionados a data encontrados:')
        for campo in campos_data:
            print(f'  ✅ {campo}')
    else:
        print('❌ Nenhum campo relacionado a data encontrado')
        print('')
        print('Todos os campos disponíveis:')
        for key in item.keys():
            print(f'  - {key}')
except Exception as e:
    print(f'Erro ao processar: {e}')
"
```

---

## 4️⃣ Verificar Código da API (se tiver acesso)

### Se a API estiver em um container Docker:
```bash
# Listar containers
docker ps

# Entrar no container da API
docker exec -it nome-do-container bash

# Procurar código
find /app /var/www /opt -name "*.php" -type f | grep -i "consulta\|identity" | head -10
```

### Se a API estiver como serviço:
```bash
# Verificar onde está instalada
systemctl status api-identity
# OU
systemctl status nome-do-servico

# Verificar arquivos de configuração
cat /etc/systemd/system/api-identity.service | grep -i "execstart\|workingdirectory"
```

### Procurar arquivo Service.php mencionado:
```bash
# Procurar Service.php
find / -name "Service.php" -type f 2>/dev/null | grep -v node_modules | grep -v vendor

# Verificar linha 40 mencionada (ordenação por data_criacao)
grep -n "data_criacao" /caminho/para/Service.php
```

---

## 5️⃣ Verificar Logs da API

### Ver logs em tempo real:
```bash
# Se usar Docker
docker logs -f nome-container-api | grep -i "data_criacao\|consulta"

# Se usar systemd
sudo journalctl -u api-identity -f | grep -i "data_criacao\|consulta"

# Se usar arquivo de log
tail -f /var/log/api-identity/access.log | grep -i "consulta"
tail -f /var/log/api-identity/error.log
```

---

## 6️⃣ Teste Completo com Análise Detalhada

### Script completo de análise:
```bash
#!/bin/bash

TOKEN="SEU_TOKEN_AQUI"
BASE_URL="http://192.168.161.165:8082"

echo "=========================================="
echo "🔍 ANÁLISE COMPLETA DA API - SERVIDOR 165"
echo "=========================================="
echo ""

# Teste 1: Por CPF
echo "1️⃣ Testando endpoint por CPF..."
CPF="06672679642"
RESPONSE_CPF=$(curl -s -X GET "$BASE_URL/api/consulta/identidade/cpf?token=$TOKEN&cpf=$CPF")

echo "$RESPONSE_CPF" | python3 << 'PYTHON_SCRIPT'
import sys, json
try:
    data = json.load(sys.stdin)
    print("✅ Resposta recebida")
    
    # Identificar estrutura
    if 'identidade' in data:
        item = data['identidade']
        print(f"📋 Estrutura: objeto 'identidade'")
    elif 'list' in data and len(data['list']) > 0:
        item = data['list'][0]
        print(f"📋 Estrutura: array 'list'")
    else:
        print("❌ Estrutura não reconhecida")
        print(json.dumps(data, indent=2))
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
    print("🔍 Buscando campos relacionados a data de criação:")
    campos_data = []
    for key in item.keys():
        key_lower = key.lower()
        if any(termo in key_lower for termo in ['data', 'criacao', 'created', 'date', 'upload']):
            campos_data.append((key, item[key]))
    
    if campos_data:
        print("✅ Campos encontrados:")
        for campo, valor in campos_data:
            print(f"  ✅ {campo}: {valor}")
    else:
        print("❌ Nenhum campo relacionado a data encontrado")
    
    print("")
    print("🖼️ Informações sobre foto:")
    if 'foto' in item:
        foto = item['foto']
        if isinstance(foto, str):
            print(f"  ✅ Campo 'foto' existe (string, tamanho: {len(foto)} chars)")
        elif isinstance(foto, dict):
            print(f"  ✅ Campo 'foto' existe (objeto)")
            print(f"     Campos dentro de 'foto': {list(foto.keys())}")
            if 'data_criacao' in foto:
                print(f"     ✅ data_criacao encontrado em foto: {foto['data_criacao']}")
        else:
            print(f"  ⚠️ Campo 'foto' existe mas tipo desconhecido: {type(foto)}")
    else:
        print("  ❌ Campo 'foto' não encontrado")
        
except json.JSONDecodeError as e:
    print(f"❌ Erro ao decodificar JSON: {e}")
    print("Resposta bruta:")
    print(sys.stdin.read()[:500])
except Exception as e:
    print(f"❌ Erro: {e}")
    import traceback
    traceback.print_exc()
PYTHON_SCRIPT

echo ""
echo "=========================================="

# Teste 2: Por Nome
echo ""
echo "2️⃣ Testando endpoint por Nome..."
RESPONSE_NAME=$(curl -s -X GET "$BASE_URL/api/consulta/identidade/nome?token=$TOKEN&name=HUMBERTO%20FRANCO%20BUENO&page_number=1&page_amount=1")

echo "$RESPONSE_NAME" | python3 << 'PYTHON_SCRIPT'
import sys, json
try:
    data = json.load(sys.stdin)
    if 'list' in data and len(data['list']) > 0:
        item = data['list'][0]
        print("✅ Resposta recebida (primeiro item da lista)")
        print("")
        print("🔍 Verificando data_criacao:")
        if 'data_criacao' in item:
            print(f"  ✅ data_criacao encontrado: {item['data_criacao']}")
        else:
            print("  ❌ data_criacao não encontrado no item")
            print("")
            print("  Campos disponíveis:")
            for key in sorted(item.keys()):
                print(f"    - {key}")
except Exception as e:
    print(f"Erro: {e}")
PYTHON_SCRIPT

echo ""
echo "=========================================="
echo "✅ Análise concluída!"
echo ""
echo "📝 Próximos passos:"
echo "1. Verifique se algum campo relacionado a data foi encontrado"
echo "2. Se não encontrou, verifique o código da API no servidor"
echo "3. Informe qual campo contém a data de criação (se existir)"
```

---

## 7️⃣ Checklist de Verificação

Marque conforme verificar:

- [ ] Servidor 192.168.161.165:8082 está acessível
- [ ] Endpoint `/api/consulta/identidade/cpf` funciona
- [ ] Endpoint `/api/consulta/identidade/nome` funciona
- [ ] Endpoint `/api/consulta/identidade/ar` funciona
- [ ] Resposta JSON contém campo `data_criacao` (direto no item)
- [ ] Resposta JSON contém campo `data_criacao` (dentro de `foto`)
- [ ] Resposta JSON contém campo com nome diferente mas relacionado a data
- [ ] Código da API foi verificado (Service.php linha 40)
- [ ] Logs da API foram verificados

---

## 8️⃣ Informações para Reportar

Após verificar, informe:

1. **Estrutura da resposta:**
   - A resposta vem em `identidade` ou `list`?
   - Quantos campos tem no objeto retornado?

2. **Campo de data:**
   - Existe algum campo relacionado a data?
   - Qual é o nome exato do campo?
   - Onde está localizado (raiz do objeto ou dentro de `foto`)?

3. **Exemplo de resposta:**
   - Cole um exemplo completo da resposta JSON (sem a foto em base64, apenas a estrutura)

4. **Código da API:**
   - Se tiver acesso, informe o que está na linha 40 do Service.php
   - Qual é a estrutura completa do objeto retornado?

---

## 9️⃣ Comandos Rápidos

### Ver todos os campos de uma resposta:
```bash
TOKEN="SEU_TOKEN"
curl -s "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=$TOKEN&cpf=06672679642" | \
  python3 -c "import sys,json; d=json.load(sys.stdin); item=d.get('identidade') or d.get('list',[{}])[0]; print('\n'.join(sorted(item.keys())))"
```

### Ver apenas campos relacionados a data:
```bash
TOKEN="SEU_TOKEN"
curl -s "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=$TOKEN&cpf=06672679642" | \
  python3 -c "import sys,json; d=json.load(sys.stdin); item=d.get('identidade') or d.get('list',[{}])[0]; [print(f'{k}: {v}') for k,v in item.items() if 'data' in k.lower() or 'criacao' in k.lower() or 'created' in k.lower()]"
```

### Ver estrutura completa (sem foto):
```bash
TOKEN="SEU_TOKEN"
curl -s "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=$TOKEN&cpf=06672679642" | \
  python3 -c "import sys,json; d=json.load(sys.stdin); item=d.get('identidade') or d.get('list',[{}])[0]; item.pop('foto', None); print(json.dumps(item, indent=2, ensure_ascii=False))"
```

---

## 🔗 Referências

- API Base: `http://192.168.161.165:8082`
- Endpoints testados:
  - `/api/consulta/identidade/nome`
  - `/api/consulta/identidade/cpf`
  - `/api/consulta/identidade/ar`
- Token: Configurado em `API_TOKEN` no arquivo `.env`

---

## 💡 Dica

Se a API não retornar `data_criacao` diretamente, pode ser que:
1. O campo tenha outro nome
2. O campo esteja em um objeto aninhado
3. Precise fazer uma requisição adicional para obter a data
4. A data esteja em outro endpoint específico

Após verificar, informe os resultados para ajustarmos o código!
