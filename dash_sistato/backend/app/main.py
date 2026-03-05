from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from app.routers import auth, users
from app.database import engine, Base
from .routers import (
    menu, temas, audit, menus, roles, permissions, dynamic_menu,
    user_permissions, consulta_integrada, servicos,
    consulta_identidade, tabelas_centralizadas, consulta_rfb,
    consulta_fiscalizacao, consulta_auditorias, consulta_estatistica,
    consulta_prescricao, eleicoes, dados_abertos, user_requests,
    activity_logs, export, dashboard, relatorios,
)
from .init_db import init_db
from .init_menus import init_menus_data
from .core.cors import setup_cors
import logging
import traceback

# Configurar logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Criar as tabelas no banco de dados
logger.info("Criando tabelas no banco de dados...")
Base.metadata.create_all(bind=engine)

# Inicializar dados do banco
logger.info("Inicializando dados do banco...")
init_db()

# Inicializar menus
logger.info("Inicializando menus...")
init_menus_data()

app = FastAPI(title="Sistema Consultas CFO API")

# Configurar CORS (DEVE ser adicionado antes dos routers para incluir headers em erros)
setup_cors(app)

# Activity Log Middleware - registra todas as acoes dos usuarios
from .lib.activity_log import ActivityLogMiddleware
app.add_middleware(ActivityLogMiddleware)


def _cors_headers(origin):
    """Headers CORS para respostas de erro (evita bloqueio no browser)"""
    from app.core.cors import ALLOWED_ORIGINS
    import re
    ok = origin and (origin in ALLOWED_ORIGINS or re.match(r"^http://(localhost|127\.0\.0\.1)(:\d+)?$", origin))
    allow = origin if ok else ALLOWED_ORIGINS[0]
    return {
        "Access-Control-Allow-Origin": allow,
        "Access-Control-Allow-Credentials": "true",
        "Access-Control-Allow-Methods": "*",
        "Access-Control-Allow-Headers": "*",
    }


@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
    """Handler para erros 500 - inclui CORS headers para que o frontend receba a resposta"""
    from fastapi import HTTPException
    from fastapi.exceptions import RequestValidationError
    if isinstance(exc, (HTTPException, RequestValidationError)):
        raise exc  # Deixa FastAPI tratar
    logger.error(f"Erro 500: {exc}\n{traceback.format_exc()}")
    origin = request.headers.get("origin")
    headers = _cors_headers(origin)
    return JSONResponse(
        status_code=500,
        content={"detail": str(exc), "type": "internal_error"},
        headers=headers,
    )

# Incluir os routers
app.include_router(auth.router)
app.include_router(users.router)
app.include_router(menu.router, prefix="/api", tags=["menu"])
app.include_router(temas.router)
app.include_router(audit.router)

# Novos routers de permissões
app.include_router(menus.router, prefix="/api", tags=["menus"])
app.include_router(roles.router, prefix="/api", tags=["roles"])
app.include_router(permissions.router, prefix="/api", tags=["permissions"])
app.include_router(dynamic_menu.router, prefix="/api", tags=["dynamic-menu"])
app.include_router(user_permissions.router, prefix="/api", tags=["user-permissions"])
app.include_router(consulta_integrada.router, prefix="/api", tags=["consulta-integrada"])
app.include_router(servicos.router, prefix="/api", tags=["servicos"])

# Routers de serviços
app.include_router(consulta_identidade.router, prefix="/api", tags=["consulta-identidade"])
app.include_router(tabelas_centralizadas.router, prefix="/api", tags=["tabelas-centralizadas"])
app.include_router(consulta_rfb.router, prefix="/api", tags=["consulta-rfb"])
app.include_router(consulta_fiscalizacao.router, prefix="/api", tags=["consulta-fiscalizacao"])
app.include_router(consulta_auditorias.router, prefix="/api", tags=["consulta-auditorias"])
app.include_router(consulta_estatistica.router, prefix="/api", tags=["consulta-estatistica"])
app.include_router(consulta_prescricao.router, prefix="/api", tags=["consulta-prescricao"])
app.include_router(eleicoes.router, prefix="/api", tags=["eleicoes-regionais"])
app.include_router(dados_abertos.router, prefix="/api", tags=["dados-abertos"])
app.include_router(user_requests.router, prefix="/api", tags=["user-requests"])
app.include_router(activity_logs.router, prefix="/api", tags=["activity-logs"])
app.include_router(export.router, prefix="/api", tags=["export"])
app.include_router(dashboard.router, prefix="/api", tags=["dashboard"])
app.include_router(relatorios.router, prefix="/api", tags=["relatorios"])

@app.get("/")
def read_root():
    return {"message": "Welcome to Sistema Consultas CFO"}
    

@app.get("/health")
def health_check():
    return {"status": "healthy"} 