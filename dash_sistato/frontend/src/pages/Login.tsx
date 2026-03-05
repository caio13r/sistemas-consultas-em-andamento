import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { MapPin, Phone, Mail, Facebook, Instagram, Twitter, Youtube, Eye, EyeOff } from 'lucide-react';

const Login: React.FC = () => {
  const [username, setUsername] = useState('');
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
      await login(username, password);
      navigate('/users');
    } catch (err: any) {
      setError(err.message || 'Erro ao fazer login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      {/* Top Bar */}
      <div className="bg-[#8d0f12] text-white py-2">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center text-sm">
            <div className="flex flex-wrap items-center gap-4 mb-2 md:mb-0">
              <div className="flex items-center gap-1">
                <MapPin size={14} />
                <span className="hidden md:inline">Brasília - DF</span>
              </div>
              <div className="flex items-center gap-1">
                <Phone size={14} />
                <span className="hidden md:inline">(61) 3033-4499 / 3033-4469</span>
              </div>
              <div className="flex items-center gap-1">
                <Mail size={14} />
                <span className="hidden md:inline">cfo@cfo.org.br</span>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <Facebook size={16} className="hover:opacity-80 cursor-pointer transition-opacity" />
              <Instagram size={16} className="hover:opacity-80 cursor-pointer transition-opacity" />
              <Twitter size={16} className="hover:opacity-80 cursor-pointer transition-opacity" />
              <Youtube size={16} className="hover:opacity-80 cursor-pointer transition-opacity" />
            </div>
          </div>
        </div>
      </div>

      {/* Header */}
      <header className="bg-[#F6F6F6] border-b border-[#E8E8E8] py-6">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <div className="bg-white p-3 rounded-lg shadow-sm">
                <div className="w-12 h-12 bg-[#8d0f12] rounded flex items-center justify-center">
                  <span className="text-white font-bold text-xl">CFO</span>
                </div>
              </div>
              <div className="ml-4">
                <h1 className="text-xl font-bold text-gray-800">Sistema de Consultas</h1>
                <p className="text-sm text-gray-600">Conselho Federal de Odontologia</p>
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <div className="max-w-md w-full">
          <div className="bg-white shadow-lg rounded-lg border-0">
            <div className="p-8 text-center">
              <h2 className="text-2xl font-bold text-gray-800 mb-2">
                Acesso ao Sistema
              </h2>
              <p className="text-gray-600 mb-8">
                Faça login para acessar o sistema de consultas do CFO
              </p>
            </div>
            <div className="px-8 pb-8">
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-2">
                  <label htmlFor="username" className="text-sm font-medium text-gray-700">
                    Usuário
                  </label>
                  <input
                    id="username"
                    name="username"
                    type="text"
                    required
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8d0f12] focus:border-transparent"
                    placeholder="Digite seu usuário"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                  />
                </div>
                
                <div className="space-y-2">
                  <label htmlFor="password" className="text-sm font-medium text-gray-700">
                    Senha
                  </label>
                  <div className="relative">
                    <input
                      id="password"
                      name="password"
                      type={showPassword ? "text" : "password"}
                      required
                      className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8d0f12] focus:border-transparent"
                      placeholder="Digite sua senha"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    >
                      {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                    </button>
                  </div>
                </div>

                {error && (
                  <div className="text-red-500 text-sm text-center">{error}</div>
                )}

                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <input
                      id="remember"
                      name="remember"
                      type="checkbox"
                      className="h-4 w-4 text-[#8d0f12] focus:ring-[#8d0f12] border-gray-300 rounded"
                    />
                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                      Lembrar-me
                    </label>
                  </div>
                  <a href="/forgot-password" className="text-sm text-[#8d0f12] hover:underline">
                    Esqueci minha senha
                  </a>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-[#8d0f12] hover:bg-[#7a0d10] disabled:opacity-60 disabled:cursor-not-allowed text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                >
                  {loading ? 'Entrando...' : 'Entrar'}
                </button>
              </form>

              <div className="mt-8 pt-6 border-t border-gray-200 text-center">
                <p className="text-sm text-gray-600 mb-4">
                  Precisa de ajuda para acessar o sistema?
                </p>
                <div className="space-y-2">
                  <a
                    href="/solicitar-usuario"
                    className="block text-sm text-[#8d0f12] hover:underline"
                  >
                    Solicitar novo usuário
                  </a>
                  <a
                    href="/status-solicitacao"
                    className="block text-sm text-[#8d0f12] hover:underline"
                  >
                    Verificar status da solicitação
                  </a>
                  <a
                    href="#"
                    className="block text-sm text-[#8d0f12] hover:underline"
                  >
                    Manual do sistema
                  </a>
                </div>
              </div>
            </div>
          </div>

          {/* Additional Info */}
          <div className="mt-8 text-center">
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
              <h3 className="font-semibold text-gray-800 mb-2">
                Sistema de Consultas CFO
              </h3>
              <p className="text-sm text-gray-600 leading-relaxed">
                O sistema de consultas do CFO permite acesso a dados de
                profissionais, auditorias, fiscalização, estatísticas e
                demais informações do Conselho Federal de Odontologia.
              </p>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-[#8d0f12] text-white py-8">
        <div className="container mx-auto px-4 text-center">
          <p className="text-sm mb-2">
            Copyright © 2025 CFO (Conselho Federal de Odontologia)
          </p>
          <p className="text-xs opacity-90">
            Lote 2, Quadra CA-07, Centro de Atividades do Setor de Habitações 
            Individuais Norte Lago Norte, Brasília – DF, CEP: 71.503-507
          </p>
          <p className="text-xs opacity-90 mt-1">
            Horário de atendimento: De segunda a sexta, das 08:00 às 17:00 horas
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Login; 