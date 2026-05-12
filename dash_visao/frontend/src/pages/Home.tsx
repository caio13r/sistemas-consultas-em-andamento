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

interface ActivityChart {
  por_dia: { dia: string; total: number }[];
  top_modulos: { modulo: string; total: number }[];
  usuarios_por_dia: { dia: string; total: number }[];
}

const quickLinks = [
  { path: '/consulta-integrada', title: 'Consulta Integrada', desc: 'Busca unificada de profissionais', icon: <PersonSearchIcon />, perm: 'view_consulta_integrada', color: '#7A1E26' },
  { path: '/consulta-identidade', title: 'Identidade Profissional', desc: 'Carteiras e registros', icon: <BadgeIcon />, perm: 'view_consulta_identidade', color: '#2F7D4F' },
  { path: '/consulta-rfb', title: 'Consulta RFB', desc: 'Receita Federal do Brasil', icon: <SearchIcon />, perm: 'view_consulta_rfb', color: '#1E4A7A' },
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
    '/api/consulta-integrada': 'Consulta Integrada',
    '/api/consulta-identidade': 'Identidade Profissional',
    '/api/consulta-rfb': 'Consulta RFB',
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

export default function Home() {
  const navigate = useNavigate();
  const { user, hasPermission } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [db3Stats, setDb3Stats] = useState<DB3Stats | null>(null);
  const [activityChart, setActivityChart] = useState<ActivityChart | null>(null);
  const [loading, setLoading] = useState(true);
  const [visibleSections, setVisibleSections] = useState<number[]>([]);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const [localRes, db3Res, chartRes] = await Promise.allSettled([
          api.get('/dashboard/stats'),
          api.get('/dashboard/stats/db3'),
          api.get('/dashboard/activity-chart'),
        ]);
        if (localRes.status === 'fulfilled') setStats(localRes.value.data);
        if (db3Res.status === 'fulfilled') setDb3Stats(db3Res.value.data);
        if (chartRes.status === 'fulfilled') setActivityChart(chartRes.value.data);
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
            <Grid item xs={6} sm={3}>
              <StatCard icon={<PeopleIcon />} value={db3Stats?.profissionais_ativos ?? null}
                label="Profissionais Ativos" color="#7A1E26" loading={loading}
                subtitle="Base nacional" />
            </Grid>
            <Grid item xs={6} sm={3}>
              <StatCard icon={<BusinessIcon />} value={db3Stats?.empresas_ativas ?? null}
                label="Empresas Ativas" color="#2D6A4F" loading={loading}
                subtitle="Pessoa jurídica" />
            </Grid>
            <Grid item xs={6} sm={3}>
              <StatCard icon={<TrendingIcon />} value={stats?.recent_activity_7d ?? null}
                label="Consultas (7 dias)" color="#1E4A7A" loading={loading}
                subtitle="Atividade recente" />
            </Grid>
            <Grid item xs={6} sm={3}>
              <StatCard icon={<PeopleIcon />} value={stats?.active_today ?? null}
                label="Usuários Ativos Hoje" color="#7B2D8B" loading={loading}
                subtitle="Sessões do dia" />
            </Grid>
          </Grid>
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
