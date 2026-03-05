# Sistema de Consultas

API/aplicação de consultas integradas em PHP (versão v1.9), com Redis para cache e sessão.

## Características

- Aplicação PHP (router, APIs de consulta)
- Redis para cache (senha: `teste123`)
- Container único da aplicação + serviço Redis

## Containers e portas

| Serviço   | Container                 | Porta host | Porta container |
|-----------|----------------------------|------------|------------------|
| App PHP   | sistema-consultas         | 8082       | 80               |
| Redis     | sistema-consultas-redis-1 | 6379       | 6379             |

- **Acesso à aplicação:** http://localhost:8082  
- **Redis:** `localhost:6379` (senha: `teste123`)

A porta **8082** está configurada no `docker-compose.yml` para evitar conflito com outros serviços (ex.: 8080).

## Subir com Docker

```powershell
cd sistema-consultas
docker-compose up -d --build
```

A primeira build pode demorar (imagem Ubuntu + PHP + extensões como sqlsrv/pdo_sqlsrv). As seguintes usam cache.

## Estrutura do repositório

```
sistema-consultas/
├── src/              # Código PHP (mapeado no container)
├── Dockerfile
├── docker-compose.yml
└── README.md
```

O volume `./src` é montado em `/var/www/html/src` no container para desenvolvimento.

## Redis

- **Comando no compose:** `redis-server --save 20 1 --loglevel warning --requirepass teste123`
- Dados persistidos em volume Docker `cache`.

## Documentação adicional

- Visão geral dos containers: [Containers e Serviços](./containers-servicos.md)
