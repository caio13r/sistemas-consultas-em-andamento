@echo off

echo Construindo as imagens...
docker-compose build

echo Iniciando os containers...
docker-compose up -d

echo Aguardando PostgreSQL iniciar...
timeout /t 10

echo Executando migracoes...
docker-compose exec web alembic upgrade head

echo Inicializando banco de dados...
docker-compose exec web python -m app.db.init

echo.
echo Aplicacao iniciada com sucesso!
echo API disponivel em: http://localhost:8000
echo Documentacao da API: http://localhost:8000/docs
echo. 