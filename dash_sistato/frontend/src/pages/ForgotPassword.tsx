import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Mail, ArrowLeft, CheckCircle } from 'lucide-react';
import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || 'http://localhost:8002';

const ForgotPassword: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await axios.post(`${API_URL}/forgot-password`, { email });
      setSent(true);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao processar solicitacao. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      {/* Top Bar */}
      <div className="bg-[#8d0f12] text-white py-2">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-center text-sm">
            <span>Sistema de Consultas - Conselho Federal de Odontologia</span>
          </div>
        </div>
      </div>

      {/* Header */}
      <header className="bg-[#F6F6F6] border-b border-[#E8E8E8] py-6">
        <div className="container mx-auto px-4">
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
      </header>

      {/* Main Content */}
      <main className="flex-grow container mx-auto px-4 py-12 flex items-center justify-center">
        <div className="max-w-md w-full">
          <div className="bg-white shadow-lg rounded-lg border-0">
            <div className="p-8">
              {sent ? (
                <div className="text-center">
                  <CheckCircle size={56} className="mx-auto text-green-500 mb-4" />
                  <h2 className="text-2xl font-bold text-gray-800 mb-2">Email Enviado!</h2>
                  <p className="text-gray-600 mb-6">
                    Se o email informado estiver cadastrado no sistema, voce recebera um link
                    para redefinir sua senha. Verifique sua caixa de entrada e pasta de spam.
                  </p>
                  <Link
                    to="/login"
                    className="inline-flex items-center gap-2 text-[#8d0f12] hover:underline font-medium"
                  >
                    <ArrowLeft size={18} />
                    Voltar para o Login
                  </Link>
                </div>
              ) : (
                <>
                  <div className="text-center mb-6">
                    <Mail size={48} className="mx-auto text-[#8d0f12] mb-4" />
                    <h2 className="text-2xl font-bold text-gray-800 mb-2">Esqueci minha senha</h2>
                    <p className="text-gray-600">
                      Informe o email cadastrado na sua conta para receber o link de recuperacao.
                    </p>
                  </div>

                  <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                      <label htmlFor="email" className="text-sm font-medium text-gray-700">
                        Email
                      </label>
                      <input
                        id="email"
                        type="email"
                        required
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8d0f12] focus:border-transparent"
                        placeholder="seu.email@exemplo.com"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                      />
                    </div>

                    {error && (
                      <div className="text-red-500 text-sm text-center">{error}</div>
                    )}

                    <button
                      type="submit"
                      disabled={loading}
                      className="w-full bg-[#8d0f12] hover:bg-[#7a0d10] text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {loading ? 'Enviando...' : 'Enviar Link de Recuperacao'}
                    </button>
                  </form>

                  <div className="mt-6 text-center">
                    <Link
                      to="/login"
                      className="inline-flex items-center gap-2 text-sm text-[#8d0f12] hover:underline"
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
      <footer className="bg-[#8d0f12] text-white py-6">
        <div className="container mx-auto px-4 text-center">
          <p className="text-sm">Copyright &copy; 2025 CFO (Conselho Federal de Odontologia)</p>
        </div>
      </footer>
    </div>
  );
};

export default ForgotPassword;
