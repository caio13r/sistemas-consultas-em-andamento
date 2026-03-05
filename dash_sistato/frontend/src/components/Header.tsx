import React, { useState, useRef, useEffect } from 'react';
import { Bell, Settings, User, Menu } from 'lucide-react';
import ReactDOM from 'react-dom';
import { useNavigate } from 'react-router-dom';
import logoCFO from '../assets/logo.png';

interface HeaderProps {
  onMenuClick?: () => void;
  userName: string;
  userEmail: string;
  onLogout: () => void;
  onEditProfile: () => void;
}

const MENU_WIDTH = 224;

const Header: React.FC<HeaderProps> = ({
  onMenuClick,
  userName,
  userEmail,
  onLogout,
  onEditProfile,
}) => {
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const buttonRef = useRef<HTMLButtonElement>(null);
  const [menuStyles, setMenuStyles] = useState<React.CSSProperties>({});
  const navigate = useNavigate();
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: PointerEvent) => {
      if (
        buttonRef.current &&
        !buttonRef.current.contains(event.target as Node) &&
        menuRef.current &&
        !menuRef.current.contains(event.target as Node)
      ) {
        setDropdownOpen(false);
      }
    };
    if (dropdownOpen) {
      document.addEventListener('pointerdown', handleClickOutside);
    } else {
      document.removeEventListener('pointerdown', handleClickOutside);
    }
    return () => document.removeEventListener('pointerdown', handleClickOutside);
  }, [dropdownOpen]);

  useEffect(() => {
    if (dropdownOpen && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      const top = rect.bottom + 8;
      const left = Math.min(rect.right - MENU_WIDTH, window.innerWidth - MENU_WIDTH - 8);
      setMenuStyles({
        position: 'fixed',
        top,
        left: left < 8 ? 8 : left,
        zIndex: 2147483647,
        width: MENU_WIDTH,
        pointerEvents: 'auto',
      });
    }
  }, [dropdownOpen]);

  // Handlers para garantir navegação
  const handleProfile = () => {
    setDropdownOpen(false);
    navigate('/edit-profile');
  };
  const handleLogoutClick = () => {
    setDropdownOpen(false);
    onLogout();
    navigate('/login');
  };

  return (
    <div className="w-full">
      {/* Topbar */}
      <div className="bg-system-wine text-white text-xs py-1 px-4 flex justify-between items-center">
        <div className="flex items-center gap-4">
          <span><i className="fa fa-phone-alt mr-1"></i> (61) 3223-8800</span>
          <span className="hidden sm:inline"><i className="fa fa-envelope mr-1"></i> contato@cfo.org.br</span>
        </div>
        <div className="flex items-center gap-4">
          <span className="hidden sm:inline"><i className="fa fa-map-marker-alt mr-1"></i> Brasília - DF</span>
          <span className="hidden sm:inline"><i className="fa fa-question-circle mr-1"></i> Ajuda</span>
        </div>
      </div>
      {/* Main Header */}
      <header className="bg-system-gray border-b border-gray-200 h-20 flex items-center justify-between shadow-sm px-2 sm:px-4">
        {/* Esquerda */}
        <div className="flex items-center gap-2 min-w-[44px]">
          <button onClick={onMenuClick} className="p-2 rounded-xl bg-white hover:bg-system-gray-dark transition hover:shadow-sm">
            <Menu className="h-5 w-5 text-gray-700" />
          </button>
        </div>
        {/* Centro */}
        <div className="flex flex-col items-center flex-1 min-w-0">
          <img src={logoCFO} alt="Logo" className="h-12 mx-auto" />
        </div>
        {/* Direita */}
        <div className="flex items-center gap-2 min-w-[140px] justify-end">
          <button className="p-2 rounded-xl bg-white hover:bg-system-gray-dark transition hover:shadow-sm">
            <Bell className="h-5 w-5 text-gray-700" />
          </button>
          <button className="p-2 rounded-xl bg-white hover:bg-system-gray-dark transition hover:shadow-sm">
            <Settings className="h-5 w-5 text-gray-700" />
          </button>
          {/* User Dropdown */}
          <button
            ref={buttonRef}
            className="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-system-gray-dark transition hover:shadow-sm"
            onClick={() => setDropdownOpen((v) => !v)}
          >
            <User className="h-5 w-5 text-gray-700" />
            <span className="text-sm font-medium text-gray-700">{userName}</span>
          </button>
          {dropdownOpen && ReactDOM.createPortal(
            <div
              ref={menuRef}
              className="bg-white rounded-xl shadow-lg border border-gray-100 z-[99999] overflow-hidden"
              style={menuStyles}
            >
              <div className="p-2 border-b border-gray-100">
                <p className="text-sm font-semibold text-gray-800 truncate">{userName}</p>
                <p className="text-xs text-gray-500 truncate">{userEmail}</p>
              </div>
              <ul className="py-1">
                <li>
                  <button
                    onClick={handleProfile}
                    className="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                  >
                    <User className="w-4 h-4 mr-2 text-gray-500" /> Meu Perfil
                  </button>
                </li>
                <li>
                  <button
                    onClick={handleLogoutClick}
                    className="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <i className="fa fa-sign-out-alt w-4 h-4 mr-2" /> Sair
                  </button>
                </li>
              </ul>
            </div>,
            document.body
          )}
        </div>
      </header>
    </div>
  );
};

export default Header; 