#!/bin/bash

# Esperar o banco de dados estar pronto
echo "Waiting for database..."
while ! nc -z db 5432; do
  sleep 0.1
done
echo "Database is ready!"
# Inicializar o banco de dados
echo "Initializing database..."
python -c "from app.init_db import init_db; init_db()"
# Iniciar a aplicação
echo "Starting application..."
uvicorn app.main:app --host 0.0.0.0 --port 8000