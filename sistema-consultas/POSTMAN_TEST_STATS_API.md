# Teste da API Stats - Postman

## Endpoint: GET /api/cpf-aprovados/stats

### URL Completa
```
http://192.168.161.165:8082/api/cpf-aprovados/stats?token=dc523cd42ccbaedd580390611cd176e1&days=10
```

### Configuração no Postman

#### Método
```
GET
```

#### URL
```
http://192.168.161.165:8082/api/cpf-aprovados/stats
```

#### Query Parameters (Params)
| Key | Value | Description |
|-----|-------|-------------|
| `token` | `dc523cd42ccbaedd580390611cd176e1` | Token de autenticação |
| `days` | `10` | Número de dias (opcional, padrão: 10) |

#### Headers
```
Accept: */*
```

### Exemplos de URLs

**Últimos 10 dias (padrão):**
```
http://192.168.161.165:8082/api/cpf-aprovados/stats?token=dc523cd42ccbaedd580390611cd176e1&days=10
```

**Últimos 30 dias:**
```
http://192.168.161.165:8082/api/cpf-aprovados/stats?token=dc523cd42ccbaedd580390611cd176e1&days=30
```

**Últimos 7 dias:**
```
http://192.168.161.165:8082/api/cpf-aprovados/stats?token=dc523cd42ccbaedd580390611cd176e1&days=7
```

### Resposta Esperada

A resposta deve ser um JSON com a seguinte estrutura:

```json
{
  "resumo": {
    "total_cpfs": 0,
    "podem_autorizar": 24811,
    "ja_processados": 0,
    "total_ufs": 27
  },
  "por_uf": [
    {
      "uf": "AC",
      "cpfs_unicos": 175,
      "total": 175
    },
    {
      "uf": "AL",
      "cpfs_unicos": 368,
      "total": 368
    }
    // ... mais UFs
  ],
  "por_dia": [
    {
      "data": "2025-12-02",
      "total": 8936,
      "disponiveis": 8936,
      "ufs": 4,
      "detalhes_uf": {
        "SP": 100,
        "MG": 50,
        "RJ": 30,
        "BA": 20
      }
    },
    {
      "data": "2025-12-01",
      "total": 10585,
      "disponiveis": 10585,
      "ufs": 10,
      "detalhes_uf": {
        "SP": 200,
        "MG": 150,
        "RJ": 100
        // ... mais UFs
      }
    }
    // ... mais dias
  ]
}
```

### ⚠️ IMPORTANTE: Verificar `detalhes_uf`

**O campo crítico que você precisa verificar é `detalhes_uf` em cada item de `por_dia`.**

Cada objeto em `por_dia` deve ter:
- `data`: Data no formato YYYY-MM-DD
- `total`: Total de CPFs
- `disponiveis`: CPFs disponíveis
- `ufs`: Número de UFs
- **`detalhes_uf`**: Objeto com contagem por UF (ex: `{"SP": 100, "MG": 50}`)

### O que verificar na resposta:

1. ✅ Se `detalhes_uf` existe em cada item de `por_dia`
2. ✅ Se `detalhes_uf` é um objeto (não array, não null, não vazio)
3. ✅ Se `detalhes_uf` contém as UFs como chaves e números como valores
4. ✅ Se a soma dos valores em `detalhes_uf` é igual a `disponiveis`

### Exemplo de resposta CORRETA:

```json
{
  "por_dia": [
    {
      "data": "2025-12-02",
      "total": 8936,
      "disponiveis": 8936,
      "ufs": 4,
      "detalhes_uf": {
        "SP": 354,
        "RJ": 2485,
        "MG": 3944,
        "BA": 2153
      }
    }
  ]
}
```

### Exemplo de resposta INCORRETA (sem detalhes_uf):

```json
{
  "por_dia": [
    {
      "data": "2025-12-02",
      "total": 8936,
      "disponiveis": 8936,
      "ufs": 4
      // ❌ Faltando detalhes_uf
    }
  ]
}
```

### Troubleshooting

**Se `detalhes_uf` não aparecer:**
1. Verifique se a API foi atualizada no servidor `192.168.161.165:8082`
2. Verifique se o código da API está usando a query correta com JOINs
3. Verifique os logs do servidor da API

**Se `detalhes_uf` vier vazio `{}`:**
1. Pode não haver dados para aquele período
2. Verifique se há CPFs disponíveis no banco de dados
3. Verifique se os JOINs estão corretos (excluindo Identity e Discard)

