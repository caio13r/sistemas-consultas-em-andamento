import React, { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import logoCfo from '../assets/logo.png';
import brasao from '../assets/brasao.png';
import {
  CheckCircle,
  MapPin, Phone, Mail,
} from 'lucide-react';

const TermosDeUso: React.FC = () => {
  const [accepted, setAccepted] = useState(false);
  const [scrolledToEnd, setScrolledToEnd] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);
  const navigate = useNavigate();
  const { acceptTerms, isAuthenticated } = useAuth();

  if (!isAuthenticated) {
    navigate('/login', { replace: true });
    return null;
  }

  const handleScroll = () => {
    if (scrollRef.current) {
      const { scrollTop, scrollHeight, clientHeight } = scrollRef.current;
      if (scrollTop + clientHeight >= scrollHeight - 20) {
        setScrolledToEnd(true);
      }
    }
  };

  const handleAccept = () => {
    acceptTerms();
    navigate('/', { replace: true });
  };

  const handleDecline = () => {
    navigate('/login', { replace: true });
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
                <span className="hidden md:inline">Brasília - DF</span>
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

      {/* Main */}
      <main className="flex-grow flex items-center justify-center px-4 py-8" style={{ position: 'relative', zIndex: 1 }}>
        <div className="w-full max-w-[720px]">
          <div
            className="rounded-2xl overflow-hidden flex flex-col"
            style={{
              backgroundColor: '#fff',
              boxShadow: '0 4px 24px rgba(122,30,38,0.06)',
              border: '1px solid rgba(122,30,38,0.08)',
            }}
          >
            {/* Accent bar */}
            <div className="h-1.5" style={{ background: 'linear-gradient(90deg, #5C1519, #7A1E26, #9A2832, #7A1E26, #5C1519)' }} />

            <div className="p-8 md:p-10">
              {/* Title */}
              <div className="text-center mb-6">
                <img src={brasao} alt="Brasão da República" className="h-16 w-auto object-contain mb-4 mx-auto" />
                <h2
                  className="font-display tracking-tight"
                  style={{ fontSize: 'clamp(22px, 3.5vw, 28px)', color: '#0A0506', fontWeight: 400 }}
                >
                  Termos de Uso e Política de Privacidade
                </h2>
                <p className="text-sm mt-1.5" style={{ color: 'rgba(20,10,12,0.5)' }}>
                  Leia atentamente antes de prosseguir
                </p>
              </div>

              {/* Scrollable terms */}
              <div
                ref={scrollRef}
                onScroll={handleScroll}
                className="overflow-y-auto mb-6 px-5 py-4 rounded-xl text-sm leading-relaxed"
                style={{
                  maxHeight: '380px',
                  backgroundColor: '#FBF8F4',
                  border: '1px solid rgba(122,30,38,0.1)',
                  color: '#0A0506',
                }}
              >
                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  1. Objeto
                </h3>
                <p className="mb-4">
                  Este termo regula o uso do sistema <strong>Visão CFO</strong>, mantido pelo Conselho Federal de
                  Odontologia, em conformidade com a LGPD (Lei nº 13.709/2018) e a LAI (Lei nº 12.527/2011).
                </p>

                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  2. Dados Coletados e Finalidade
                </h3>
                <p className="mb-2">
                  São coletados: nome, CPF, e-mail, cargo, vínculo institucional, registros de acesso (IP, data/hora)
                  e ações realizadas no sistema. Esses dados são utilizados exclusivamente para:
                </p>
                <ul className="list-disc pl-6 mb-4 space-y-1">
                  <li>Controle de acesso e auditoria do sistema;</li>
                  <li>Consultas e fiscalizações institucionais;</li>
                  <li>Cumprimento de obrigações legais e regulatórias.</li>
                </ul>

                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  3. Direitos do Titular (Art. 18, LGPD)
                </h3>
                <p className="mb-2">
                  Você pode solicitar a qualquer momento:
                </p>
                <ul className="list-disc pl-6 mb-4 space-y-1">
                  <li>Acesso, correção ou eliminação dos seus dados pessoais;</li>
                  <li>Informação sobre compartilhamento de dados;</li>
                  <li>Revogação do consentimento, quando aplicável.</li>
                </ul>

                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  4. Segurança e Compartilhamento
                </h3>
                <p className="mb-4">
                  O CFO adota medidas técnicas e administrativas para proteger seus dados (Art. 46, LGPD). Os dados
                  poderão ser compartilhados com órgãos públicos apenas para fins institucionais e legais (Art. 26, LGPD),
                  resguardado o sigilo previsto em lei.
                </p>

                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  5. Responsabilidades do Usuário
                </h3>
                <ul className="list-disc pl-6 mb-4 space-y-1">
                  <li>Manter sigilo sobre suas credenciais de acesso;</li>
                  <li>Utilizar o sistema exclusivamente para fins institucionais;</li>
                  <li>Não divulgar dados acessados sem autorização;</li>
                  <li>Comunicar incidentes de segurança imediatamente.</li>
                </ul>

                <h3 className="font-bold text-base mb-3" style={{ color: '#7A1E26' }}>
                  6. Registro de Atividades
                </h3>
                <p className="mb-4">
                  Todas as ações realizadas no sistema são registradas em log de auditoria, conforme os princípios
                  de responsabilização e prestação de contas da LGPD (Art. 6º, X).
                </p>

                <p className="text-xs mt-4" style={{ color: 'rgba(20,10,12,0.4)' }}>
                  Última atualização: Abril de 2026.
                </p>
              </div>

              {/* Scroll hint */}
              {!scrolledToEnd && (
                <p className="text-center text-xs mb-4" style={{ color: 'rgba(20,10,12,0.4)' }}>
                  Role até o final do documento para habilitar a aceitação
                </p>
              )}

              {/* Checkbox */}
              <label
                className="flex items-start gap-3 mb-6 cursor-pointer select-none"
                style={{ opacity: scrolledToEnd ? 1 : 0.4, pointerEvents: scrolledToEnd ? 'auto' : 'none' }}
              >
                <input
                  type="checkbox"
                  checked={accepted}
                  onChange={(e) => setAccepted(e.target.checked)}
                  className="mt-0.5 w-4 h-4 rounded"
                  style={{ accentColor: '#7A1E26' }}
                />
                <span className="text-sm" style={{ color: '#0A0506' }}>
                  Declaro que li e concordo com os <strong>Termos de Uso</strong> e a{' '}
                  <strong>Política de Privacidade</strong> do sistema Visão CFO, em conformidade com a LGPD
                  (Lei nº 13.709/2018) e a LAI (Lei nº 12.527/2011).
                </span>
              </label>

              {/* Buttons */}
              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  onClick={handleDecline}
                  className="flex-1 py-3 px-6 rounded-xl text-sm font-semibold transition-all duration-200"
                  style={{
                    border: '1px solid rgba(122,30,38,0.15)',
                    color: '#7A1E26',
                    backgroundColor: 'transparent',
                  }}
                >
                  Recusar e Sair
                </button>
                <button
                  onClick={handleAccept}
                  disabled={!accepted}
                  className="flex-1 flex items-center justify-center gap-2 py-3 px-6 rounded-xl text-white font-semibold text-sm
                    transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]"
                  style={{
                    background: accepted ? 'linear-gradient(135deg, #7A1E26 0%, #9A2832 100%)' : '#6D6E71',
                    boxShadow: accepted ? '0 4px 14px rgba(122,30,38,0.25)' : 'none',
                  }}
                >
                  <CheckCircle size={18} />
                  Aceito os Termos
                </button>
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
            Lote 2, Quadra CA-07, Centro de Atividades do Setor de Habitações
            Individuais Norte Lago Norte, Brasília – DF, CEP: 71.503-507
          </p>
        </div>
      </footer>
    </div>
  );
};

export default TermosDeUso;
