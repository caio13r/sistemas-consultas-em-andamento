import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Lock, ArrowLeft, CheckCircle, Eye, EyeOff, MapPin, Phone, Mail } from 'lucide-react';
import logoCfo from '../assets/logo.png';
import axios from 'axios';

const ResetPassword: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (password.length < 6) {
      setError('A senha deve ter pelo menos 6 caracteres.');
      return;
    }

    if (password !== confirmPassword) {
      setError('As senhas nao coincidem.');
      return;
    }

    if (!token) {
      setError('Token de recuperação inválido. Solicite um novo link.');
      return;
    }

    setLoading(true);
    try {
      await axios.post('/api/reset-password', {
        token,
        new_password: password,
      });
      setSuccess(true);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao redefinir senha. O link pode ter expirado.');
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
        <div className="w-full max-w-[420px] mx-auto px-5 md:px-6">
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

      {/* Main */}
      <main className="flex-grow flex items-center justify-center px-4 py-10" style={{ position: 'relative', zIndex: 1 }}>
        <div className="w-full max-w-[420px]">
          <div
            className="rounded-2xl overflow-hidden"
            style={{
              backgroundColor: '#fff',
              boxShadow: '0 4px 24px rgba(122,30,38,0.06)',
              border: '1px solid rgba(122,30,38,0.08)',
            }}
          >
            {/* Card accent */}
            <div className="h-1.5" style={{ background: 'linear-gradient(90deg, #5C1519, #7A1E26, #9A2832, #7A1E26, #5C1519)' }} />

            <div className="p-8 md:p-10">
              {success ? (
                <div className="text-center">
                  <div
                    className="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4"
                    style={{ background: 'linear-gradient(135deg, #2e7d32 0%, #43a047 100%)' }}
                  >
                    <CheckCircle size={28} className="text-white" />
                  </div>
                  <h2
                    className="font-display tracking-tight mb-2"
                    style={{ fontSize: 'clamp(24px, 4vw, 32px)', color: '#0A0506', fontWeight: 400 }}
                  >
                    Senha Redefinida!
                  </h2>
                  <p className="text-sm mb-8" style={{ color: 'rgba(20,10,12,0.5)' }}>
                    Sua senha foi alterada com sucesso. Voce ja pode fazer login com a nova senha.
                  </p>
                  <Link
                    to="/login"
                    className="inline-flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl text-white font-semibold text-sm
                      transition-all duration-200 active:scale-[0.98]"
                    style={{
                      background: 'linear-gradient(135deg, #7A1E26 0%, #9A2832 100%)',
                      boxShadow: '0 4px 14px rgba(122,30,38,0.25)',
                    }}
                  >
                    Ir para o Login
                  </Link>
                </div>
              ) : (
                <>
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
                      Redefinir Senha
                    </h2>
                    <p className="text-sm mt-1.5" style={{ color: 'rgba(20,10,12,0.5)' }}>
                      Digite sua nova senha abaixo
                    </p>
                  </div>

                  {/* Form */}
                  <form onSubmit={handleSubmit} className="space-y-5">
                    <div>
                      <label
                        htmlFor="password"
                        className="block text-xs font-semibold uppercase mb-1.5"
                        style={{ color: 'rgba(20,10,12,0.5)', letterSpacing: '0.12em' }}
                      >
                        Nova Senha
                      </label>
                      <div className="relative">
                        <input
                          id="password"
                          type={showPassword ? 'text' : 'password'}
                          required
                          minLength={6}
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
                          placeholder="Minimo 6 caracteres"
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

                    <div>
                      <label
                        htmlFor="confirmPassword"
                        className="block text-xs font-semibold uppercase mb-1.5"
                        style={{ color: 'rgba(20,10,12,0.5)', letterSpacing: '0.12em' }}
                      >
                        Confirmar Nova Senha
                      </label>
                      <input
                        id="confirmPassword"
                        type={showPassword ? 'text' : 'password'}
                        required
                        minLength={6}
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
                        placeholder="Repita a nova senha"
                        value={confirmPassword}
                        onChange={(e) => setConfirmPassword(e.target.value)}
                      />
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
                          <span>Redefinindo...</span>
                        </>
                      ) : (
                        <>
                          <Lock size={18} />
                          <span>Redefinir Senha</span>
                        </>
                      )}
                    </button>
                  </form>

                  {/* Back to login */}
                  <div className="mt-8 text-center">
                    <Link
                      to="/login"
                      className="inline-flex items-center gap-2 text-sm font-semibold transition-colors"
                      style={{ color: '#7A1E26' }}
                    >
                      <ArrowLeft size={16} />
                      Voltar para o Login
                    </Link>
                  </div>
                </>
              )}
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

export default ResetPassword;
