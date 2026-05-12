import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';
import logoCfo from '../assets/logo.png';
import {
  MapPin, Phone, Mail, Eye, EyeOff, LogIn,
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
      localStorage.removeItem('acceptedTerms');
      await login(email, password);
      navigate('/termos-de-uso');
    } catch (err: any) {
      setError(err.message || 'Erro ao fazer login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col relative" style={{ backgroundColor: '#FBF8F4' }}>
      {/* Mesh grid background */}
      <div
        className="fixed inset-0 pointer-events-none"
        style={{
          zIndex: 0,
          backgroundImage: `
            linear-gradient(rgba(122,30,38,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(122,30,38,0.06) 1px, transparent 1px)
          `,
          backgroundSize: '48px 48px',
        }}
      />

      {/* Topbar institucional */}
      <div
        className="text-white/90 py-2"
        style={{ background: 'linear-gradient(90deg, #5C1519 0%, #7A1E26 100%)', position: 'relative', zIndex: 1 }}
      >
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center text-xs tracking-wide font-body">
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
          </div>
        </div>
      </div>

      {/* Header */}
      <header
        className="py-3"
        style={{
          backgroundColor: '#F5EDE0',
          borderBottom: '1px solid rgba(122,30,38,0.1)',
          position: 'relative',
          zIndex: 1,
        }}
      >
        <div className="container mx-auto px-4 md:px-8">
          <div className="flex items-center">
            <img src={logoCfo} alt="CFO" className="h-8 w-auto object-contain" />
            <div className="mx-2.5" style={{ width: '2px', height: '28px', backgroundColor: 'rgba(122,30,38,0.2)', borderRadius: '1px', flexShrink: 0 }} />
            <div>
              <h1
                className="tracking-tight font-display"
                style={{ color: '#0A0506', fontWeight: 400, fontSize: 'clamp(16px, 2vw, 20px)' }}
              >
                Vis&atilde;o CFO
              </h1>
              <p className="eyebrow" style={{ fontSize: '8px', letterSpacing: '0.18em', whiteSpace: 'nowrap' }}>
                Sistema de integração geral de dados
              </p>
            </div>
          </div>
        </div>
      </header>

      {/* Main — Split layout */}
      <main className="flex-grow flex items-center justify-center px-4 py-10" style={{ position: 'relative', zIndex: 1 }}>
        <div className="w-full max-w-[480px]">
          <div
            className="rounded-2xl overflow-hidden flex flex-col"
            style={{
              backgroundColor: '#fff',
              boxShadow: '0 4px 24px rgba(122,30,38,0.06)',
              border: '1px solid rgba(122,30,38,0.08)',
            }}
          >
            {/* Login form */}
            <div className="flex-grow flex flex-col">
              {/* Card accent */}
              <div className="h-1.5 md:hidden" style={{ background: 'linear-gradient(90deg, #5C1519, #7A1E26, #9A2832, #7A1E26, #5C1519)' }} />

              <div className="p-8 md:p-10 flex-grow flex flex-col justify-center">
                {/* Title */}
                <div className="text-center mb-8">
                  <div
                    className="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                    style={{ background: 'linear-gradient(135deg, #7A1E26 0%, #9A2832 100%)' }}
                  >
                    <Lock size={28} className="text-white" />
                  </div>
                  <h2
                    className="font-display tracking-tight"
                    style={{ fontSize: 'clamp(24px, 4vw, 32px)', color: '#0A0506', fontWeight: 400 }}
                  >
                    Acesso ao Sistema
                  </h2>
                  <p className="text-sm mt-1.5" style={{ color: 'rgba(20,10,12,0.5)' }}>
                    Informe suas credenciais para continuar
                  </p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label
                      htmlFor="email"
                      className="block text-xs font-semibold uppercase mb-1.5"
                      style={{ color: 'rgba(20,10,12,0.5)', letterSpacing: '0.12em' }}
                    >
                      E-mail
                    </label>
                    <input
                      id="email"
                      name="email"
                      type="email"
                      required
                      autoComplete="email"
                      className="w-full px-4 py-3 rounded-xl text-sm transition-all duration-200"
                      style={{
                        backgroundColor: '#FBF8F4',
                        border: '1px solid rgba(122,30,38,0.12)',
                        color: '#0A0506',
                        outline: 'none',
                      }}
                      onFocus={e => {
                        e.target.style.borderColor = '#7A1E26';
                        e.target.style.boxShadow = '0 0 0 3px rgba(122,30,38,0.08)';
                        e.target.style.backgroundColor = '#fff';
                      }}
                      onBlur={e => {
                        e.target.style.borderColor = 'rgba(122,30,38,0.12)';
                        e.target.style.boxShadow = 'none';
                        e.target.style.backgroundColor = '#FBF8F4';
                      }}
                      placeholder="Digite seu e-mail"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                    />
                  </div>

                  <div>
                    <label
                      htmlFor="password"
                      className="block text-xs font-semibold uppercase mb-1.5"
                      style={{ color: 'rgba(20,10,12,0.5)', letterSpacing: '0.12em' }}
                    >
                      Senha
                    </label>
                    <div className="relative">
                      <input
                        id="password"
                        name="password"
                        type={showPassword ? 'text' : 'password'}
                        required
                        autoComplete="current-password"
                        className="w-full px-4 py-3 pr-12 rounded-xl text-sm transition-all duration-200"
                        style={{
                          backgroundColor: '#FBF8F4',
                          border: '1px solid rgba(122,30,38,0.12)',
                          color: '#0A0506',
                          outline: 'none',
                        }}
                        onFocus={e => {
                          e.target.style.borderColor = '#7A1E26';
                          e.target.style.boxShadow = '0 0 0 3px rgba(122,30,38,0.08)';
                          e.target.style.backgroundColor = '#fff';
                        }}
                        onBlur={e => {
                          e.target.style.borderColor = 'rgba(122,30,38,0.12)';
                          e.target.style.boxShadow = 'none';
                          e.target.style.backgroundColor = '#FBF8F4';
                        }}
                        placeholder="Digite sua senha"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute inset-y-0 right-0 pr-4 flex items-center transition-colors"
                        style={{ color: 'rgba(20,10,12,0.35)' }}
                        tabIndex={-1}
                      >
                        {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                      </button>
                    </div>
                  </div>

                  {/* Error */}
                  {error && (
                    <div
                      className="flex items-center gap-2 px-4 py-3 rounded-xl"
                      style={{ backgroundColor: 'rgba(200,57,63,0.06)', border: '1px solid rgba(200,57,63,0.15)' }}
                    >
                      <div className="w-2 h-2 rounded-full flex-shrink-0" style={{ backgroundColor: '#C8393F' }} />
                      <p className="text-sm font-medium" style={{ color: '#C8393F' }}>{error}</p>
                    </div>
                  )}

                  {/* Forgot password */}
                  <div className="flex justify-end">
                    <Link
                      to="/forgot-password"
                      className="text-xs font-semibold transition-colors"
                      style={{ color: '#7A1E26' }}
                    >
                      Esqueci minha senha
                    </Link>
                  </div>

                  {/* Submit */}
                  <button
                    type="submit"
                    disabled={loading}
                    className="w-full flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl text-white font-semibold text-sm
                      transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]"
                    style={{
                      background: loading ? '#6D6E71' : 'linear-gradient(135deg, #7A1E26 0%, #9A2832 100%)',
                      boxShadow: loading ? 'none' : '0 4px 14px rgba(122,30,38,0.25)',
                    }}
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
                    <div className="w-full" style={{ borderTop: '1px solid rgba(122,30,38,0.1)' }} />
                  </div>
                  <div className="relative flex justify-center">
                    <span className="bg-white px-4 text-xs uppercase tracking-wider" style={{ color: 'rgba(20,10,12,0.35)' }}>ou</span>
                  </div>
                </div>

                {/* Help links */}
                <div className="space-y-2.5">
                  <Link
                    to="/solicitar-usuario"
                    className="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group"
                    style={{ border: '1px solid rgba(122,30,38,0.1)', backgroundColor: 'rgba(251,248,244,0.5)' }}
                  >
                    <div
                      className="flex items-center justify-center w-8 h-8 rounded-lg"
                      style={{ backgroundColor: 'rgba(122,30,38,0.06)' }}
                    >
                      <UserPlus size={16} style={{ color: '#7A1E26' }} />
                    </div>
                    <div className="flex-grow">
                      <p className="text-sm font-semibold" style={{ color: '#0A0506' }}>Solicitar novo usuario</p>
                      <p className="text-xs" style={{ color: 'rgba(20,10,12,0.4)' }}>Primeiro acesso ao sistema</p>
                    </div>
                    <ChevronRight size={16} style={{ color: 'rgba(20,10,12,0.2)' }} />
                  </Link>

                  <Link
                    to="/status-solicitacao"
                    className="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group"
                    style={{ border: '1px solid rgba(122,30,38,0.1)', backgroundColor: 'rgba(251,248,244,0.5)' }}
                  >
                    <div
                      className="flex items-center justify-center w-8 h-8 rounded-lg"
                      style={{ backgroundColor: 'rgba(122,30,38,0.06)' }}
                    >
                      <FileSearch size={16} style={{ color: '#7A1E26' }} />
                    </div>
                    <div className="flex-grow">
                      <p className="text-sm font-semibold" style={{ color: '#0A0506' }}>Verificar status da solicitacao</p>
                      <p className="text-xs" style={{ color: 'rgba(20,10,12,0.4)' }}>Acompanhe seu pedido de acesso</p>
                    </div>
                    <ChevronRight size={16} style={{ color: 'rgba(20,10,12,0.2)' }} />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer
        className="text-white py-6"
        style={{
          background: 'linear-gradient(90deg, #5C1519 0%, #7A1E26 50%, #5C1519 100%)',
          position: 'relative',
          zIndex: 1,
        }}
      >
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
