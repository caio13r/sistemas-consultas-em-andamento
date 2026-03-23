import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Lock, ArrowLeft, CheckCircle, Eye, EyeOff } from 'lucide-react';
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
      setError('Token de recuperacao invalido. Solicite um novo link.');
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
              {success ? (
                <div className="text-center">
                  <CheckCircle size={56} className="mx-auto text-green-500 mb-4" />
                  <h2 className="text-2xl font-bold text-gray-800 mb-2">Senha Redefinida!</h2>
                  <p className="text-gray-600 mb-6">
                    Sua senha foi alterada com sucesso. Voce ja pode fazer login com a nova senha.
                  </p>
                  <Link
                    to="/login"
                    className="inline-flex items-center gap-2 bg-[#8d0f12] hover:bg-[#7a0d10] text-white font-medium py-2 px-6 rounded-md transition-colors duration-200"
                  >
                    Ir para o Login
                  </Link>
                </div>
              ) : (
                <>
                  <div className="text-center mb-6">
                    <Lock size={48} className="mx-auto text-[#8d0f12] mb-4" />
                    <h2 className="text-2xl font-bold text-gray-800 mb-2">Redefinir Senha</h2>
                    <p className="text-gray-600">
                      Digite sua nova senha abaixo.
                    </p>
                  </div>

                  <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                      <label htmlFor="password" className="text-sm font-medium text-gray-700">
                        Nova Senha
                      </label>
                      <div className="relative">
                        <input
                          id="password"
                          type={showPassword ? 'text' : 'password'}
                          required
                          minLength={6}
                          className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8d0f12] focus:border-transparent"
                          placeholder="Minimo 6 caracteres"
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

                    <div className="space-y-2">
                      <label htmlFor="confirmPassword" className="text-sm font-medium text-gray-700">
                        Confirmar Nova Senha
                      </label>
                      <input
                        id="confirmPassword"
                        type={showPassword ? 'text' : 'password'}
                        required
                        minLength={6}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#8d0f12] focus:border-transparent"
                        placeholder="Repita a nova senha"
                        value={confirmPassword}
                        onChange={(e) => setConfirmPassword(e.target.value)}
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
                      {loading ? 'Redefinindo...' : 'Redefinir Senha'}
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

export default ResetPassword;
