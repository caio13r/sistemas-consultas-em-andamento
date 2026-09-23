import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Typography, Box, Paper, Grid, Card, CardContent, CardActionArea,
  CircularProgress, Divider, alpha, Chip, LinearProgress, Tooltip,
  Skeleton,
  Fade,
  keyframes,
} from '@mui/material';
import {
  People as PeopleIcon,
  Business as BusinessIcon,
  Search as SearchIcon,
  Assessment as ReportIcon,
  Map as MapIcon,
  TrendingUp as TrendingIcon,
  PersonSearch as PersonSearchIcon,
  Gavel as GavelIcon,
  BarChart as ChartIcon,
  Description as DescriptionIcon,
  HowToVote as VoteIcon,
  Storage as StorageIcon,
  Badge as BadgeIcon,
  AccessTime as ClockIcon,
  ArrowForward as ArrowIcon,
  Timeline as TimelineIcon,
  GroupWork as GroupWorkIcon,
  Speed as SpeedIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface DashboardStats {
  total_users: number;
  active_users: number;
  active_today: number;
  recent_activity_7d: number;
}

interface DB3Stats {
  profissionais_ativos: number | null;
  empresas_ativas: number | null;
  profissionais_por_categoria: { categoria: string; total: number }[];
  profissionais_por_uf: { uf: string; total: number }[];
}

interface RegionStats {
  [region: string]: { profissionais: number; empresas: number };
}

interface ActivityChart {
  por_dia: { dia: string; total: number }[];
  top_modulos: { modulo: string; total: number }[];
  usuarios_por_dia: { dia: string; total: number }[];
}

const quickLinks = [
  { path: '/consulta-integrada', title: 'Visão integrada', desc: 'Busca unificada de profissionais', icon: <PersonSearchIcon />, perm: 'view_consulta_integrada', color: '#7A1E26' },
  { path: '/consulta-identidade', title: 'Identidade Profissional', desc: 'Carteiras e registros', icon: <BadgeIcon />, perm: 'view_consulta_identidade', color: '#2F7D4F' },
  { path: '/consulta-rfb', title: 'Visão RFB', desc: 'Receita Federal do Brasil', icon: <SearchIcon />, perm: 'view_consulta_rfb', color: '#1E4A7A' },
  { path: '/consulta-fiscalizacao', title: 'Fiscalização', desc: 'Processos e ações fiscais', icon: <GavelIcon />, perm: 'view_consulta_fiscalizacao', color: '#5C1519' },
  { path: '/consulta-auditorias', title: 'Auditorias', desc: 'Gestão de auditorias', icon: <DescriptionIcon />, perm: 'view_consulta_auditoria', color: '#B88A56' },
  { path: '/consulta-estatistica', title: 'Estatísticas', desc: 'Dados e indicadores', icon: <ChartIcon />, perm: 'view_consulta_estatistica', color: '#1E4A7A' },
  { path: '/relatorios-diversos', title: 'Relatórios', desc: 'Relatórios gerenciais', icon: <ReportIcon />, perm: 'view_relatorios_diversos', color: '#6D6E71' },
  { path: '/eleicoes-regionais', title: 'Eleições', desc: 'Eleições dos CROs', icon: <VoteIcon />, perm: 'view_eleicoes_regionais', color: '#9A2832' },
  { path: '/tabelas-centralizadas', title: 'Tabelas Centralizadas', desc: 'Tabelas de referência', icon: <StorageIcon />, perm: 'view_tabelas_centralizadas', color: '#2F7D4F' },
];

/* ── Shimmer animation ── */
const shimmer = keyframes`
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
`;

const shimmerSx = {
  '&::after': {
    content: '""',
    position: 'absolute',
    top: 0, left: 0, right: 0, bottom: 0,
    background: 'linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.4) 50%, transparent 100%)',
    backgroundSize: '200% 100%',
    animation: `${shimmer} 1.8s ease-in-out infinite`,
  },
};

const ShimmerSkeleton = ({ ...props }: React.ComponentProps<typeof Skeleton>) => (
  <Skeleton
    {...props}
    sx={{
      ...props.sx,
      position: 'relative',
      overflow: 'hidden',
      bgcolor: alpha('#7A1E26', 0.06),
      ...shimmerSx,
    }}
  />
);

/* Paleta harmônica — cores variadas mas que combinam entre si */
const catBarColors = ['#7A1E26', '#2F7D4F', '#1E4A7A', '#9A2832', '#B88A56'];
const ufBarColors = [
  '#7A1E26', '#2F7D4F',
  '#1E4A7A', '#9A2832',
  '#B88A56', '#5C1519',
  '#6D6E71', '#2F7D4F',
  '#D4A574', '#1E4A7A',
];

/* ── Friendly module names ── */
const moduleName = (path: string): string => {
  const map: Record<string, string> = {
    '/api/consulta-integrada': 'Visão integrada',
    '/api/consulta-identidade': 'Identidade Profissional',
    '/api/consulta-rfb': 'Visão RFB',
    '/api/consulta-fiscalizacao': 'Fiscalização',
    '/api/consulta-auditorias': 'Auditorias',
    '/api/consulta-estatistica': 'Estatísticas',
    '/api/relatorios': 'Relatórios',
    '/api/eleicoes': 'Eleições',
    '/api/tabelas-centralizadas': 'Tabelas Centralizadas',
    '/api/export': 'Exportação',
    '/api/menu': 'Menu',
    '/api/users': 'Usuários',
  };
  for (const [prefix, name] of Object.entries(map)) {
    if (path.startsWith(prefix)) return name;
  }
  return path.replace('/api/', '').split('/')[0] || path;
};

/* ── Mini line chart (SVG) ── */
const MiniLineChart = ({ data, color, height = 120 }: { data: { label: string; value: number }[]; color: string; height?: number }) => {
  const [hoverIdx, setHoverIdx] = useState<number | null>(null);
  const max = Math.max(...data.map(d => d.value), 1);
  const total = data.reduce((sum, d) => sum + d.value, 0);
  const padX = 28;
  const padTop = 20;
  const padBot = 6;
  const w = 300;
  const chartH = height - padTop - padBot;
  const stepX = (w - padX * 2) / Math.max(data.length - 1, 1);

  const points = data.map((d, i) => ({
    x: padX + i * stepX,
    y: padTop + chartH - (d.value / max) * chartH,
    ...d,
  }));

  const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ');
  const areaPath = `${linePath} L${points[points.length - 1].x},${padTop + chartH} L${points[0].x},${padTop + chartH} Z`;

  // Grid lines (3 horizontal)
  const gridLines = [0, 0.5, 1].map(f => padTop + chartH - f * chartH);
  const gridLabels = [0, Math.round(max / 2), max];

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1, mb: 1.5 }}>
        <Typography variant="h5" sx={{ fontWeight: 800, color, letterSpacing: '-0.02em' }}>
          {total.toLocaleString('pt-BR')}
        </Typography>
        <Typography variant="caption" color="text.disabled" sx={{ fontWeight: 600 }}>
          total no período
        </Typography>
      </Box>
      <Box sx={{ width: '100%', position: 'relative' }}>
        <svg viewBox={`0 0 ${w} ${height}`} width="100%" height={height} style={{ overflow: 'visible' }}
          onMouseLeave={() => setHoverIdx(null)}>
          {/* Grid */}
          {gridLines.map((y, i) => (
            <g key={i}>
              <line x1={padX} y1={y} x2={w - padX} y2={y} stroke="#e0e0e0" strokeWidth={0.8} strokeDasharray={i > 0 ? '4 3' : 'none'} />
              <text x={padX - 6} y={y + 3} textAnchor="end" fontSize="8" fill="#999" fontWeight="600" fontFamily="inherit">
                {gridLabels[i].toLocaleString('pt-BR')}
              </text>
            </g>
          ))}
          {/* Area fill */}
          <defs>
            <linearGradient id={`grad-${color.replace('#', '')}`} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={color} stopOpacity={0.7} />
              <stop offset="100%" stopColor={color} stopOpacity={0.1} />
            </linearGradient>
          </defs>
          <path d={areaPath} fill={`url(#grad-${color.replace('#', '')})`} />
          {/* Line */}
          <path d={linePath} fill="none" stroke={color} strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" opacity={0.85} />
          {/* Hover vertical dashed line */}
          {hoverIdx !== null && (
            <line
              x1={points[hoverIdx].x} y1={padTop}
              x2={points[hoverIdx].x} y2={padTop + chartH}
              stroke={color} strokeWidth={1.5} strokeDasharray="4 3" opacity={0.85}
            />
          )}
          {/* Dots + labels + hover areas */}
          {points.map((p, i) => (
            <g key={i}
              onMouseEnter={() => setHoverIdx(i)}
              style={{ cursor: 'pointer' }}>
              {/* Invisible wider hit area */}
              <rect x={p.x - stepX / 2} y={padTop} width={stepX} height={chartH} fill="transparent" />
              <circle cx={p.x} cy={p.y} r={hoverIdx === i ? 6 : 4} fill="#fff" stroke={color}
                strokeWidth={3} style={{ transition: 'r 0.15s ease' }} />
              {p.value > 0 && (
                <text x={p.x} y={p.y - 10} textAnchor="middle" fontSize="9" fill={color} fontWeight="700" fontFamily="inherit">
                  {p.value.toLocaleString('pt-BR')}
                </text>
              )}
            </g>
          ))}
        </svg>
      </Box>
    </Box>
  );
};

/* ── Animated count-up hook ── */
const useCountUp = (target: number | null, duration = 1400) => {
  const [display, setDisplay] = useState(0);
  const rafRef = useRef<number>(0);
  const startedRef = useRef(false);

  useLayoutEffect(() => {
    if (target == null || target === 0) {
      setDisplay(0);
      return;
    }

    // Always animate from 0 on first trigger
    if (!startedRef.current) {
      setDisplay(0);
      startedRef.current = true;
    }

    const startTime = performance.now();
    const animate = (now: number) => {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // ease-out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      setDisplay(Math.round(target * eased));
      if (progress < 1) {
        rafRef.current = requestAnimationFrame(animate);
      }
    };

    rafRef.current = requestAnimationFrame(animate);
    return () => cancelAnimationFrame(rafRef.current);
  }, [target, duration]);

  return display;
};

/* ── Inline count-up component ── */
const CountUp = ({ value, duration = 1200 }: { value: number; duration?: number }) => {
  const display = useCountUp(value, duration);
  return <>{display.toLocaleString('pt-BR')}</>;
};

/* ── Stat Card ── */
const StatCard = ({ icon, value, label, color, loading: isLoading, subtitle }: {
  icon: React.ReactElement; value: string | number | null; label: string;
  color: string; loading: boolean; subtitle?: string;
}) => {
  const numericValue = value != null ? Number(value) : null;
  const animated = useCountUp(isLoading ? null : numericValue);

  return (
    <Paper sx={{
      p: 0, position: 'relative', overflow: 'hidden',
      border: '1px solid', borderColor: 'divider',
      borderRadius: 3,
      transition: 'all 0.3s cubic-bezier(0.4,0,0.2,1)',
      '&:hover': {
        boxShadow: `0 8px 28px ${alpha(color, 0.15)}`,
        transform: 'translateY(-2px)',
        borderColor: alpha(color, 0.25),
      },
    }} elevation={0}>
      {/* Top accent bar */}
      <Box sx={{
        height: 4, width: '100%',
        background: `linear-gradient(90deg, ${color}, ${alpha(color, 0.7)})`,
      }} />

      <Box sx={{ p: 2.5 }}>
        {/* Icon + subtitle row */}
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
          <Box sx={{
            width: 44, height: 44, borderRadius: 2.5, flexShrink: 0,
            background: `linear-gradient(135deg, ${alpha(color, 0.12)} 0%, ${alpha(color, 0.06)} 100%)`,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            border: `1px solid ${alpha(color, 0.1)}`,
          }}>
            {React.cloneElement(icon, { sx: { fontSize: 22, color } })}
          </Box>
          {subtitle && (
            <Chip
              label={subtitle}
              size="small"
              sx={{
                height: 22, fontSize: '0.6rem', fontWeight: 700,
                bgcolor: alpha(color, 0.08), color: alpha(color, 0.8),
                letterSpacing: '0.02em',
                '& .MuiChip-label': { px: 1 },
              }}
            />
          )}
        </Box>

        {/* Value */}
        <Typography sx={{
          fontWeight: 800, color, lineHeight: 1,
          letterSpacing: '-0.03em', fontVariantNumeric: 'tabular-nums',
          fontSize: { xs: '1.75rem', sm: '2rem' },
        }}>
          {isLoading ? <ShimmerSkeleton width={100} height={36} /> : (numericValue != null ? animated.toLocaleString('pt-BR') : '-')}
        </Typography>

        {/* Label */}
        <Typography variant="body2" sx={{
          fontWeight: 600, mt: 0.75, color: 'text.secondary',
          fontSize: '0.8125rem',
        }}>
          {label}
        </Typography>
      </Box>
    </Paper>
  );
};

/* ── Brazil Region Map — real state-level SVG paths ── */
const regionColors: Record<string, string> = {
  Norte: '#2F7D4F',
  Nordeste: '#1E4A7A',
  'Centro-Oeste': '#B88A56',
  Sudeste: '#7A1E26',
  Sul: '#9A2832',
};

const statesByRegion: Record<string, { uf: string; d: string }[]> = {
  Norte: [
    { uf: 'RR', d: 'M113.18,24.107c-0.972-2.753-7.861-5.889-6.999-8.984c0.068-0.232,13.229,6.053,12.79,2.808c0.398,1.329,1.219,1.889,2.439,1.685c1.889-1.301,7.148,4.204,8.216,1.889c0.438-0.959-1.657-3.753,0.74-3.848c1.026,0.438,1.534,0.164,1.52-0.822c0.835-1.752,3.575,0.219,4.793,0.083c0.767-1.056,10.625-3.026,9.037-5.094c1.37,0.438,4.574,0.808,4.63-1.547c4.546-2.054,1.15-4.409,2.644-6.354c2.177-2.82,9.791,0.809,7.327,5.738c-1.972,3.93,7.121,4.027,5.724,9.366c-0.452,1.686-2.479,2.724-3.423,3.971c-1.179,1.546-1.836,9.243-1.356,11.53c1.041,4.889,3.231,8.695,6.134,12.16c1.712,2.027,5.614,2.261,5.724,4.369c0.164,2.945,1.165,6.177,0.329,9.092c-1.547,5.424-36.618,30.471-36.618,30.471s-12.517-52.736-20.335-54.063C115.261,36.417,111.523,25.682,113.18,24.107z' },
    { uf: 'AP', d: 'M225.198,39.089c3.274,1.165,3.985-1.315,6.572-1.74c3.616-0.603,5.683,2.725,9.037,2.067c4.055-0.78,7.093-8.025,7.314-11.598c4.492-3.534,5.503-11.258,9.42-14.68c6.055,4.258,6.11,15.788,7.589,22.485c-0.164,0.083,6.57,7.998,7.944,8.682c3.396,1.657,3.366,6.203,0.078,9.34c-3.777,3.587-7.449,34.275-7.449,34.275h-46.489c0,0,0.932-50.366,0-51.449C221.814,36.458,223.334,38.417,225.198,39.089z' },
    { uf: 'AM', d: 'M10.078,136.412c1.15-4.972,4.258-10.394,8.215-13.105c4.41-3.027,7.656-5.71,13.105-6.082c2.165-0.149,10.216-5.75,11.983-2.984c3.711,5.765,4.998-3.739,5.574-7.025c1.726-9.667,3.697-19.322,4.86-29.086c-0.342-1.356-2.013-6.231-2.833-7.163c-1.453-1.616-4.287-2.122-4.768-4.544c-0.272-1.452-0.574-7.258,1.109-8.121c3.494-1.768,6.547-0.042,9.737-0.89c-2.561-4.053,0.302-4.327-5.532-5.135c-3.438-0.466-3.971-2.466-2.738-6.368c1.053-3.3,15.898-1,19.088-1.396c-1.534,0.178-1.11-2.479-0.042-2.616c1.274-0.165,1.576,2.684,3.165,0.998c1.286-1.395,3.189-2.915,4.6-3.751c2.438-1.45,4.533,8.217,4.465,9.833c-0.041,0.78-0.137,2.438,1.177,2.246c3.012-0.466,4.219,2.849,7.273,4.231c3.778,1.713,3.929-1.355,7.023-2.068c4.301-0.985,0.711,3.396,2.383,3.793c1.589,0.385,3.806-4.969,4.821-5.572c0.93-0.533,3.725-0.753,4.846-1.602c3.013-2.245,1.933-1.686,3.492-1.206c3.478,1.041,2.233-8.367,6.491-7.066c1.822-0.466,3.643-2.34,5.533-2.423c1.041-0.043,6.066,2.287,6.544,3.147c0.589,1.465,0.316,2.795-0.793,3.986c1.575,1.425,2.698,3.149,3.355,5.162c0.904,2.862-1.286,6.807,0.588,9.299c-0.22,6.655,4.808,7.887-0.396,12.597c0.192-0.178,6.711,7.067,7.121,8.039c0.971-0.711,4.066,0.849,4.381,1.535c-1.658-3.629,0.547-17.09,6.628-10.915c7.203,7.327,5.491-3.615,9.148-8.627c2.834-3.875,14.597-3.136,14.077,3.246c-1.082,3.273,6.271,14.256,9.667,11.436c2.26,5.737,6.889,4.285,10.407,8.051c5.094,5.464,4.37,3.396,11.313,2.848c-2.259,3.602-3.425,4.808-5.272,8.86c-3.149,6.862-6.15,13.776-9.204,20.678c-2.437,5.505-14.843,23.471-11.105,28.442c4.806,6.395,9.339,30.183,11.324,29.934c-6.162-0.26-48.079-10.625-51.652-8.105c-1.453,1.013-53.626,10.503-55.9,10.819c-6.369,0.875-18.09-7.272-23.719-10.136c-8.601-4.381-16.61-8.981-26.088-11.05c-10.282-2.259-20.635-4.793-29.878-10.011C4.121,145.766,12.433,144.779,10.078,136.412z' },
    { uf: 'PA', d: 'M173.378,50.619c2.259,2.63,5.629-4.478,7.901-3.82c3.19,0.918,1.478-1.108,5.026-1.752c1.931,0.806,3.096,0.273,3.519-1.631c0.535-1.26,1.453-1.726,2.725-1.384c1.768-1.684,13.558,3.603,14.68,0.384c0.629-1.821-4.287-5.709-0.302-6.997c1.643-0.533,6.012,0.808,8.75-0.068c3.986-1.288,4.876,2.684,4.382,6.066c0.631,3.587,13.145,5.766,12.982,7.97c3.589-1.518,5.354,12.763,7.105,14.447c0.357,4.26,6.304,8.585,7.07,12.544c0.628,3.396,7.065,3.616,8.213,0.095c2.578-8.133,9.696-10.022,13.475-16.651c4.603-8.038,3.725,3.752,8.955,1.067c2.11,0.411,2.876,3.629,4.574,4.724c3.18,2.027,7.779,0.974,10.572,3.013c-4.192,4.382,8.188,3.752,9.231,3.875c4.682,0.575,8.104,2.383,11.855,3.629c-0.164-0.069,4.792,0.52,5.178,1.245c2.026,3.767-4.904,19.214-6.382,21.486c-1.121,1.713-2.932,4.985-3.727,6.834c-0.902,2.026-4.764,7.313-4.655,9.229c-1.888,0.972-2.248,4.835-5.012,4.328c-3.096,3.026-8.187,4.999-10.27,8.956c2.057,0.781,8.325,1.041,5.311,4.272c-0.821,0.877-1.094,5.533-1.615,6.833c-0.575,1.384-4.464,4.779-6.108,5.34c-4.107,1.426-2.736,4.135-4.271,7.655c-0.933,2.054-0.546,3.491,1.756,4.339c-0.083,2.835-0.988,5.575-2.385,7.998c-3.041,5.245-9.009,9.818-10.079,16.27c-3.261,3.408-87.066-1.22-87.464-2.644c-1.423-5.012,1.508-24.006-2.808-27.88c-0.19-2.082-29.893-6.299-30.714-8.081C150.016,140.479,173.173,58.561,173.378,50.619z' },
    { uf: 'TO', d: 'M289.558,235.641c16.104,0.575,44.973-31.647,44.835-45.259c-0.136-13.612-17.227-58.446-22.349-66.088c-5.122-7.628-37.905,2.506-37.905,2.506S234.852,233.695,289.558,235.641z' },
    { uf: 'RO', d: 'M83.34,180.232c0.931-1.574,5.341-4.668,6.312-4.656c1.355-0.067,2.671,0.138,3.958,0.603c3.012,1.44,2.039-1.135,5.341-0.123c-1.274-2.287,3.793-2.943,2.86-0.315c3.068,0.247,2.725-4.683,6.668-5.12c4.438-0.508,5.054-0.646,7.122-4.534c0.135-0.246,2.628-5.519,2.752-5.025c2.191-6.491,14.585-0.878,15.638,3.355c0.397,1.615,1.834,3.137,3.642,4.369c1.246,0.862,6.327-3.999,6.134,1.314c-0.78,1.274,26.663,7.656,30.005,19.282c3.82,13.338-16.421,32.167-18.173,34.043c-4.464,1.191-2.039,1.726-6.6,0.15c-2.574-0.875-6.422,0.986-9.08,0.289c-2.409-0.645-3.041-3.957-5.86-4.683c-3.055-0.78-5.423-1.795-7.654-3.93c-4.041-3.876-8.983-2.645-14.475-3.808c-1.835-0.083-6.053-6.779-7.874-5.327c-1.821-0.438-5.381-9.094-3.397-11.204c0.124-1.67-0.26-3.204-1.163-4.627c-0.986-2.644,1.041-5.026,0.863-7.806c-0.384-6.081-1.028-1.986-3.382-1.903C94.336,180.686,85.957,181.671,83.34,180.232z' },
    { uf: 'AC', d: 'M3.656,148.545c12.557,7.544,27.524,8.367,41.082,13.2c12.802,8.065,27.278,12.845,40.616,19.872c-2.834,1.205-7.587,4.382-9.983,6.395c-2.93,2.45-1.3,2.04-4.628,1.957c-2.93-0.069-3.957,4.615-7.203,5.259c-2.999,0.603-7.161-1.958-10.995-1.697c-1.905,0.136-11.969-0.056-12.64,0.603c0.313-3.642-0.385-7.299-0.165-10.941c0.096-1.439,1.998-6.533,1.245-7.451c-6.82,3.149-8.339,7.19-16.733,7.013c-2.136-0.042-2.562-2.492-3.081-4.001c-1.247-3.572-7.218-3.422-10.559-3.778c6.299-3.41-3.107-11.9-5.216-15.679c-0.52-0.918-3.588-4.655-3.629-5.957C1.642,150.174,6.612,151.968,3.656,148.545z' },
  ],
  Nordeste: [
    { uf: 'MA', d: 'M288.845,127.827c4.108-2.726,31.195-48.985,31.386-50.395c1.235,0.397,6.084,7.435,7.562,5.025c0.493,0.013-0.328,2.15-0.547,2.396c-0.054-0.135,2.189-2.286,2.52-2.436c0.521-0.233,1.948,1.903,3.451-0.726c5.642,1.575,1.314,14.31,9.121,11.694c-1.147,0.384,1.452,0.74,0.848,1.905c5.095-6.587,8.488-0.027,15.337,1.491c2.025,0.466,6.243,0.575,8.162,0.207c3.808-0.823-2.082,6.847-2.082,6.887c-1.369,2.986-5.041,1.713-6.818,5.683c-0.684,1.549-3.506,4.327-3.042,6.148c0.494,1.781,2.081,2.863,0.274,4.629c0.603,2.793,3.066,7.109-0.385,9.12c-4.601,4.383,2.304,7.52,1.316,11.598c-0.9,3.726-6.244,5.725-9.147,2.78c-4.847-0.11-6.872,3.821-10.406,6.45c-2.74,2.041-8.793,2.493-10.327,5.642c-1.918,3.929-3.699,8.763-5.341,12.79c-1.699,4.204,6.383,18.762-4.328,15.611c-0.932-0.273-3.396-4.725-3.396-5.738c-0.081-3.739-2.738-4.176-4.821-7.477c0.356-3.025,2.466-6.929,4.766-8.052c3.342-1.63,1.919-6.629-2.466-4.465c-3.505,1.726-4.709-2.794-6.958-5.287c0.548,0.59-3.064-4.696-3.146-3.697c0.19-1.89,2.876-5.833,3.341-8.448c0.575-3.259,0.52-6.764-0.521-10.105c-0.63-2.068-4.656-4.521-6.518-4.437c-1.289,0.287-2.443,0-3.427-0.878C290.983,125.675,290.983,128.044,288.845,127.827z' },
    { uf: 'PI', d: 'M320.781,185.478c2.465-5.149-7.505-20.801-7.505-20.801s47.354-65.868,54.285-66.841c0.299-0.042,6.243,1.768,6.463,2.219c0.438,0.863-0.821,5.244-0.685,6.587c0.275,2.629,2.879,6.587,2.328,8.684c-1.15,4.736-1.863,6.134,1.369,9.901c2.794,3.245,0.325,10.16,2.544,14.269c-1.778,4.23,4.768,3.656,3.943,7.613c-0.655,3.163-5.424,7.655-1.176,10.312c0.274,4.642-4.685,4.983-6.79,7.818c-2.631,2.835-5.535,5.013-7.999,7.888c-0.55,0.671-8.821,4.096-9.998,4.082c0.302-0.301-17.665-6.449-11.967,2.354c2.463,3.808-1.505,5.56-3.177,8.778c-0.633,2.164-5.836,0.958-7.836,3.205C328.176,198.748,327.409,180.727,320.781,185.478z' },
    { uf: 'CE', d: 'M372.379,104.409c0.437-1.368,2.961-3.627,1.043-5.025c12.106-1.328,17.581-0.849,27.66,6.723c4.026,3.054,6.822,5.574,10.571,9.147c1.317,1.273,7.614,4.313,7.914,6.164c-0.054-0.316-5.396,3.696-5.997,5.217c-1.066,2.684-2.659,6.093-4.3,8.298c0.025-0.055-6.903,3.957-3.532,4.217c-4.41,3.821-1.015,8.135-0.797,11.517c0.196,2.767-4.38,7.587-6.765,5.422c-2.244-1.999-3.998-5.711-7.779-5.094c-1.998,0.329-5.476,2.189-7.612,0.479c-2.52-2.054,3.669-5.162-0.545-7.354c-6.987-3.615-1.264-15.393-6.684-20.239c-3.504-3.136,1.753-7.313,0.109-10.749C374.952,111.68,373.694,105.244,372.379,104.409z' },
    { uf: 'RN', d: 'M404.698,138.795c2.383-4.027,6.574-6.123,8.49-11.149c1.973-5.107,3.834-5.818,8.764-4.642c5.041,1.207,9.339,0.837,14.57,1.671c7.534,1.193,6.848,10.968,9.206,16.516c-1.919,1.096-13.972,0.521-15.064-1.657c-1.041-2.067-2.904,7.107-5.094,7.3c1.532-5.847-12.654,1.78-5.424-8.683c2.545-3.67-6.302-0.808-6.711,0.725C410.121,144.013,407.217,139.151,404.698,138.795z' },
    { uf: 'PB', d: 'M401.575,141.096c2.081-3.081,16.791-6.82,19.117-4.616c0,1.918,7.259,1.686,10.133,2.712c-0.492,3.038,12.652,1.533,14.408,2.259c1.421,0.589,3.833,11.983,1.421,12.202c-0.874-1.124-2.083-1.739-3.586-1.835c-2.957-0.027-2.546,1.863-4.383,3.108c-2.626,1.767-6.571,1.917-9.558,2.109c-0.162,1.232-3.943,4.438-5.259,4.916c-3.122,1.149-2.657-2.727-5.095-3.602c0.713-1.124,4.082-5.203,3.725-6.205c-1.423-3.846-12.051,5.52-14.981,3.506c-1.396-0.973-6.218,1.493-3.476-2.588C405.574,150.776,400.398,142.889,401.575,141.096z' },
    { uf: 'PE', d: 'M373.011,167.238c2.709-0.795,6.218-14.106,8.325-15.106c4.136-1.986,17.255-1.437,17.8,4.903c-0.437-0.068,8.189-2.273,7.479-1.466c1.7-0.711,10.518-4.723,12.599-4.82c0.274-0.013,4.603,0.905,3.068,2.315c-0.464,0.439,4.219,3.698,10.789,3.45c4.66-0.176,5.179-3.436,8.627-4.409c5.89-1.67,4.737,3.698,5.589,6.943c-1.182,2.684-1.646,5.586-2.74,8.285c-1.533,3.792-9.804,9.791-13.39,12.119c-7.287,4.778-21.802-4.067-22.762-5.67c-0.602-0.985-2.55-5.121-3.178-5.107c-0.629,0.356-1.04,0.861-1.287,1.519c-0.904-0.013-7.256-3.533-7.502-4.655c-4.769-1.151-5.425,6.108-8.957,6.19c0.219,0.108-8.244,6.681-7.506,3.314C383.556,170.4,374.241,168.566,373.011,167.238z' },
    { uf: 'AL', d: 'M413.953,169.018c3.78,3.313,9.424,5.505,12.547,5.491c3.229-0.013,5.009-3.328,7.421-4.794c1.177-0.712,10.297-1.93,9.174,1.042c-1.807,4.848-7.122,8.585-10.024,12.789c-2.792,2-3.423,7.093-6.354,1.864c-3.259,0.424-3.722-4.424-6.957-4.477c-3.668-2.261-7.998-3.769-11.201-6.342C410.615,172.646,412.751,171.359,413.953,169.018z' },
    { uf: 'SE', d: 'M408.561,191.735c0.521-1.505,2.465-0.725,3.533-0.794c2.273-0.164,0.494-2.738,1.095-3.778c2.026-3.793-2.738-5.999-1.998-10.408c4.024,1.931,9.448,3.397,12.408,6.89c1.343,1.533,5.504,2.656,5.832,4.847c-6.822,0.384-6.901,8.819-11.942,11.572C413.545,202.212,407.055,193.721,408.561,191.735z' },
    { uf: 'BA', d: 'M313.276,197.775c2.084-2.739,3.506-7.012,6.464-8.764c1.641-0.973,3.232-4.684,4.271-5.163c2.304-1.014,12.161-25.143,20.706-22.513c1.095,0.342,29.881,3.478,32.153,7.532c2.246-0.506,17.582-8.804,25.829-4.999c9.172,4.246,11.225,20.679,11.2,20.843c0.107,0.328-0.823,5.765-0.985,5.929c-1.15,1-5.258-0.807-4.22,2.138c1.317,3.751,5.094,10.583,9.97,6.613c-3.669,6.574-6.846,16.022-13.966,17.747c-5.808,1.411-4.605,13.421-5.178,18.037c-0.465,3.75,0.192,8.448,1.014,12.117c1.148,4.959-0.821,8.6-1.808,13.42c-0.822,4.162-0.219,8.299-0.987,12.297c-0.271,1.286-4.407,5.723-5.559,7.148c-1.616-1.426-63.952-37.248-73.1-36.265c1.149-3.738,2.438-9.559-0.741-12.723c-8.625-8.572-0.135-19.335-0.162-19.432c-0.546-1.725-5.396-6.079-0.026-7.175c-3.175,0.959-1.944-4.027,0.875-3.012C316.726,200.733,314.044,200.527,313.276,197.775z' },
  ],
  'Centro-Oeste': [
    { uf: 'MT', d: 'M142.237,173.962c4-0.316-1.888-6.452,5-5.738c7.914,0.808,16.295,0.328,24.279,0.218c1.629-0.013,8.902,1.288,7.395-1.833c-1.192-2.453,1.821-6.425,0.425-9.725c2.027-0.864,1.289-3.807,2.629-5.107c1.151-1.123,4.176,7.244,4.436,7.819c1.097,2.451,0.398,5.478,1.932,7.654c1.41,1.987,4.574,2.136,5.889,4.259c3.136,5.136,10.845,4.137,17.13,4.657c20.159,1.656,40.356,2.669,60.486,4.752c-3.48,7.763-3.999,14.912-5.122,22.552c-0.437,2.972,1.863,7.163-0.056,10.065c1.945,1.287,1.346,2.753,1.424,4.409c1.151,25.129-20.429,60.186-33.548,58.569c-10.914-1.369-45.3,0.058-46.928-3.396c-1.165-3.944-6.136-2.658-8.395-6.603c-2.301-4.051,0.684-6.299,0.737-10.242c-6.997,0.603-14.09-0.384-21.102-0.324c0.793-5.016-3.725-9.288-2.929-13.809c0.519-3.025,2.726-2.916,0.932-6.79c-1.206-2.589-0.261-4.247-0.699-6.382c-0.289-1.385-1.042-1.876-2.124-2.424c-2.931-1.493,1.246-2.48,2.056-3.644c1.726-2.465,3.299-11.394,6.545-11.612c1.219-1.999-1.781-3.643-1.465-5.56c-3.902-3.588,0.506-4.643,0.369-7.984c-0.151-3.627-9.654-3.944-12.256-3.751c-1.821,0.137-4.109,0.562-5.888-0.094c0.493-3.521-0.521-6.054-0.535-9.217c-0.014-2.286,1.288-5.177,0.835-7.45C143.581,176.618,141.937,174.714,142.237,173.962z' },
    { uf: 'GO', d: 'M237.768,270.519c0.628-2.904,1.835-7.396,4.709-8.766c1.015-1.644,1.754-5.147,2.275-5.586c2.408-2.247,3.889-3.783,6.63-4.656c3.723-1.205,3.338-5.342,4.846-8.165c1.504-2.845,4.736-1.15,5.942-3.382c1.479-2.834,0.741-6.161,2.189-8.874c2.902-5.531,1.862-17.363,8.656-20.567c-4.878,7.641,3.698,4.971,7.201,9.449c2.273,1.738,2.164-1.822,2.71-3.055c1.618-3.533,2.878,2.247,4.52-1.533c0.413,0.37,4.136,5.765,3.427,5.601c-0.029-0.931,0.326-1.408,1.037-1.438c0.108,0.534,0.274,1.013,0.602,1.452c-0.602-0.261,9.697-0.095,8.82,1.534c0.36-0.657-0.602-3.11,0.221-3.438c1.039-0.411,3.971,1.368,6.351,0.438c1.045-0.397,7.889-2.807,7.671-3.683c0.767,0.905,1.262,2.67,2.85,1.286c-2.632,2.274-2.576,4.466,1.258,3.821c-1.861,1.438-2.846,4.341-2.382,6.547c0.357,1.643,3.752,5.973,3.478,6.751c-1.78,0.315,0.602,5.438-2.325,6.078c-3.181,0.701-3.973-5.53-4.3,0.688c-0.164,1.48-1.097,1.67-2.768,0.576c-3.288,0.327-0.549,2.19-1.121,3.888c-0.988,2.902,2.792,6.437-2.411,6.764c-3.586,0.219-2.682,1.341-2.682-2.739c-0.028-4.573-12.054-3.643-10.218,0.521c-4.901,6.355,12.05-0.326,9.668,6.355c-1.313,3.752,15.83,28.211,10.406,25.416c-1.944-0.986-50.804,10.271-49.982,12.105c-5.012-2.136-11.804-7.941-17.391-8.162c-0.438-2.189-3.618-1.284-5.095-1.533c-3.724-0.604,1.04-3.231,0.22-4.109c-1.89-1.916-4.382,1.756-3.588-3.012C239.602,274.627,237.055,273.038,237.768,270.519z' },
    { uf: 'DF', d: 'M292.461,246.197c0,0,12.929-2.903,14.188,0c1.233,2.903,0.659,10.683-1.424,11.504c-2.08,0.849-14.296-1.806-14.023-3.313C291.503,252.853,292.461,246.197,292.461,246.197z' },
    { uf: 'MS', d: 'M183.198,294.536c2.136-4.464,3.177-9.394,5.312-13.61c1.712-3.344-4.067-7.587-2.423-9.807c0.027-0.026,2.738,3.641,3.917,3.725c3.204-1.534,4.807-2.272,6.984-5.228c2.615-3.59,10.832-3.014,14.051-0.305c1.259,1.041,3.068,2.107,4.668,2.574c3.163,0.934,5.889-3.013,8.559-0.873c3.724,2.982,4.626-1.862,7.86-3.509c1.945-1.012-1.768,8.465-2.244,7.781c2.463,0.959,4.285,0.901,6.82,0.959c3.504,0.081,1.805,1.205,2.436,3.339c0.466,1.564,28.948-5.997,29.416,0.578c0.302,3.837-0.987,61.813-0.987,61.813s-39.532,5.533-41.602,5.286c-3.889-0.492-3.587-3.231-8.063-0.933c-2.028,0.329-6.012,1.205-5.177-2.409c-2.013-4.354-0.111-14.625-4.849-17.088c-1.206-0.659-7.092-2.36-7.504-1.945c-1.699,1.777-3.739,1.562-6.121,1.121c-2.904,0.027-5.629-1.614-8.243-1.203c-4.178,0.656-0.603-2.986-1.645-3.535c0.932-2.847,1.411-9.912,0.453-11.856c-0.165-0.331-3.52-7.232-2.547-8.108C186.306,297.688,182.334,299.415,183.198,294.536z' },
  ],
  Sudeste: [
    { uf: 'MG', d: 'M262.881,297.305c-1.696-5.094,15.531-19.882,18.844-13.421c5.531-7.367,15.886,1.588,19.773-3.944c0.988-1.367,3.015-1.453,3.725-2.957c0.326-0.711-0.493-2.793-0.056-3.888c1.369-3.398-4.873-2.355-0.109-6.603c4.547-4.053-1.917-4.739-1.204-8.186c0.957-4.604,1.807-4.713,5.613-6.027c1.943-0.688,0.906-8.272,0.083-8.52c-0.108-2.699,1.974-2.546,3.782-1.617c2.188-0.135-0.276-3.695,0.957-4.243c-0.357,0.151,5.559,1.999,5.724,2.055c0.986,0.358-0.52,3.534-0.931,3.943c8.217-2.355,14.514-11.789,23.279-11.242c4.983,0.316-0.327,4.339,5.367,5.544c0.684,1.234,3.34-1.054,4.054-1.189c2.876-0.536,5.53,3.284,8.106,3.886c2.301,3.578,7.503,0.537,10.298,3.001c1.755,1.589,2.188,3.397,3.396,5.313c1.314,2.052,3.86-0.465,5.726-0.109c3.257,0.656,6.326,2.026,9.338,3.723c2.19,1.205,0.768,3.179-0.548,4.573c-0.765,0.796-3.259,6.165-2.627,5.643c-2.138,1.781-2.628-1.669-3.397,2.764c-0.628,3.674,0.164,4.714,3.149,7.015c4.901,3.229-6.765,3.12-6.71,3.504c0.22,0.601-2.846,41.96-3.835,42.179c-6.737,1.562-14.513,5.311-21.744,7.012c-12.736,2.985-24.295,3.778-29.471,4.656c0,1.452-5.367,6.872-8.518,1.259c0,0-3.041-7.285-2.821-7.229c0.105-0.027,2.138-5.506,2.244-6.137c0.768-3.504-5.042-0.765-5.749-2.188c-0.878-1.81-2.358-4.576-2.166-6.628c1.699-1.205,1.672-2.383-0.08-3.562c-1.04-1.095-1.205-2.303-0.521-3.672c-2.329-1.424-3.065-2.683-5.698-2.462c-1.479,0.138-4.055,3.668-5.506,0.629c0.878,2.108-4.188,0.769-5.094,1.56c-2.354-1.202-1.779,2.028-2.384,3.069c-0.137,0.22-1.014-2.904-1.065-2.961c-1.149-1.175-2.767,4.165-3.505-0.055c0.766-4.105-4.657-2.709-7.67-2.93c-4.708-0.353-5.53-1.613-9.858,0.631C262.993,300.562,262.336,299.274,262.881,297.305z' },
    { uf: 'ES', d: 'M367.119,308.834c1.044-1.999-0.298-5.451,1.841-6.326c3.697-1.453,3.858-0.467,5.941-4.49c0.767-1.563,3.999-5.807,2.848-7.835c-0.439-0.765-3.204-3.613-3.286-4.05c1.04-0.249,2.079-0.219,3.123,0.054c1.366-0.654-6.465-10.519,2.137-8.054c-1.204-0.655-1.535-1.365-0.932-2.135c4.358-0.138,13.856,0.027,12.845,6.738c-0.577,3.835,0.933,8.079-0.577,11.804c-0.218,0.576-5.861,8.954-5.831,8.954c0.985,3.289-5.18,5.808-6.054,8.165c-1.313,3.56-2.135,3.013-5.614,2.573c-1.64-0.274-3.202-0.768-4.736-1.451C368.819,311.297,369.424,309.055,367.119,308.834z' },
    { uf: 'RJ', d: 'M332.886,337.429c-1.26-2.768,8.409-4.795,7.89-6.71c-3.177-1.864-4.602,1.148-6.63-2.959c4.274-0.686,9.533-4.49,13.831-3.562c0.548-0.219,4.902-1.753,4.96,0.167c2.546-1.566,5.479-2.412,8.105-3.837c2.246-1.206,0.932-8.218,3.725-9.643c6.054-3.123,1.398,1.836,7.066,2.959c5.888,1.205,5.395,1.48,5.641,7.067c0.247,5.642-8.763,4.381-11.063,8.764c-1.039,1.999,1.698,5.368-3.368,4.903c-4.188-0.413-10.628,2.355-9.285-3.18c-1.039-0.08-1.861,0.301-2.464,1.124c0,0,0.105,2.767-0.74,2.741c-0.766-0.056-7.643,1.094-7.449,0.463c1.398-0.359,2.708-0.684,4.135-0.794c-1.667-0.713-2.957-1.839-4.901-0.142c0.465,0.195-4.227-0.086-3.379-0.113c-0.521,1.727-3.814,0.699-3.879,3.045C336.717,337.908,333.927,342.41,332.886,337.429z' },
    { uf: 'SP', d: 'M239.3,330.554c3.26-4.356,9.56-5.039,11.531-10.792c1.369-3.942,3.889-8.818,6.135-13.036c1.561-2.957,7.749-7.121,10.517-8.65c0.383-0.196,32.974-6.138,42.234-1.701c20.265,9.724,26.017,33.879,27.854,33.304c4.408-1.425,5.34,3.778,2.106,4.49c-1.754,0.413-6.519,1.479-6.49,3.399c0.027,3.448,0.521,1.615-2.931,3.639c-2.189-1.42-3.34,4.111-4.763,3.426c-4.271-2.244-6.958,2.96-9.258,1.918c-4.271-1.918-16.98,13.092-19.638,15.336c0.245-0.218-1.148-1.479-1.587-2.685c-0.466-1.369-2.658,0.385-4.025,0.082c-0.986-0.192,1.751-4.079-2.303-4.52c-1.369-0.164-3.753,0.303-4.929,0.084c-2.903-0.547,0.108-2.41-0.439-3.862c-1.067-2.986-3.013-4.931-3.751-7.779c-0.52-1.945,0.165-7.531-3.615-7.395c-0.848-2.956-6.628-1.451-9.066-1.862c-0.162,0.163-8.846-2.684-10.079-2.684c-1.616-0.029-6.791-3.396-7.121-0.274C247.982,330.386,239.876,331.21,239.3,330.554z' },
  ],
  Sul: [
    { uf: 'PR', d: 'M222.225,363.694c1.807-2.138,1.889-4.881,2.424-7.479c0.301-1.453,0.465-7.86,1.369-8.736c2.3-0.684,2.3-3.315,2.726-5.204c0.616-2.738,2.821-2.958,3.984-5.616c4.369-9.91,38.947-9.529,46.476-9.227c4.658,0.193,15.775,34.563,17.916,33.794c-1.728,2.19-5.754,8.929-8.41,8.984c-4.054,0.057-14.215,14.68-14.215,14.68s-37.329-12.05-40.287-11.285c-3.875-1.449-2.698-6.491-6.054-8.216C226.663,364.623,222.498,367.8,222.225,363.694z' },
    { uf: 'SC', d: 'M231.029,383.959c1.669-3.338-0.284-10.516,4.573-10.569c6.631-0.109,13.639,3.559,20.402,3.888c1.317,0.055,5.231,2.163,4.357-1.15c-1.095-4.164,3.945-1.863,5.67-3.179c2.274-1.724,8.187-4.106,11.311-1.367c1.423,1.809,20.05-5.395,13.284,3.946c-1.368,1.395,0.713,10.789,0.466,10.734c-3.449,4.438,1.726,11.666-5.096,15.334c-2.901,1.536-7.284,7.779-9.64,9.995C276.085,411.866,233.534,382.918,231.029,383.959z' },
    { uf: 'RS', d: 'M191.236,416.881c0.52-2.684,7.38-8.409,9.477-10.351c0.37-0.359,8.599-10.08,9.174-8.329c-1.301-3.89,2.781-1.589,3.917-4.819c0.26-0.521,7.04-4.821,7.109-4.795c1.436-0.191,6.721-3.695,7.421-3.257c1.204-2.028,8.927-1.479,8.653-0.824c1.165-0.38,2.284-0.877,3.326-1.479c0.221-0.821,22.459,7.533,24.319,11.531c2.523,5.34,12.217,2.822,13.15,5.563c0.106,0.275-5.809,9.339-3.89,9.173c-0.985,0.08,3.204-2.875,3.834,0.409c-2.793,3.619-4.6,7.834-6.571,11.944c-3.696,7.614-8.872,12.765-15.886,17.42c-7.394,4.902-7.339,11.941-13.257,17.693c-8.091,7.942-10.159-0.574-4.08-5.752c3.806-3.231-22.527-19.746-25.578-22.732c-1.918-1.862-2.384,0.274-4.219,1.15c-2.547,1.205-1.917-2.822-3.588-4.273c-2.3-1.999-4.793-5.479-7.737-6.68c-3.478-1.367-5.615,5.145-9.052,0.821C189.168,418.854,190.332,418.032,191.236,416.881z' },
  ],
};

/* Label positions for each region (approximate center) */
const regionLabels: Record<string, { x: number; y: number }> = {
  Norte: { x: 175, y: 110 },
  Nordeste: { x: 395, y: 195 },
  'Centro-Oeste': { x: 235, y: 265 },
  Sudeste: { x: 330, y: 310 },
  Sul: { x: 260, y: 395 },
};

const BrazilMap = ({ data, loading: isLoading }: { data: RegionStats | null; loading: boolean }) => {
  const [hovered, setHovered] = useState<string | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });
  const containerRef = useRef<HTMLDivElement>(null);

  const handleMouseMove = (e: React.MouseEvent) => {
    if (containerRef.current) {
      const rect = containerRef.current.getBoundingClientRect();
      setTooltipPos({ x: e.clientX - rect.left, y: e.clientY - rect.top });
    }
  };

  return (
    <Paper sx={{
      p: 3, border: '1px solid', borderColor: 'divider',
      position: 'relative', overflow: 'hidden',
    }} elevation={0}>
      <Box sx={{
        height: 4, width: '100%', position: 'absolute', top: 0, left: 0,
        background: 'linear-gradient(90deg, #7A1E26, #2F7D4F, #1E4A7A, #B88A56, #9A2832)',
      }} />
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 2, mt: 0.5 }}>
        <Box sx={{
          width: 36, height: 36, borderRadius: 2,
          bgcolor: alpha('#7A1E26', 0.08),
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>
          <MapIcon sx={{ fontSize: 20, color: '#7A1E26' }} />
        </Box>
        <Box>
          <Typography variant="h6" sx={{ fontFamily: "'Instrument Serif', Georgia, serif", fontWeight: 400, lineHeight: 1.2 }}>
            Distribuição por Região
          </Typography>
          <Typography variant="caption" color="text.disabled" sx={{ fontWeight: 500 }}>
            Passe o cursor sobre uma região para ver os detalhes
          </Typography>
        </Box>
      </Box>

      {isLoading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
          <ShimmerSkeleton variant="rounded" width={360} height={380} />
        </Box>
      ) : (
        <Box sx={{ display: 'flex', flexDirection: { xs: 'column', md: 'row' }, alignItems: 'center', gap: 3 }}>
          {/* Map */}
          <Box
            ref={containerRef}
            onMouseMove={handleMouseMove}
            sx={{ position: 'relative', flexShrink: 0, width: { xs: '100%', md: 420 }, maxWidth: 420, mx: 'auto' }}
          >
            <svg viewBox="-5 -5 470 475" width="100%" style={{ display: 'block' }}>
              {Object.entries(statesByRegion).map(([region, states]) => {
                const color = regionColors[region] || '#999';
                const isHovered = hovered === region;
                return (
                  <g key={region}>
                    {states.map(({ uf, d }) => (
                      <path
                        key={uf}
                        d={d}
                        fill={isHovered ? alpha(color, 0.25) : '#F0F0F0'}
                        stroke={isHovered ? color : '#BDBDBD'}
                        strokeWidth={isHovered ? 1.5 : 1}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        style={{
                          cursor: 'pointer',
                          transition: 'all 0.25s ease',
                        }}
                        onMouseEnter={() => setHovered(region)}
                        onMouseLeave={() => setHovered(null)}
                      />
                    ))}
                  </g>
                );
              })}
              {/* Region labels */}
              {Object.entries(regionLabels).map(([region, { x, y }]) => {
                const color = regionColors[region] || '#999';
                const isHovered = hovered === region;
                return (
                  <text
                    key={`label-${region}`}
                    x={x} y={y}
                    textAnchor="middle"
                    fontSize="11"
                    fontWeight="700"
                    fill={isHovered ? color : '#999'}
                    style={{ pointerEvents: 'none', transition: 'fill 0.25s ease', fontFamily: 'inherit' }}
                  >
                    {region}
                  </text>
                );
              })}
            </svg>

            {/* Tooltip */}
            {hovered && data && data[hovered] && (
              <Box sx={{
                position: 'absolute',
                left: tooltipPos.x + 16,
                top: tooltipPos.y - 10,
                bgcolor: '#0A0506',
                color: '#fff',
                borderRadius: 2,
                px: 2, py: 1.5,
                boxShadow: '0 8px 32px rgba(0,0,0,0.35)',
                pointerEvents: 'none',
                zIndex: 10,
                minWidth: 200,
                border: `2px solid ${alpha(regionColors[hovered] || '#fff', 0.5)}`,
              }}>
                <Typography sx={{ fontWeight: 800, fontSize: '0.9rem', color: regionColors[hovered], mb: 0.75 }}>
                  {hovered}
                </Typography>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
                  <PeopleIcon sx={{ fontSize: 16, color: '#fff', opacity: 0.7 }} />
                  <Typography sx={{ fontSize: '0.8rem', fontWeight: 600 }}>
                    {data[hovered].profissionais.toLocaleString('pt-BR')} profissionais
                  </Typography>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <BusinessIcon sx={{ fontSize: 16, color: '#fff', opacity: 0.7 }} />
                  <Typography sx={{ fontSize: '0.8rem', fontWeight: 600 }}>
                    {data[hovered].empresas.toLocaleString('pt-BR')} empresas
                  </Typography>
                </Box>
              </Box>
            )}
          </Box>

          {/* Region legend / summary */}
          <Box sx={{ flex: 1, width: '100%' }}>
            {Object.entries(regionColors).map(([region, color]) => {
              const regionData = data?.[region];
              return (
                <Box
                  key={region}
                  onMouseEnter={() => setHovered(region)}
                  onMouseLeave={() => setHovered(null)}
                  sx={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    py: 1.5, px: 2, mb: 1,
                    borderRadius: 2,
                    cursor: 'pointer',
                    border: '1px solid',
                    borderColor: hovered === region ? alpha(color, 0.3) : 'divider',
                    bgcolor: hovered === region ? alpha(color, 0.04) : 'transparent',
                    transition: 'all 0.2s ease',
                    '&:hover': {
                      borderColor: alpha(color, 0.3),
                      bgcolor: alpha(color, 0.04),
                    },
                  }}
                >
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                    <Box sx={{
                      width: 12, height: 12, borderRadius: '3px',
                      bgcolor: color, flexShrink: 0,
                    }} />
                    <Typography sx={{ fontWeight: 700, fontSize: '0.875rem' }}>
                      {region}
                    </Typography>
                  </Box>
                  <Box sx={{ display: 'flex', gap: 2.5 }}>
                    <Box sx={{ textAlign: 'right' }}>
                      <Typography sx={{ fontWeight: 800, fontSize: '0.875rem', color, fontVariantNumeric: 'tabular-nums' }}>
                        {regionData ? <CountUp value={regionData.profissionais} /> : '-'}
                      </Typography>
                      <Typography sx={{ fontSize: '0.6rem', color: 'text.disabled', fontWeight: 600 }}>
                        profissionais
                      </Typography>
                    </Box>
                    <Box sx={{ textAlign: 'right' }}>
                      <Typography sx={{ fontWeight: 800, fontSize: '0.875rem', color, fontVariantNumeric: 'tabular-nums' }}>
                        {regionData ? <CountUp value={regionData.empresas} /> : '-'}
                      </Typography>
                      <Typography sx={{ fontSize: '0.6rem', color: 'text.disabled', fontWeight: 600 }}>
                        empresas
                      </Typography>
                    </Box>
                  </Box>
                </Box>
              );
            })}
          </Box>
        </Box>
      )}
    </Paper>
  );
};

export default function Home() {
  const navigate = useNavigate();
  const { user, hasPermission } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [db3Stats, setDb3Stats] = useState<DB3Stats | null>(null);
  const [activityChart, setActivityChart] = useState<ActivityChart | null>(null);
  const [regionStats, setRegionStats] = useState<RegionStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [visibleSections, setVisibleSections] = useState<number[]>([]);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const [localRes, db3Res, chartRes, regionRes] = await Promise.allSettled([
          api.get('/dashboard/stats'),
          api.get('/dashboard/stats/db3'),
          api.get('/dashboard/activity-chart'),
          api.get('/dashboard/stats/db3/regioes'),
        ]);
        if (localRes.status === 'fulfilled') setStats(localRes.value.data);
        if (db3Res.status === 'fulfilled') setDb3Stats(db3Res.value.data);
        if (chartRes.status === 'fulfilled') setActivityChart(chartRes.value.data);
        if (regionRes.status === 'fulfilled') setRegionStats(regionRes.value.data);
      } catch (err) {
        console.error('Erro ao carregar dashboard:', err);
      } finally { setLoading(false); }
    };
    fetchStats();
  }, []);

  useEffect(() => {
    if (loading) {
      setVisibleSections([]);
      return;
    }
    // Stagger each section appearance
    const timers = [0, 1, 2, 3].map((i) =>
      window.setTimeout(() => setVisibleSections(prev => [...prev, i]), 100 + i * 150)
    );
    return () => { timers.forEach(window.clearTimeout); };
  }, [loading]);

  const visibleLinks = quickLinks.filter(l => hasPermission(l.perm));

  const totalProfissionais = db3Stats?.profissionais_ativos || 0;
  const maxUf = Math.max(...(db3Stats?.profissionais_por_uf || []).map(i => i.total), 1);
  const maxCat = Math.max(...(db3Stats?.profissionais_por_categoria || []).map(i => i.total), 1);
  const fmtPct = (v: number) => totalProfissionais ? ((v / totalProfissionais) * 100).toFixed(1) + '%' : '';

  const now = new Date();
  const greeting = now.getHours() < 12 ? 'Bom dia' : now.getHours() < 18 ? 'Boa tarde' : 'Boa noite';

  /* ── Chart data: preencher dias vazios ── */
  const chartDays: { label: string; value: number }[] = [];
  const userDays: { label: string; value: number }[] = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    const dayLabel = d.toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric' });
    const found = activityChart?.por_dia?.find(r => r.dia === key);
    chartDays.push({ label: dayLabel, value: found?.total || 0 });
    const foundU = activityChart?.usuarios_por_dia?.find(r => r.dia === key);
    userDays.push({ label: dayLabel, value: foundU?.total || 0 });
  }

  const topModulos = activityChart?.top_modulos || [];
  const maxModulo = Math.max(...topModulos.map(m => m.total), 1);

  return (
    <PageContainer>
      {/* ── Header ── */}
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
          <Typography variant="caption" color="text.disabled" sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
            <ClockIcon sx={{ fontSize: 14 }} />
            {now.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
          </Typography>
        </Box>
        <Typography variant="h5" sx={{ fontFamily: "'Instrument Serif', Georgia, serif", fontWeight: 400, letterSpacing: '-0.02em' }}>
          {greeting}, {user?.full_name?.split(' ')[0] || user?.username || 'Usuário'}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
          Sistema de Consultas — Conselho Federal de Odontologia
        </Typography>
        {loading && (
          <Box sx={{ mt: 2 }}>
            <LinearProgress
              sx={{
                height: 6,
                borderRadius: 6,
                bgcolor: alpha('#7A1E26', 0.08),
                '& .MuiLinearProgress-bar': {
                  borderRadius: 6,
                  background: `linear-gradient(90deg, ${alpha('#7A1E26', 0.35)}, #7A1E26, ${alpha('#7A1E26', 0.35)})`,
                },
              }}
            />
            <Typography variant="caption" color="text.disabled" sx={{ display: 'block', mt: 0.75 }}>
              Carregando dados do painel…
            </Typography>
          </Box>
        )}
      </Box>

      {/* ── KPI Cards ── */}
      <Fade in={loading || visibleSections.includes(0)} timeout={500}>
        <Box sx={{ mb: 4, transform: visibleSections.includes(0) ? 'translateY(0)' : 'translateY(12px)', transition: 'transform 500ms cubic-bezier(0.4,0,0.2,1)' }}>
          <Grid container spacing={2.5}>
            <Grid item xs={6} sm={6}>
              <StatCard icon={<PeopleIcon />} value={db3Stats?.profissionais_ativos ?? null}
                label="Total Brasil Ativo" color="#7A1E26" loading={loading}
                subtitle="Profissionais ativos" />
            </Grid>
            <Grid item xs={6} sm={6}>
              <StatCard icon={<BusinessIcon />} value={db3Stats?.empresas_ativas ?? null}
                label="Empresas Ativas" color="#2D6A4F" loading={loading}
                subtitle="Pessoa jurídica" />
            </Grid>
          </Grid>
        </Box>
      </Fade>

      {/* ── Brazil Map ── */}
      <Fade in={loading || visibleSections.includes(0)} timeout={500}>
        <Box sx={{ mb: 4 }}>
          <BrazilMap data={regionStats} loading={loading} />
        </Box>
      </Fade>

      {/* ── Distribution ── */}
      <Fade in={loading || visibleSections.includes(1)} timeout={500}>
        <Grid container spacing={2.5} sx={{ mb: 4, transform: visibleSections.includes(1) ? 'translateY(0)' : 'translateY(14px)', transition: 'transform 500ms cubic-bezier(0.4,0,0.2,1)' }}>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3, border: '1px solid', borderColor: 'divider', height: '100%' }} elevation={0}>
            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2.5 }}>
              <Box>
                <Typography variant="h6" sx={{ fontWeight: 700, fontSize: '1rem' }}>Profissionais por Categoria</Typography>
                <Typography variant="caption" color="text.disabled">Distribuição nacional</Typography>
              </Box>
              <Chip label="Top 5" size="small" sx={{ bgcolor: alpha('#7A1E26', 0.08), color: '#7A1E26', fontWeight: 700, fontSize: '0.7rem' }} />
            </Box>
            {loading ? (
              <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                {[...Array(5)].map((_, i) => <ShimmerSkeleton key={i} height={32} variant="rounded" />)}
              </Box>
            ) : (
              (db3Stats?.profissionais_por_categoria || []).map((item, idx) => {
                const pct = (item.total / maxCat) * 100;
                const barColor = catBarColors[idx] || '#999';
                return (
                  <Box key={idx} sx={{ mb: idx < (db3Stats?.profissionais_por_categoria?.length ?? 0) - 1 ? 1.5 : 0 }}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', mb: 0.5 }}>
                      <Typography variant="body2" sx={{ fontWeight: 600, fontSize: '0.8125rem' }}>
                        {item.categoria || 'N/A'}
                      </Typography>
                      <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1 }}>
                        <Typography variant="caption" sx={{ color: 'text.disabled', fontWeight: 600, fontVariantNumeric: 'tabular-nums' }}>
                          {fmtPct(item.total)}
                        </Typography>
                        <Typography variant="body2" sx={{ fontWeight: 700, color: barColor, fontSize: '0.8125rem', fontVariantNumeric: 'tabular-nums' }}>
                          <CountUp value={item.total} />
                        </Typography>
                      </Box>
                    </Box>
                    <Tooltip
                      arrow
                      placement="top"
                      componentsProps={{
                        tooltip: {
                          sx: {
                            bgcolor: '#0A0506',
                            borderRadius: 2,
                            boxShadow: '0 8px 32px rgba(0,0,0,0.3)',
                            px: 2, py: 1.25,
                            maxWidth: 260,
                          },
                        },
                        arrow: { sx: { color: '#0A0506' } },
                      }}
                      title={
                        <Box>
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
                            <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: barColor, flexShrink: 0 }} />
                            <Typography sx={{ fontWeight: 800, fontSize: '0.8rem', color: '#fff' }}>
                              {item.categoria}
                            </Typography>
                          </Box>
                          <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1 }}>
                            <Typography sx={{ fontSize: '0.75rem', color: '#fff', fontWeight: 700 }}>
                              {item.total.toLocaleString('pt-BR')} profissionais
                            </Typography>
                            <Typography sx={{ fontSize: '0.65rem', color: 'rgba(255,255,255,0.45)', fontWeight: 600 }}>
                              {fmtPct(item.total)} do total
                            </Typography>
                          </Box>
                        </Box>
                      }
                    >
                      <LinearProgress
                        variant="determinate" value={pct}
                        sx={{
                          height: 6, borderRadius: 3,
                          cursor: 'pointer',
                          bgcolor: alpha(barColor, 0.2),
                          '& .MuiLinearProgress-bar': {
                            borderRadius: 3,
                            background: `linear-gradient(90deg, ${barColor}, ${alpha(barColor, 0.85)})`,
                          },
                        }}
                      />
                    </Tooltip>
                  </Box>
                );
              })
            )}
          </Paper>
        </Grid>

        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3, border: '1px solid', borderColor: 'divider', height: '100%' }} elevation={0}>
            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2.5 }}>
              <Box>
                <Typography variant="h6" sx={{ fontWeight: 700, fontSize: '1rem' }}>Top 10 UFs por Profissionais</Typography>
                <Typography variant="caption" color="text.disabled">Profissionais ativos por estado</Typography>
              </Box>
              <Chip label="Top 10" size="small" sx={{ bgcolor: alpha('#1E4A7A', 0.08), color: '#1E4A7A', fontWeight: 700, fontSize: '0.7rem' }} />
            </Box>
            {loading ? (
              <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                {[...Array(5)].map((_, i) => <ShimmerSkeleton key={i} height={32} variant="rounded" />)}
              </Box>
            ) : (
              (db3Stats?.profissionais_por_uf || []).map((item, idx) => {
                const pct = (item.total / maxUf) * 100;
                const barColor = ufBarColors[idx] || '#DEB5B3';
                return (
                  <Box key={idx} sx={{ mb: idx < (db3Stats?.profissionais_por_uf?.length ?? 0) - 1 ? 1.25 : 0 }}>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 0.25 }}>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Chip
                          label={`${idx + 1}`}
                          size="small"
                          sx={{
                            minWidth: 24, height: 20, fontSize: '0.65rem', fontWeight: 800,
                            bgcolor: idx < 3 ? barColor : alpha(barColor, 0.2),
                            color: idx < 3 ? '#fff' : barColor,
                            '& .MuiChip-label': { px: 0.5 },
                          }}
                        />
                        <Typography variant="body2" sx={{ fontWeight: 700, fontSize: '0.8125rem', minWidth: 24 }}>
                          {item.uf || 'N/A'}
                        </Typography>
                      </Box>
                      <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1 }}>
                        <Typography variant="caption" sx={{ color: 'text.disabled', fontWeight: 600, fontVariantNumeric: 'tabular-nums' }}>
                          {fmtPct(item.total)}
                        </Typography>
                        <Typography variant="body2" sx={{
                          fontWeight: 700, color: barColor, fontSize: '0.8125rem',
                          fontVariantNumeric: 'tabular-nums',
                        }}>
                          <CountUp value={item.total} />
                        </Typography>
                      </Box>
                    </Box>
                    <Tooltip
                      arrow
                      placement="top"
                      componentsProps={{
                        tooltip: {
                          sx: {
                            bgcolor: '#0A0506',
                            borderRadius: 2,
                            boxShadow: '0 8px 32px rgba(0,0,0,0.3)',
                            p: 0,
                            maxWidth: 280,
                          },
                        },
                        arrow: { sx: { color: '#0A0506' } },
                      }}
                      title={
                        <Box>
                          {/* Header */}
                          <Box sx={{
                            px: 2, py: 1.25,
                            borderBottom: '1px solid rgba(255,255,255,0.1)',
                            background: 'linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 100%)',
                            borderRadius: '8px 8px 0 0',
                          }}>
                            <Typography sx={{ fontWeight: 800, fontSize: '0.8rem', color: '#fff', letterSpacing: '0.02em' }}>
                              {item.uf}
                            </Typography>
                            <Typography sx={{ fontSize: '0.7rem', color: 'rgba(255,255,255,0.5)', fontWeight: 500 }}>
                              {item.total.toLocaleString('pt-BR')} profissionais • {fmtPct(item.total)} do total
                            </Typography>
                          </Box>
                          {/* Categories */}
                          <Box sx={{ px: 2, py: 1.25 }}>
                            {(db3Stats?.profissionais_por_categoria || []).map((cat, ci) => {
                              const catPct = totalProfissionais ? (cat.total / totalProfissionais) : 0;
                              const estimativa = Math.round(item.total * catPct);
                              const catColors = ['#7A1E26', '#2F7D4F', '#1E4A7A', '#9A2832', '#B88A56'];
                              return (
                                <Box key={ci} sx={{
                                  display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                                  py: 0.5,
                                  borderBottom: ci < (db3Stats?.profissionais_por_categoria?.length ?? 0) - 1
                                    ? '1px solid rgba(255,255,255,0.06)' : 'none',
                                }}>
                                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                    <Box sx={{
                                      width: 8, height: 8, borderRadius: '50%',
                                      bgcolor: catColors[ci] || '#999', flexShrink: 0,
                                    }} />
                                    <Typography sx={{ fontSize: '0.7rem', color: 'rgba(255,255,255,0.85)', fontWeight: 500 }}>
                                      {cat.categoria}
                                    </Typography>
                                  </Box>
                                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, ml: 2 }}>
                                    <Typography sx={{
                                      fontSize: '0.7rem', color: '#fff', fontWeight: 700,
                                      fontVariantNumeric: 'tabular-nums',
                                    }}>
                                      ~{estimativa.toLocaleString('pt-BR')}
                                    </Typography>
                                    <Typography sx={{
                                      fontSize: '0.6rem', color: 'rgba(255,255,255,0.4)',
                                      fontWeight: 600, fontVariantNumeric: 'tabular-nums', minWidth: 36, textAlign: 'right',
                                    }}>
                                      {(catPct * 100).toFixed(1)}%
                                    </Typography>
                                  </Box>
                                </Box>
                              );
                            })}
                          </Box>
                        </Box>
                      }
                    >
                      <LinearProgress
                        variant="determinate" value={pct}
                        sx={{
                          height: 5, borderRadius: 3,
                          cursor: 'pointer',
                          bgcolor: alpha(barColor, 0.2),
                          '& .MuiLinearProgress-bar': {
                            borderRadius: 3,
                            bgcolor: barColor,
                          },
                        }}
                      />
                    </Tooltip>
                  </Box>
                );
              })
            )}
          </Paper>
        </Grid>
        </Grid>
      </Fade>

      {/* ── Activity Dashboard ── */}
      <Box sx={{ mb: 2.5 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
          <Box sx={{
            width: 36, height: 36, borderRadius: 2,
            bgcolor: alpha('#7A1E26', 0.08),
            display: 'flex', alignItems: 'center', justifyContent: 'center',
          }}>
            <TimelineIcon sx={{ fontSize: 20, color: '#7A1E26' }} />
          </Box>
          <Box>
            <Typography variant="h6" sx={{ fontFamily: "'Instrument Serif', Georgia, serif", fontWeight: 400, lineHeight: 1.2 }}>Atividade do Sistema</Typography>
            <Typography variant="caption" color="text.disabled" sx={{ fontWeight: 500 }}>
              Visão geral dos últimos 7 dias
            </Typography>
          </Box>
        </Box>
      </Box>
      <Fade in={loading || visibleSections.includes(2)} timeout={500}>
        <Grid container spacing={2.5} sx={{ mb: 4, transform: visibleSections.includes(2) ? 'translateY(0)' : 'translateY(14px)', transition: 'transform 500ms cubic-bezier(0.4,0,0.2,1)' }}>
        {/* Consultas por dia */}
        <Grid item xs={12} md={4}>
          <Paper sx={{
            p: 3, border: '1px solid', borderColor: 'divider', height: '100%',
            position: 'relative', overflow: 'hidden',
            transition: 'box-shadow 0.25s ease',
            '&:hover': { boxShadow: `0 8px 24px ${alpha('#7A1E26', 0.1)}` },
          }} elevation={0}>
            <Box sx={{
              position: 'absolute', top: 0, left: 0, right: 0, height: 3,
              background: `linear-gradient(90deg, #7A1E26, ${alpha('#7A1E26', 0.8)})`,
            }} />
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
              <Box sx={{
                width: 32, height: 32, borderRadius: 2,
                bgcolor: alpha('#7A1E26', 0.08),
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>
                <TimelineIcon sx={{ fontSize: 18, color: '#7A1E26' }} />
              </Box>
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.2 }}>Consultas por Dia</Typography>
                <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem' }}>
                  Requisições ao sistema
                </Typography>
              </Box>
            </Box>
            <Divider sx={{ my: 1.5, borderColor: alpha('#7A1E26', 0.08) }} />
            {loading ? (
              <ShimmerSkeleton variant="rounded" height={100} />
            ) : (
              <>
                <MiniLineChart data={chartDays} color="#7A1E26" />
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 1.5, px: 0.5 }}>
                  {chartDays.map((d, i) => (
                    <Typography key={i} variant="caption" sx={{
                      fontSize: '0.6rem', textAlign: 'center', flex: 1,
                      fontWeight: 600, color: 'text.disabled', textTransform: 'capitalize',
                    }}>
                      {d.label.split(' ')[0]}
                    </Typography>
                  ))}
                </Box>
              </>
            )}
          </Paper>
        </Grid>

        {/* Usuários por dia */}
        <Grid item xs={12} md={4}>
          <Paper sx={{
            p: 3, border: '1px solid', borderColor: 'divider', height: '100%',
            position: 'relative', overflow: 'hidden',
            transition: 'box-shadow 0.25s ease',
            '&:hover': { boxShadow: `0 8px 24px ${alpha('#1E4A7A', 0.1)}` },
          }} elevation={0}>
            <Box sx={{
              position: 'absolute', top: 0, left: 0, right: 0, height: 3,
              background: `linear-gradient(90deg, #1E4A7A, ${alpha('#1E4A7A', 0.8)})`,
            }} />
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
              <Box sx={{
                width: 32, height: 32, borderRadius: 2,
                bgcolor: alpha('#1E4A7A', 0.08),
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>
                <GroupWorkIcon sx={{ fontSize: 18, color: '#1E4A7A' }} />
              </Box>
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.2 }}>Usuários por Dia</Typography>
                <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem' }}>
                  Usuários únicos ativos
                </Typography>
              </Box>
            </Box>
            <Divider sx={{ my: 1.5, borderColor: alpha('#1E4A7A', 0.08) }} />
            {loading ? (
              <ShimmerSkeleton variant="rounded" height={100} />
            ) : (
              <>
                <MiniLineChart data={userDays} color="#1E4A7A" />
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 1.5, px: 0.5 }}>
                  {userDays.map((d, i) => (
                    <Typography key={i} variant="caption" sx={{
                      fontSize: '0.6rem', textAlign: 'center', flex: 1,
                      fontWeight: 600, color: 'text.disabled', textTransform: 'capitalize',
                    }}>
                      {d.label.split(' ')[0]}
                    </Typography>
                  ))}
                </Box>
              </>
            )}
          </Paper>
        </Grid>

        {/* Top módulos — Donut chart */}
        <Grid item xs={12} md={4}>
          <Paper sx={{
            p: 3, border: '1px solid', borderColor: 'divider', height: '100%',
            position: 'relative', overflow: 'hidden',
            transition: 'box-shadow 0.25s ease',
            '&:hover': { boxShadow: `0 8px 24px ${alpha('#9A2832', 0.1)}` },
          }} elevation={0}>
            <Box sx={{
              position: 'absolute', top: 0, left: 0, right: 0, height: 3,
              background: `linear-gradient(90deg, #9A2832, ${alpha('#9A2832', 0.8)})`,
            }} />
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
              <Box sx={{
                width: 32, height: 32, borderRadius: 2,
                bgcolor: alpha('#9A2832', 0.08),
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>
                <SpeedIcon sx={{ fontSize: 18, color: '#9A2832' }} />
              </Box>
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.2 }}>Módulos Mais Acessados</Typography>
                <Typography variant="caption" color="text.disabled" sx={{ fontSize: '0.65rem' }}>
                  Top 5 — últimos 7 dias
                </Typography>
              </Box>
            </Box>
            <Divider sx={{ my: 1.5, borderColor: alpha('#9A2832', 0.08) }} />
            {loading ? (
              <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
                {[...Array(5)].map((_, i) => <ShimmerSkeleton key={i} height={28} variant="rounded" />)}
              </Box>
            ) : topModulos.length === 0 ? (
              <Typography variant="body2" color="text.disabled" sx={{ textAlign: 'center', py: 3 }}>
                Sem dados
              </Typography>
            ) : (() => {
              const pieColors = ['#7A1E26', '#2F7D4F', '#1E4A7A', '#9A2832', '#B88A56'];
              const totalModulos = topModulos.reduce((sum, mod) => sum + mod.total, 0);
              const cx = 90, cy = 90, r = 70, innerR = 45;
              let cumAngle = -90;

              return (
                <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2 }}>
                  {/* Donut */}
                  <Box sx={{ position: 'relative', width: 180, height: 180 }}>
                    <svg viewBox="0 0 180 180" width="180" height="180">
                      {topModulos.map((m, idx) => {
                        const slicePct = totalModulos ? m.total / totalModulos : 0;
                        const angle = slicePct * 360;
                        const startAngle = cumAngle;
                        cumAngle += angle;
                        const endAngle = cumAngle;

                        const startRad = (startAngle * Math.PI) / 180;
                        const endRad = (endAngle * Math.PI) / 180;
                        const largeArc = angle > 180 ? 1 : 0;

                        const x1 = cx + r * Math.cos(startRad);
                        const y1 = cy + r * Math.sin(startRad);
                        const x2 = cx + r * Math.cos(endRad);
                        const y2 = cy + r * Math.sin(endRad);
                        const ix1 = cx + innerR * Math.cos(endRad);
                        const iy1 = cy + innerR * Math.sin(endRad);
                        const ix2 = cx + innerR * Math.cos(startRad);
                        const iy2 = cy + innerR * Math.sin(startRad);

                        const path = [
                          `M${x1},${y1}`,
                          `A${r},${r} 0 ${largeArc} 1 ${x2},${y2}`,
                          `L${ix1},${iy1}`,
                          `A${innerR},${innerR} 0 ${largeArc} 0 ${ix2},${iy2}`,
                          'Z',
                        ].join(' ');

                        return (
                          <path
                            key={idx}
                            d={path}
                            fill={pieColors[idx] || '#999'}
                            stroke="#fff"
                            strokeWidth={2}
                            style={{ transition: 'opacity 0.2s', cursor: 'pointer' }}
                            onMouseEnter={(e) => { e.currentTarget.style.opacity = '0.8'; }}
                            onMouseLeave={(e) => { e.currentTarget.style.opacity = '1'; }}
                          >
                            <title>{`${moduleName(m.modulo)}: ${m.total.toLocaleString('pt-BR')} (${(slicePct * 100).toFixed(1)}%)`}</title>
                          </path>
                        );
                      })}
                    </svg>
                    {/* Center label */}
                    <Box sx={{
                      position: 'absolute', top: '50%', left: '50%',
                      transform: 'translate(-50%, -50%)',
                      textAlign: 'center',
                    }}>
                      <Typography sx={{ fontWeight: 800, fontSize: '1.25rem', color: '#0A0506', lineHeight: 1, letterSpacing: '-0.02em' }}>
                        {totalModulos.toLocaleString('pt-BR')}
                      </Typography>
                      <Typography sx={{ fontSize: '0.6rem', color: 'text.disabled', fontWeight: 600 }}>
                        consultas
                      </Typography>
                    </Box>
                  </Box>

                  {/* Legend */}
                  <Box sx={{ width: '100%' }}>
                    {topModulos.map((m, idx) => {
                      const slicePct = totalModulos ? ((m.total / totalModulos) * 100).toFixed(1) : '0';
                      return (
                        <Box key={idx} sx={{
                          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                          py: 0.6,
                          borderBottom: idx < topModulos.length - 1 ? '1px solid' : 'none',
                          borderColor: 'divider',
                        }}>
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                            <Box sx={{
                              width: 10, height: 10, borderRadius: '3px',
                              bgcolor: pieColors[idx] || '#999', flexShrink: 0,
                            }} />
                            <Typography sx={{ fontSize: '0.75rem', fontWeight: 600, color: 'text.primary' }}>
                              {moduleName(m.modulo)}
                            </Typography>
                          </Box>
                          <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 0.75 }}>
                            <Typography sx={{ fontSize: '0.75rem', fontWeight: 700, color: pieColors[idx], fontVariantNumeric: 'tabular-nums' }}>
                              <CountUp value={m.total} />
                            </Typography>
                            <Typography sx={{ fontSize: '0.6rem', fontWeight: 600, color: 'text.disabled', minWidth: 32, textAlign: 'right' }}>
                              {slicePct}%
                            </Typography>
                          </Box>
                        </Box>
                      );
                    })}
                  </Box>
                </Box>
              );
            })()}
          </Paper>
        </Grid>
        </Grid>
      </Fade>

      {/* ── Quick Access ── */}
      <Box sx={{ mb: 2 }}>
        <Typography variant="h6" sx={{ fontFamily: "'Instrument Serif', Georgia, serif", fontWeight: 400 }}>Acesso Rápido</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
          Navegue diretamente para os módulos disponíveis
        </Typography>
      </Box>
      <Fade in={loading || visibleSections.includes(3)} timeout={500}>
      <Grid container spacing={2}>
        {loading ? (
          [...Array(6)].map((_, i) => (
            <Grid item xs={6} sm={4} md={3} lg={2} key={`ql-skel-${i}`}>
              <Card sx={{ height: '100%' }}>
                <Box sx={{ p: 2.5, height: '100%' }}>
                  <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                    <ShimmerSkeleton variant="rounded" width={48} height={48} sx={{ borderRadius: 3, mb: 1.5 }} />
                    <ShimmerSkeleton width="80%" height={22} />
                    <ShimmerSkeleton width="95%" height={16} />
                    <ShimmerSkeleton width="70%" height={16} />
                  </Box>
                </Box>
              </Card>
            </Grid>
          ))
        ) : (
          visibleLinks.map((link) => (
            <Grid item xs={6} sm={4} md={3} lg={2} key={link.path}>
              <Card sx={{
                height: '100%',
                transition: 'all 0.2s ease',
                '&:hover': {
                  borderColor: alpha(link.color, 0.3),
                  '& .card-arrow': { opacity: 1, transform: 'translateX(0)' },
                },
              }}>
                <CardActionArea onClick={() => navigate(link.path)} sx={{ p: 2.5, height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                  <Box sx={{
                    width: 48, height: 48, borderRadius: 3, mb: 1.5,
                    bgcolor: alpha(link.color, 0.08),
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    transition: 'all 0.2s',
                  }}>
                    {React.cloneElement(link.icon, { sx: { fontSize: 24, color: link.color } })}
                  </Box>
                  <Typography variant="body2" sx={{ fontWeight: 700, lineHeight: 1.3, textAlign: 'center', mb: 0.5 }}>
                    {link.title}
                  </Typography>
                  <Typography variant="caption" color="text.disabled" sx={{ textAlign: 'center', lineHeight: 1.3, fontSize: '0.65rem' }}>
                    {link.desc}
                  </Typography>
                </CardActionArea>
              </Card>
            </Grid>
          ))
        )}
      </Grid>
      </Fade>
    </PageContainer>
  );
}
