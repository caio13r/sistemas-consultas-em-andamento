FROM python:3.11-slim

WORKDIR /app

# Instalar netcat, dos2unix, curl e dependências para SQL Server (pyodbc)
RUN apt-get update && apt-get install -y \
    netcat-traditional dos2unix curl \
    gnupg2 apt-transport-https \
    unixodbc unixodbc-dev \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar o resto dos arquivos
COPY . .

# Script de inicialização
COPY app/start.sh .
RUN dos2unix start.sh && chmod +x start.sh

# Comando para iniciar a aplicação em modo de desenvolvimento
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"] 