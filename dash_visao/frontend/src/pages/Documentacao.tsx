import React from 'react';
import {
  Typography,
  Box,
  Divider,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Accordion,
  AccordionSummary,
  AccordionDetails,
} from '@mui/material';
import {
  ExpandMore as ExpandMoreIcon,
  Storage as StorageIcon,
  Code as CodeIcon,
  Security as SecurityIcon,
  Dns as DnsIcon,
  Layers as LayersIcon,
  Api as ApiIcon,
  DesignServices as DesignIcon,
  Terminal as TerminalIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';

const sectionCardSx = {
  mb: 0,
  '&:before': { display: 'none' },
  boxShadow: 'none',
  border: '1px solid rgba(122,30,38,0.08)',
  borderRadius: '10px !important',
  overflow: 'hidden',
};

const sectionHeaderSx = {
  backgroundColor: 'rgba(122,30,38,0.03)',
  '&:hover': { backgroundColor: 'rgba(122,30,38,0.06)' },
};

const tableSx = {
  '& .MuiTableCell-root': { py: 1.2, px: 2, fontSize: '0.82rem' },
};

const codeBlockSx = {
  bgcolor: '#1a1a2e',
  color: '#e0e0e0',
  p: 2,
  borderRadius: 1,
  fontSize: '0.75rem',
  fontFamily: "'JetBrains Mono', 'Fira Code', monospace",
  overflow: 'auto',
  lineHeight: 1.6,
  mt: 1.5,
  mb: 0.5,
  whiteSpace: 'pre' as const,
  '& .kw': { color: '#c792ea' },
  '& .fn': { color: '#82aaff' },
  '& .str': { color: '#c3e88d' },
  '& .cmt': { color: '#546e7a' },
  '& .type': { color: '#ffcb6b' },
  '& .num': { color: '#f78c6c' },
};

const CodeBlock: React.FC<{ title: string; file: string; children: React.ReactNode }> = ({ title, file, children }) => (
  <Box sx={{ mt: 2 }}>
    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
      <Typography variant="caption" sx={{ fontWeight: 700, color: '#7A1E26' }}>{title}</Typography>
      <Typography variant="caption" sx={{ color: 'text.secondary', fontFamily: 'monospace', fontSize: '0.7rem' }}>{file}</Typography>
    </Box>
    <Box component="pre" sx={codeBlockSx}>{children}</Box>
  </Box>
);

const Documentacao: React.FC = () => {
  return (
    <PageContainer>
      <Box sx={{ mb: 4 }}>
        <Typography variant="h5" sx={{ fontWeight: 700, color: '#7A1E26', mb: 0.5 }}>
          Documentação do Sistema
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Visão geral da arquitetura, tecnologias, bancos de dados e funcionamento do sistema.
        </Typography>
      </Box>

      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>

        {/* Visao Geral */}
        <Accordion defaultExpanded sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <LayersIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Visão Geral</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <Typography variant="body2" sx={{ mb: 2 }}>
              Sistema desenvolvido para a <strong>CFO (Coordenação de Fiscalização de Órgãos)</strong> e os <strong>27 CROs (Conselhos Regionais)</strong>.
            </Typography>
            <Box sx={{ p: 2, borderLeft: '4px solid #7A1E26', bgcolor: 'rgba(122,30,38,0.03)', borderRadius: 1, mb: 2 }}>
              <Typography variant="subtitle2" sx={{ fontWeight: 700, color: '#7A1E26' }}>dash_visao</Typography>
              <Typography variant="body2" color="text.secondary">
                Dashboard moderno com gestão de usuários, permissões dinâmicas, auditorias, consultas integradas e relatórios. Construído com FastAPI (Python) no backend e React (TypeScript) no frontend, utilizando PostgreSQL como banco principal.
              </Typography>
            </Box>
            <Typography variant="body2" color="text.secondary">
              O sistema é containerizado com Docker e se conecta a múltiplos bancos de dados para consolidar informações de diferentes fontes.
            </Typography>
          </AccordionDetails>
        </Accordion>

        {/* Tecnologias - Backend */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <CodeIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Tecnologias - Backend</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <TableContainer>
              <Table size="small" sx={tableSx}>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Tecnologia</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Versão</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Finalidade</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {[
                    ['Python', '3.11', 'Linguagem principal do backend'],
                    ['FastAPI', '0.104.1', 'Framework web / API REST'],
                    ['Uvicorn', '0.24.0', 'Servidor ASGI'],
                    ['SQLAlchemy', '2.0.23', 'ORM (mapeamento objeto-relacional)'],
                    ['Alembic', '1.12.1', 'Migrações de banco de dados'],
                    ['Pydantic', '2.5.2', 'Validação de dados e schemas'],
                    ['Python-jose', '-', 'Geração e validação de tokens JWT'],
                    ['Passlib + bcrypt', '-', 'Hash seguro de senhas'],
                    ['Redis', '5.0.1', 'Cache e filas'],
                    ['Celery', '5.3.6', 'Tarefas assíncronas em background'],
                  ].map(([tech, ver, desc]) => (
                    <TableRow key={tech}>
                      <TableCell><Chip label={tech} size="small" variant="outlined" sx={{ fontWeight: 600 }} /></TableCell>
                      <TableCell>{ver}</TableCell>
                      <TableCell>{desc}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            <Divider sx={{ my: 2.5 }} />

            <CodeBlock title="FastAPI - Inicialização da aplicação" file="backend/app/main.py">
{`from fastapi import FastAPI
from app.database import engine, Base

Base.metadata.create_all(bind=engine)

app = FastAPI(title="Sistema Consultas CFO API")
setup_cors(app)

# Activity Log Middleware
from .lib.activity_log import ActivityLogMiddleware
app.add_middleware(ActivityLogMiddleware)

# Routers
app.include_router(auth.router, prefix="/api")
app.include_router(users.router, prefix="/api")
app.include_router(consulta_integrada.router, prefix="/api")`}
            </CodeBlock>

            <CodeBlock title="SQLAlchemy - Modelo User" file="backend/app/models/user.py">
{`from sqlalchemy import Column, Integer, String, Boolean, DateTime
from sqlalchemy.orm import relationship
from ..database import Base

class User(Base):
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    username = Column(String(50), unique=True, index=True)
    email = Column(String(100), unique=True, index=True)
    full_name = Column(String(100))
    hashed_password = Column(String(100), nullable=False)
    is_active = Column(Boolean, default=True)
    is_superuser = Column(Boolean, default=False)

    roles = relationship("Role", secondary=user_roles, back_populates="users")
    direct_permissions = relationship("Permission", secondary=user_permissions)`}
            </CodeBlock>

            <CodeBlock title="Pydantic - Schema de validação" file="backend/app/schemas/user_request.py">
{`from pydantic import BaseModel, EmailStr
from typing import List, Optional

class UserRequestCreate(BaseModel):
    nome_completo: str
    email: EmailStr
    senha: str
    telefone: Optional[str] = None
    origem_tipo: str        # "cfo" ou "cro"
    organizacao: str
    justificativa: str
    items: List[UserRequestItemCreate] = []`}
            </CodeBlock>

            <CodeBlock title="JWT - Geração e validação de tokens" file="backend/app/core/auth.py">
{`from jose import JWTError, jwt
from passlib.context import CryptContext

SECRET_KEY = os.getenv("SECRET_KEY", "your-secret-key-here")
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 30

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")

def create_access_token(data: dict, expires_delta=None):
    to_encode = data.copy()
    expire = datetime.utcnow() + (expires_delta or timedelta(minutes=15))
    to_encode.update({"exp": expire})
    return jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)

def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)`}
            </CodeBlock>

            <CodeBlock title="SQLAlchemy - Conexão multi-banco" file="backend/app/database.py">
{`from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

# PostgreSQL - Banco Principal
DATABASE_URL = os.getenv("DATABASE_URL",
    "postgresql://postgres:postgres@db:5432/appdb")
engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(bind=engine)

# MySQL - Banco Auxiliar (Locaweb)
_db1_url = f"mysql+pymysql://{user}:{pwd}@{host}:{port}/{name}"
engine_db1 = create_engine(_db1_url, pool_pre_ping=True)
SessionDB1 = sessionmaker(bind=engine_db1)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

        {/* Tecnologias - Frontend */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <DesignIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Tecnologias - Frontend</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <TableContainer>
              <Table size="small" sx={tableSx}>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Tecnologia</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Versão</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Finalidade</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {[
                    ['React', '18.2.0', 'Biblioteca de interface de usuário'],
                    ['TypeScript', '5.2.2', 'Tipagem estática para JavaScript'],
                    ['Vite', '5.1.0', 'Build tool e servidor de desenvolvimento'],
                    ['Material-UI (MUI)', '5.15.10', 'Biblioteca de componentes visuais'],
                    ['Tailwind CSS', '3.4.1', 'Framework CSS utilitário'],
                    ['Axios', '1.6.7', 'Cliente HTTP para comunicação com API'],
                    ['React Router DOM', '6.22.1', 'Roteamento SPA (Single Page Application)'],
                    ['Notistack', '3.0.2', 'Notificações toast'],
                    ['Lucide React', '0.344.0', 'Biblioteca de ícones'],
                  ].map(([tech, ver, desc]) => (
                    <TableRow key={tech}>
                      <TableCell><Chip label={tech} size="small" variant="outlined" sx={{ fontWeight: 600 }} /></TableCell>
                      <TableCell>{ver}</TableCell>
                      <TableCell>{desc}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            <Divider sx={{ my: 2.5 }} />

            <CodeBlock title="Axios - Cliente HTTP com interceptors" file="frontend/src/services/api.ts">
{`import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.DEV
    ? '/api'
    : (import.meta.env.VITE_API_URL || 'http://localhost:8002/api'),
});

// Adiciona token JWT automaticamente
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token && config.headers) {
    config.headers.Authorization = \`Bearer \${token}\`;
  }
  return config;
});

// Redireciona para login em caso de 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);`}
            </CodeBlock>

            <CodeBlock title="React Context - Autenticação e permissões" file="frontend/src/contexts/AuthContext.tsx">
{`interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  permissions: string[];
  roles: string[];
  isAdmin: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => void;
  hasPermission: (permission: string) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
}

export function AuthProvider({ children }) {
  const [permissions, setPermissions] = useState<string[]>([]);

  const fetchCurrentUser = async () => {
    const currentUser = await userService.getCurrentUser();
    setPermissions(currentUser.permissions || []);
    setRoles(currentUser.roles || []);
    setIsAuthenticated(true);
  };
}`}
            </CodeBlock>

            <CodeBlock title="React Router - Rotas protegidas por permissão" file="frontend/src/App.tsx">
{`<Routes>
  {/* Rota publica */}
  <Route path="/login" element={<Login />} />

  {/* Rota privada (qualquer usuario autenticado) */}
  <Route path="/" element={
    <PrivateRoute><Layout><Home /></Layout></PrivateRoute>
  } />

  {/* Rota com permissao especifica */}
  <Route path="/consulta-integrada" element={
    <PermissionRoute requiredPermission="view_consulta_integrada">
      <Layout><ConsultaIntegrada /></Layout>
    </PermissionRoute>
  } />

  {/* Rota somente admin */}
  <Route path="/users" element={
    <AdminRoute><Layout><UserList /></Layout></AdminRoute>
  } />
</Routes>`}
            </CodeBlock>

            <CodeBlock title="Material-UI - Tema customizado" file="frontend/src/App.tsx">
{`const theme = createTheme({
  palette: {
    primary: {
      main: '#7A1E26',      // Burgundy
      light: '#9A2832',
      dark: '#5C1519',
    },
    secondary: { main: '#635962' },  // Wine
    background: {
      default: '#FBF8F4',   // Creme
      paper: '#FFFFFF',
    },
    success: { main: '#2F7D4F' },
    warning: { main: '#F7C437' },
    error: { main: '#C8393F' },
  },
  typography: {
    fontFamily: "'Geist', 'Inter', sans-serif",
    button: { textTransform: 'none', fontWeight: 600 },
  },
});`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

        {/* Bancos de Dados */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <StorageIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Bancos de Dados</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <Typography variant="body2" sx={{ mb: 2 }}>
              O sistema utiliza <strong>6 bancos de dados</strong> diferentes para consolidar informações de múltiplas fontes:
            </Typography>
            <TableContainer>
              <Table size="small" sx={tableSx}>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Banco</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Tipo</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Nome</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Finalidade</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {[
                    ['Principal', 'PostgreSQL 15', 'appdb', 'Usuários, permissões, menus, auditorias, logs, documentos'],
                    ['DB1', 'MySQL (Locaweb)', 'db_sistema_consultas', 'Auditoria RFB, rótulos e cadastro'],
                    ['DB2', 'MySQL (WSCFO)', '-', 'Webservice SISCAF'],
                    ['DB3', 'SQL Server (Implanta)', 'CFO_CWS', 'Consultas integradas, auditoria e fiscalização'],
                    ['DB5', 'MySQL', 'db_prescricao', 'Dados de prescrição'],
                    ['DB6', 'MySQL', 'identity_professional', 'Identidade profissional'],
                  ].map(([label, tipo, nome, desc]) => (
                    <TableRow key={label}>
                      <TableCell><Chip label={label} size="small" color={label === 'Principal' ? 'primary' : 'default'} /></TableCell>
                      <TableCell>{tipo}</TableCell>
                      <TableCell><code>{nome}</code></TableCell>
                      <TableCell>{desc}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            <Divider sx={{ my: 2.5 }} />

            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.5 }}>
              Tabelas Principais (PostgreSQL - appdb)
            </Typography>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
              {[
                'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
                'user_permissions', 'menus', 'submenus', 'audits', 'themes',
                'documents', 'activity_logs', 'change_logs', 'user_requests',
                'servicos', 'password_reset_tokens',
              ].map(t => (
                <Chip key={t} label={t} size="small" variant="outlined" sx={{ fontFamily: 'monospace', fontSize: '0.75rem' }} />
              ))}
            </Box>

            <CodeBlock title="Tabelas de associação (many-to-many)" file="backend/app/models/permission.py">
{`# Associacao roles <-> permissions
role_permissions = Table('role_permissions', Base.metadata,
    Column('role_id', Integer, ForeignKey('roles.id')),
    Column('permission_id', Integer, ForeignKey('permissions.id')),
    Column('granted', Boolean, default=True),   # False = negacao explicita
)

# Associacao users <-> roles
user_roles = Table('user_roles', Base.metadata,
    Column('user_id', Integer, ForeignKey('users.id')),
    Column('role_id', Integer, ForeignKey('roles.id')),
)

# Permissões diretas do usuario (override)
user_permissions = Table('user_permissions', Base.metadata,
    Column('user_id', Integer, ForeignKey('users.id')),
    Column('permission_id', Integer, ForeignKey('permissions.id')),
    Column('granted', Boolean, default=True),
)`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

        {/* Autenticacao */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <SecurityIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Autenticação e Segurança</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', mb: 2 }}>
              <Box sx={{ flex: 1, minWidth: 280 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>JWT (JSON Web Token)</Typography>
                <TableContainer>
                  <Table size="small" sx={tableSx}>
                    <TableBody>
                      {[
                        ['Algoritmo', 'HS256'],
                        ['Expiração', '30 minutos (configurável)'],
                        ['Armazenamento', 'localStorage no navegador'],
                        ['Header', 'Authorization: Bearer <token>'],
                        ['Hash de senhas', 'bcrypt (via Passlib)'],
                      ].map(([k, v]) => (
                        <TableRow key={k}>
                          <TableCell sx={{ fontWeight: 600, width: 160 }}>{k}</TableCell>
                          <TableCell><code>{v}</code></TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
              <Box sx={{ flex: 1, minWidth: 280 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Fluxo de Login</Typography>
                <Box component="ol" sx={{ pl: 2.5, m: 0, '& li': { mb: 0.5, fontSize: '0.82rem' } }}>
                  <li>Usuário envia email/username + senha para <code>POST /api/token</code></li>
                  <li>Backend valida credenciais com bcrypt</li>
                  <li>Token JWT é gerado e retornado</li>
                  <li>Frontend armazena token no localStorage</li>
                  <li>Requisições incluem token no header Authorization</li>
                  <li>Resposta 401 redireciona para tela de login</li>
                </Box>
              </Box>
            </Box>

            <Divider sx={{ my: 2 }} />

            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Medidas de Segurança</Typography>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
              {[
                'Senhas com hash bcrypt',
                'Tokens JWT com expiração',
                'CORS restrito',
                'Log de todas as requisições',
                'Permissões granulares',
                'Validação Pydantic',
                'Proteção contra ciclos em papéis',
                'Interceptor 401 no frontend',
              ].map(item => (
                <Chip key={item} label={item} size="small" color="success" variant="outlined" />
              ))}
            </Box>
          </AccordionDetails>
        </Accordion>

        {/* Sistema de Permissoes */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <SecurityIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Sistema de Permissões e Papéis</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', mb: 2 }}>
              <Box sx={{ flex: 1, minWidth: 280 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Modelo de Permissão</Typography>
                <Typography variant="body2" sx={{ mb: 1 }}>Cada permissão possui:</Typography>
                <TableContainer>
                  <Table size="small" sx={tableSx}>
                    <TableBody>
                      {[
                        ['name', 'Nome único (ex: ver_consulta_integrada)'],
                        ['action', 'view, edit, delete, export, approve, manage'],
                        ['resource', 'Recurso associado'],
                        ['scope_type', 'global, cfo ou cro'],
                      ].map(([k, v]) => (
                        <TableRow key={k}>
                          <TableCell sx={{ fontWeight: 600, fontFamily: 'monospace' }}>{k}</TableCell>
                          <TableCell>{v}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
              <Box sx={{ flex: 1, minWidth: 280 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Hierarquia de Papéis</Typography>
                <Typography variant="body2" sx={{ mb: 1 }}>
                  Papeis possuem relação pai-filho com detecção de ciclos:
                </Typography>
                <TableContainer>
                  <Table size="small" sx={tableSx}>
                    <TableBody>
                      {[
                        ['Nível 1', 'Visualização'],
                        ['Nível 2', 'Edição'],
                        ['Nível 3', 'Aprovação'],
                        ['Nível 4', 'Administração'],
                      ].map(([k, v]) => (
                        <TableRow key={k}>
                          <TableCell sx={{ fontWeight: 600 }}>{k}</TableCell>
                          <TableCell>{v}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
            </Box>

            <Divider sx={{ my: 2 }} />

            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Cálculo de Permissões Efetivas</Typography>
            <Box component="ol" sx={{ pl: 2.5, m: 0, '& li': { mb: 0.5, fontSize: '0.82rem' } }}>
              <li>Coleta todos os papéis do usuário, incluindo herança hierárquica</li>
              <li>Aplica permissões dos papéis (respeitando campo <code>granted</code> para negações)</li>
              <li>Aplica permissões diretas do usuário como sobrescrita</li>
              <li>Resultado: <code>concedidas - negadas_por_papel + sobrescritas_diretas</code></li>
            </Box>

            <CodeBlock title="Hierarquia de roles e cálculo de permissões" file="backend/app/core/auth.py">
{`def _get_role_hierarchy(db, role, visited=None):
    """Caminha a hierarquia de roles ate a raiz, com protecao contra ciclos"""
    if visited is None:
        visited = set()
    hierarchy = [role]
    visited.add(role.id)
    if role.parent_role_id and role.parent_role_id not in visited:
        parent = db.query(Role).filter(Role.id == role.parent_role_id).first()
        if parent:
            hierarchy.extend(_get_role_hierarchy(db, parent, visited))
    return hierarchy

def get_user_permissions(db, user_id):
    user = db.query(User).filter(User.id == user_id).first()

    # 1. Coletar todos os roles (diretos + herdados)
    all_roles = []
    for role in user.roles:
        all_roles.extend(_get_role_hierarchy(db, role))

    # 2. Aplicar role_permissions (granted/denied)
    # 3. Aplicar user_permissions como override
    # Resultado: granted - denied_by_role + direct_overrides`}
            </CodeBlock>

            <CodeBlock title="Sidebar dinâmico baseado em permissões" file="frontend/src/components/Sidebar.tsx">
{`const { hasPermission, isAdmin } = useAuth();

const canSeeMenu = (menu) => {
  if (!menu.permission_name) return true;
  if (adminOnlyPermissions.includes(menu.permission_name))
    return isAdmin;
  return hasPermission(menu.permission_name);
};

// Menus carregados via API: GET /api/menus/
const fetchMenus = async () => {
  const response = await api.get('/menus/');
  const data = response.data
    .filter((m) => !m.disable)
    .sort((a, b) => a.order - b.order);
  setMenus(data);
};`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

        {/* Endpoints da API */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <ApiIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Principais Endpoints da API</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            {[
              {
                group: 'Autenticação',
                endpoints: [
                  ['POST', '/api/token', 'Login (email ou username)'],
                  ['GET', '/api/users/me', 'Usuário atual com permissões'],
                  ['POST', '/api/forgot-password', 'Solicitar reset de senha'],
                  ['POST', '/api/reset-password', 'Redefinir senha'],
                ],
              },
              {
                group: 'Usuários',
                endpoints: [
                  ['GET', '/api/users', 'Listar usuários'],
                  ['POST', '/api/users', 'Criar usuário'],
                  ['PATCH', '/api/users/me', 'Atualizar perfil'],
                  ['POST', '/api/users/{id}/roles', 'Atribuir papéis'],
                  ['POST', '/api/users/{id}/permissions', 'Permissões diretas'],
                ],
              },
              {
                group: 'Permissões e Papéis',
                endpoints: [
                  ['GET', '/api/permissions', 'Listar permissões'],
                  ['POST', '/api/permissions', 'Criar permissão'],
                  ['GET', '/api/roles', 'Listar papéis'],
                  ['POST', '/api/roles', 'Criar papel'],
                ],
              },
              {
                group: 'Menus',
                endpoints: [
                  ['GET', '/api/menu', 'Menu do usuário (filtrado)'],
                  ['GET', '/api/menus', 'Listar todos (admin)'],
                  ['POST', '/api/menus', 'Criar menu'],
                ],
              },
              {
                group: 'Solicitações',
                endpoints: [
                  ['GET', '/api/user-requests', 'Listar solicitações'],
                  ['POST', '/api/user-requests', 'Criar solicitação'],
                  ['PATCH', '/api/user-requests/{id}/status', 'Atualizar status'],
                ],
              },
              {
                group: 'Documentos',
                endpoints: [
                  ['GET', '/api/documentos', 'Listar documentos'],
                  ['POST', '/api/documentos/upload', 'Upload de documento'],
                  ['POST', '/api/documentos/{id}/validate', 'Validar documento'],
                ],
              },
            ].map(({ group, endpoints }) => (
              <Box key={group} sx={{ mb: 2.5 }}>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1, color: '#7A1E26' }}>{group}</Typography>
                <TableContainer>
                  <Table size="small" sx={tableSx}>
                    <TableBody>
                      {endpoints.map(([method, path, desc]) => (
                        <TableRow key={path + method}>
                          <TableCell sx={{ width: 70 }}>
                            <Chip
                              label={method}
                              size="small"
                              color={
                                method === 'GET' ? 'info' :
                                method === 'POST' ? 'success' :
                                method === 'PATCH' ? 'warning' : 'default'
                              }
                              sx={{ fontWeight: 700, fontFamily: 'monospace', fontSize: '0.7rem' }}
                            />
                          </TableCell>
                          <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.78rem' }}>{path}</TableCell>
                          <TableCell>{desc}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
            ))}

            <Divider sx={{ my: 2 }} />

            <CodeBlock title="Registro de routers no FastAPI" file="backend/app/main.py">
{`# Autenticacao e usuarios
app.include_router(auth.router, prefix="/api")
app.include_router(users.router, prefix="/api")

# Permissoes e menus
app.include_router(permissions.router, prefix="/api", tags=["permissions"])
app.include_router(roles.router, prefix="/api", tags=["roles"])
app.include_router(menus.router, prefix="/api", tags=["menus"])

# Servicos de consulta
app.include_router(consulta_integrada.router, prefix="/api")
app.include_router(consulta_rfb.router, prefix="/api")
app.include_router(consulta_fiscalizacao.router, prefix="/api")
app.include_router(consulta_estatistica.router, prefix="/api")
app.include_router(consulta_prescricao.router, prefix="/api")

# Documentos, logs, exportacao
app.include_router(documentos.router, prefix="/api")
app.include_router(activity_logs.router, prefix="/api")
app.include_router(export.router, prefix="/api")`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

        {/* Infraestrutura Docker */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <DnsIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Infraestrutura e Docker</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Serviços Docker</Typography>
            <TableContainer sx={{ mb: 2.5 }}>
              <Table size="small" sx={tableSx}>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Serviço</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Imagem</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Porta</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Descrição</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {[
                    ['db', 'PostgreSQL 15', '5434', 'Banco de dados principal'],
                    ['backend', 'Python 3.11-slim', '8002', 'API FastAPI'],
                    ['frontend', 'Node + Nginx', '5174', 'Interface React'],
                  ].map(([srv, img, port, desc]) => (
                    <TableRow key={srv}>
                      <TableCell><code>{srv}</code></TableCell>
                      <TableCell>{img}</TableCell>
                      <TableCell><Chip label={port} size="small" color="primary" variant="outlined" /></TableCell>
                      <TableCell>{desc}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            <CodeBlock title="Docker Compose" file="dash_visao/docker-compose.yml">
{`services:
  db:
    image: postgres:15
    ports: ["5434:5432"]
    environment:
      POSTGRES_DB: appdb
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    volumes: [postgres_data:/var/lib/postgresql/data]
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]

  backend:
    build: ./backend
    ports: ["8002:8000"]
    depends_on: [db]
    environment:
      DATABASE_URL: postgresql://postgres:postgres@db:5432/appdb
      SECRET_KEY: your-super-secret-key
    volumes: [./backend:/app]

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile.dev
    ports: ["5174:5173"]
    depends_on: [backend]
    volumes: [./frontend:/app]`}
            </CodeBlock>

            <Divider sx={{ my: 2 }} />

            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Como Subir o Sistema</Typography>
            <Box component="ol" sx={{ pl: 2.5, m: 0, '& li': { mb: 0.8, fontSize: '0.82rem' } }}>
              <li>Instalar Docker e Docker Compose</li>
              <li>
                Subir os containers:
                <Box component="pre" sx={{ bgcolor: '#1a1a2e', color: '#e0e0e0', p: 1.5, borderRadius: 1, mt: 0.5, fontSize: '0.78rem', overflow: 'auto' }}>
                  <code>cd dash_visao && docker-compose up -d</code>
                </Box>
              </li>
              <li>Acessar o sistema em <code>http://localhost:5174</code></li>
            </Box>
          </AccordionDetails>
        </Accordion>

        {/* Variáveis de Ambiente */}
        <Accordion sx={sectionCardSx}>
          <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={sectionHeaderSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <TerminalIcon sx={{ color: '#7A1E26' }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Variáveis de Ambiente</Typography>
            </Box>
          </AccordionSummary>
          <AccordionDetails>
            <TableContainer>
              <Table size="small" sx={tableSx}>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700 }}>Variável</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Descrição</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Padrão</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {[
                    ['DATABASE_URL', 'URL de conexão PostgreSQL', 'postgresql://...@db:5432/appdb'],
                    ['SECRET_KEY', 'Chave secreta para JWT', '(alterar em produção)'],
                    ['ALGORITHM', 'Algoritmo JWT', 'HS256'],
                    ['ACCESS_TOKEN_EXPIRE_MINUTES', 'Expiração do token (min)', '30'],
                    ['BACKEND_HOST', 'Host do servidor', '0.0.0.0'],
                    ['BACKEND_PORT', 'Porta do servidor', '8000'],
                    ['REDIS_URL', 'URL do Redis', 'redis://localhost:6379/0'],
                    ['SMTP_HOST', 'Servidor de email', 'smtp.gmail.com'],
                    ['SMTP_PORT', 'Porta SMTP', '587'],
                    ['LOG_LEVEL', 'Nível de log', 'INFO'],
                    ['ENVIRONMENT', 'Ambiente de execução', 'development'],
                    ['VITE_API_URL', 'URL base da API (frontend)', '-'],
                  ].map(([v, desc, def]) => (
                    <TableRow key={v}>
                      <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.78rem', fontWeight: 600 }}>{v}</TableCell>
                      <TableCell>{desc}</TableCell>
                      <TableCell><code>{def}</code></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>

            <CodeBlock title="Exemplo de arquivo .env" file="backend/.env">
{`DATABASE_URL=postgresql://postgres:postgres@db:5432/appdb
SECRET_KEY=your-super-secret-key-change-in-production
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30

BACKEND_HOST=0.0.0.0
BACKEND_PORT=8000

REDIS_URL=redis://localhost:6379/0

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_TLS=True
SMTP_USER=your-email@gmail.com
SMTP_PASSWORD=your-app-password

LOG_LEVEL=INFO
ENVIRONMENT=development
DEBUG=True`}
            </CodeBlock>
          </AccordionDetails>
        </Accordion>

      </Box>

      <Box sx={{ mt: 4, pt: 2, borderTop: '1px solid rgba(0,0,0,0.06)' }}>
        <Typography variant="caption" color="text.secondary">
          Documentação do sistema dash_visao — CFO / CROs
        </Typography>
      </Box>
    </PageContainer>
  );
};

export default Documentacao;
