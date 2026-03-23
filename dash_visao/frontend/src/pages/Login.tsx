import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import logoCfo from '../assets/logo.png';
import {
  MapPin, Phone, Mail, Eye, EyeOff, LogIn, Shield,
  UserPlus, FileSearch, Lock, ChevronRight,
} from 'lucide-react';

const Login: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (loading) return;
    setLoading(true);
    try {
      await login(email, password);
      navigate('/');
    } catch (err: any) {
      setError(err.message || 'Erro ao fazer login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-white/80">
      {/* ── Top Bar ── */}
      <div style={{ background: 'linear-gradient(90deg, #6B0A0D 0%, #8d0f12 40%, #A3171A 100%)' }} className="text-white/90 py-2">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center text-xs tracking-wide">
            <div className="flex flex-wrap items-center gap-5 mb-1.5 md:mb-0">
              <div className="flex items-center gap-1.5">
                <MapPin size={12} className="opacity-70" />
                <span className="hidden md:inline">Brasilia - DF</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Phone size={12} className="opacity-70" />
                <span className="hidden md:inline">(61) 3033-4499 / 3033-4469</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Mail size={12} className="opacity-70" />
                <span className="hidden md:inline">cfo@cfo.org.br</span>
              </div>
            </div>
            <div className="flex items-center gap-1.5 text-white/60">
              <Shield size={12} />
              <span>Acesso restrito a usuarios autorizados</span>
            </div>
          </div>
        </div>
      </div>

      {/* ── Header ── */}
      <header className="py-5 border-b border-[#7a0d10]/30" style={{ background: 'linear-gradient(135deg, #f8f4f4 0%, #f0eaea 50%, #ebe4e4 100%)' }}>
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-center md:justify-start">
            <img src={logoCfo} alt="CFO" className="h-14 w-auto object-contain" />
            <div className="ml-4 border-l border-gray-300 pl-4">
              <h1 className="text-lg font-bold text-gray-800 tracking-tight">Sistema de Consultas</h1>
              <p className="text-xs text-gray-500 tracking-wide uppercase">Conselho Federal de Odontologia</p>
            </div>
          </div>
        </div>
      </header>

      {/* ── Main ── */}
      <main className="flex-grow flex items-center justify-center px-4 py-10">
        <div className="w-full max-w-[440px]">

          {/* Login Card */}
          <div className="bg-white rounded-2xl shadow-xl shadow-black/5 border border-gray-100 overflow-hidden">
            {/* Card accent */}
            <div className="h-1.5" style={{ background: 'linear-gradient(90deg, #6B0A0D, #8d0f12, #B71C1F, #8d0f12, #6B0A0D)' }} />

            <div className="p-8 md:p-10">
              {/* Title */}
              <div className="text-center mb-8">
                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                  style={{ background: 'linear-gradient(135deg, #8d0f12 0%, #B71C1F 100%)' }}>
                  <Lock size={28} className="text-white" />
                </div>
                <h2 className="text-2xl font-bold text-gray-900 tracking-tight">
                  Acesso ao Sistema
                </h2>
                <p className="text-sm text-gray-500 mt-1.5">
                  Informe suas credenciais para continuar
                </p>
              </div>

              {/* Form */}
              <form onSubmit={handleSubmit} className="space-y-5">
                <div>
                  <label htmlFor="email" className="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                    E-mail
                  </label>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autoComplete="email"
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm
                      focus:outline-none focus:ring-2 focus:ring-[#8d0f12]/20 focus:border-[#8d0f12] focus:bg-white
                      transition-all duration-200 placeholder:text-gray-400"
                    placeholder="Digite seu e-mail"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                  />
                </div>

                <div>
                  <label htmlFor="password" className="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
                    Senha
                  </label>
                  <div className="relative">
                    <input
                      id="password"
                      name="password"
                      type={showPassword ? 'text' : 'password'}
                      required
                      autoComplete="current-password"
                      className="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-sm
                        focus:outline-none focus:ring-2 focus:ring-[#8d0f12]/20 focus:border-[#8d0f12] focus:bg-white
                        transition-all duration-200 placeholder:text-gray-400"
                      placeholder="Digite sua senha"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                      tabIndex={-1}
                    >
                      {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                    </button>
                  </div>
                </div>

                {/* Error */}
                {error && (
                  <div className="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                    <div className="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" />
                    <p className="text-red-700 text-sm font-medium">{error}</p>
                  </div>
                )}

                {/* Forgot password */}
                <div className="flex justify-end">
                  <Link
                    to="/forgot-password"
                    className="text-xs font-semibold text-[#8d0f12] hover:text-[#6B0A0D] transition-colors"
                  >
                    Esqueci minha senha
                  </Link>
                </div>

                {/* Submit */}
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl text-white font-semibold text-sm
                    transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed
                    hover:shadow-lg hover:shadow-[#8d0f12]/25 active:scale-[0.98]"
                  style={{ background: loading ? '#999' : 'linear-gradient(135deg, #8d0f12 0%, #B71C1F 100%)' }}
                >
                  {loading ? (
                    <>
                      <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      <span>Entrando...</span>
                    </>
                  ) : (
                    <>
                      <LogIn size={18} />
                      <span>Entrar</span>
                    </>
                  )}
                </button>
              </form>

              {/* Divider */}
              <div className="relative my-8">
                <div className="absolute inset-0 flex items-center">
                  <div className="w-full border-t border-gray-200" />
                </div>
                <div className="relative flex justify-center">
                  <span className="bg-white px-4 text-xs text-gray-400 uppercase tracking-wider">ou</span>
                </div>
              </div>

              {/* Help links */}
              <div className="space-y-2.5">
                <Link
                  to="/solicitar-usuario"
                  className="flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50
                    hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 group"
                >
                  <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-[#8d0f12]/8">
                    <UserPlus size={16} className="text-[#8d0f12]" />
                  </div>
                  <div className="flex-grow">
                    <p className="text-sm font-semibold text-gray-700">Solicitar novo usuario</p>
                    <p className="text-xs text-gray-400">Primeiro acesso ao sistema</p>
                  </div>
                  <ChevronRight size={16} className="text-gray-300 group-hover:text-gray-500 transition-colors" />
                </Link>

                <Link
                  to="/status-solicitacao"
                  className="flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50
                    hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 group"
                >
                  <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-[#8d0f12]/8">
                    <FileSearch size={16} className="text-[#8d0f12]" />
                  </div>
                  <div className="flex-grow">
                    <p className="text-sm font-semibold text-gray-700">Verificar status da solicitacao</p>
                    <p className="text-xs text-gray-400">Acompanhe seu pedido de acesso</p>
                  </div>
                  <ChevronRight size={16} className="text-gray-300 group-hover:text-gray-500 transition-colors" />
                </Link>
              </div>
            </div>
          </div>

          {/* Info card */}
          <div className="mt-6 px-6 py-4 rounded-xl bg-white/60 backdrop-blur-sm border border-gray-200/60 text-center">
            <p className="text-xs text-gray-500 leading-relaxed">
              O acesso a este sistema e restrito a servidores e colaboradores autorizados
              pelo Conselho Federal de Odontologia. O uso indevido podera acarretar responsabilidade
              civil e criminal.
            </p>
          </div>
        </div>
      </main>

      {/* ── Footer ── */}
      <footer style={{ background: 'linear-gradient(90deg, #5a0a0c 0%, #8d0f12 50%, #5a0a0c 100%)' }} className="text-white py-6">
        <div className="container mx-auto px-4 text-center">
          <p className="text-xs font-medium mb-1.5 opacity-90">
            &copy; {new Date().getFullYear()} CFO — Conselho Federal de Odontologia
          </p>
          <p className="text-xs opacity-50 leading-relaxed">
            Lote 2, Quadra CA-07, Centro de Atividades do Setor de Habitacoes
            Individuais Norte Lago Norte, Brasilia – DF, CEP: 71.503-507
          </p>
          <p className="text-xs opacity-50 mt-1">
            Atendimento: Segunda a sexta, 08:00 as 17:00
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Login;
