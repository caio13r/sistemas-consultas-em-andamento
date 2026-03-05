# 🧪 Como Testar se o Servidor 165 Retorna data_criacao

## 📋 Resumo

O sistema foi configurado para:
1. **Primeiro**: Buscar `data_criacao` na resposta da API do servidor 165
2. **Se não encontrar**: Fazer chamada adicional ao servidor de foto (192.168.161.128) que **definitivamente retorna** `data_criacao`

---

## 🚀 Teste Rápido

### Opção 1: Usando cURL (Mais Simples)

```bash
# Substitua SEU_TOKEN pelo token real do arquivo .env
TOKEN="SEU_TOKEN_AQUI"

# Teste 1: Consulta por CPF
curl -s "http://192.168.161.165:8082/api/consulta/identidade/cpf?token=$TOKEN&cpf=06672679642" | python3 -m json.tool

# Teste 2: Consulta por Nome
curl -s "http://192.168.161.165:8082/api/consulta/identidade/nome?token=$TOKEN&name=HUMBERTO%20FRANCO%20BUENO&page_number=1&page_amount=1" | python3 -m json.tool
```

### Opção 2: Usando o Script Bash

```bash
# Passar o token como parâmetro
./testar-endpoints-165.sh SEU_TOKEN_AQUI

# Ou configurar variável de ambiente
export API_TOKEN=seu_token_aqui
./testar-endpoints-165.sh
```

### Opção 3: Usando o Script PHP (se PHP CLI estiver disponível)

```bash
php testar-endpoints-165.php
```

---

## 🔍 O que Verificar

### ✅ Se o servidor 165 RETORNA data_criacao:

A resposta JSON deve conter um campo `data_criacao` em um destes lugares:

1. **Na raiz do objeto:**
```json
{
  "identidade": {
    "id": "123",
    "nome": "...",
    "cpf": "...",
    "data_criacao": "09/04/2025 20:27:16",  ← AQUI
    "foto": "..."
  }
}
```

2. **Dentro do objeto 'foto' (se for objeto):**
```json
{
  "identidade": {
    "foto": {
      "data_criacao": "09/04/2025 20:27:16",  ← AQUI
      "base64": "..."
    }
  }
}
```

3. **No array 'list' (consulta por nome):**
```json
{
  "list": [
    {
      "id": "123",
      "data_criacao": "09/04/2025 20:27:16",  ← AQUI
      "foto": "..."
    }
  ]
}
```

### ❌ Se o servidor 165 NÃO RETORNA data_criacao:

O sistema **automaticamente** fará uma chamada adicional ao servidor de foto:

```
POST http://192.168.161.128/service/v1/consulta/solicitacao/cpf
```

Que **definitivamente retorna** `data_criacao` conforme documentado.

---

## 📊 Exemplo de Saída do Teste

### Se encontrar data_criacao no servidor 165:

```
✅ Requisição bem-sucedida!
📦 Estrutura: objeto 'identidade'
📊 Campos disponíveis:
  • cpf                          = 06672679642
  • data_criacao                 = 09/04/2025 20:27:16  ← ENCONTRADO!
  • foto                         = iVBORw0KGgoAAAANSUh...
  • id                           = 123
  • nome                         = HUMBERTO FRANCO BUENO

🔍 Verificando data_criacao:
  ✅ data_criacao encontrado na raiz: 09/04/2025 20:27:16
```

### Se NÃO encontrar:

```
✅ Requisição bem-sucedida!
📦 Estrutura: objeto 'identidade'
📊 Campos disponíveis:
  • cpf                          = 06672679642
  • foto                         = iVBORw0KGgoAAAANSUh...
  • id                           = 123
  • nome                         = HUMBERTO FRANCO BUENO

🔍 Verificando data_criacao:
  ❌ data_criacao NÃO encontrado na raiz
  ❌ data_criacao NÃO encontrado dentro de 'foto'

→ O sistema fará chamada adicional ao servidor de foto (192.168.161.128)
```

---

## 🎯 Resultado Esperado

Independente do resultado:

- ✅ **Se o servidor 165 retornar `data_criacao`**: O sistema usa diretamente
- ✅ **Se o servidor 165 NÃO retornar `data_criacao`**: O sistema busca no servidor de foto automaticamente
- ✅ **A data sempre aparecerá** ao lado da foto na tabela
- ✅ **A data sempre será incluída** no PDF exportado

---

## 🔧 Configuração Atual

O código está configurado para:

1. **Buscar na resposta da API 165** (múltiplos campos possíveis):
   - `data_criacao`
   - `data_criacao_foto`
   - `foto_data_criacao`
   - `data_foto`
   - `created_at`
   - E outros...

2. **Se não encontrar, buscar no servidor de foto**:
   - Endpoint: `POST http://[IP]/service/v1/consulta/solicitacao/cpf`
   - Baseado no CRO para determinar qual servidor usar
   - Retorna a foto mais recente com `data_criacao`

3. **Cache implementado** para evitar múltiplas requisições do mesmo CPF

---

## 📝 Próximos Passos

1. Execute o teste usando um dos métodos acima
2. Verifique se `data_criacao` aparece na resposta do servidor 165
3. Se aparecer: ✅ Tudo funcionando!
4. Se não aparecer: ✅ O sistema buscará automaticamente no servidor de foto

**Em ambos os casos, a data será exibida na tabela e no PDF!**

---

## 🆘 Problemas?

Se o teste não funcionar:

1. **Verifique o token**: Deve estar no arquivo `.env` como `API_TOKEN=seu_token`
2. **Verifique conectividade**: `ping 192.168.161.165`
3. **Verifique porta**: `telnet 192.168.161.165 8082`
4. **Verifique logs**: Os erros são logados no PHP error_log

---

**Última atualização**: 26/01/2026
