#!/bin/bash

# Construir as imagens
docker-compose build

# Iniciar os containers
docker-compose up -d

# Aguardar o PostgreSQL estar pronto
echo "Aguardando PostgreSQL iniciar..."
sleep 10

# Executar as migrações
docker-compose exec web alembic upgrade head

# Inicializar o banco de dados
docker-compose exec web python -m app.db.init

echo "Aplicação iniciada com sucesso!"
echo "API disponível em: http://localhost:8000"
echo "Documentação da API: http://localhost:8000/docs" 