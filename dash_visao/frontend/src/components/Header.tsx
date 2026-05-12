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
      {/* Topbar institucional */}
      <div
        className="text-white/90 text-xs py-1.5 px-4 flex justify-between items-center"
        style={{ background: 'linear-gradient(90deg, #5C1519 0%, #7A1E26 100%)' }}
      >
        <div className="flex items-center gap-4 font-body">
          <span className="tracking-wide">(61) 3223-8800</span>
          <span className="hidden sm:inline tracking-wide">contato@cfo.org.br</span>
        </div>
        <div className="flex items-center gap-4 font-body">
          <span className="hidden sm:inline tracking-wide">Brasilia - DF</span>
          <span className="hidden sm:inline tracking-wide">Ajuda</span>
        </div>
      </div>
      {/* Main Header */}
      <header
        className="h-20 flex items-center justify-between px-2 sm:px-4"
        style={{
          backgroundColor: '#FBF8F4',
          borderBottom: '1px solid rgba(122,30,38,0.1)',
          boxShadow: '0 1px 3px rgba(122,30,38,0.04)',
        }}
      >
        {/* Esquerda */}
        <div className="flex items-center gap-2 min-w-[44px]">
          <button
            onClick={onMenuClick}
            className="p-2 rounded-xl bg-white hover:bg-cream-100 transition hover:shadow-sm"
            style={{ border: '1px solid rgba(122,30,38,0.08)' }}
          >
            <Menu className="h-5 w-5" style={{ color: '#3D0E10' }} />
          </button>
        </div>
        {/* Centro */}
        <div className="flex flex-col items-center flex-1 min-w-0">
          <img src={logoCFO} alt="Logo" className="h-12 mx-auto" />
        </div>
        {/* Direita */}
        <div className="flex items-center gap-2 min-w-[140px] justify-end">
          <button
            className="p-2 rounded-xl bg-white hover:bg-cream-100 transition hover:shadow-sm"
            style={{ border: '1px solid rgba(122,30,38,0.08)' }}
          >
            <Bell className="h-5 w-5" style={{ color: '#5C1519' }} />
          </button>
          <button
            className="p-2 rounded-xl bg-white hover:bg-cream-100 transition hover:shadow-sm"
            style={{ border: '1px solid rgba(122,30,38,0.08)' }}
          >
            <Settings className="h-5 w-5" style={{ color: '#5C1519' }} />
          </button>
          {/* User Dropdown */}
          <button
            ref={buttonRef}
            className="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-cream-100 transition hover:shadow-sm"
            style={{ border: '1px solid rgba(122,30,38,0.08)' }}
            onClick={() => setDropdownOpen((v) => !v)}
          >
            <User className="h-5 w-5" style={{ color: '#5C1519' }} />
            <span className="text-sm font-medium" style={{ color: '#0A0506' }}>{userName}</span>
          </button>
          {dropdownOpen && ReactDOM.createPortal(
            <div
              ref={menuRef}
              className="bg-white rounded-xl shadow-lg overflow-hidden"
              style={{ ...menuStyles, border: '1px solid rgba(122,30,38,0.1)' }}
            >
              <div className="p-2" style={{ borderBottom: '1px solid rgba(122,30,38,0.08)' }}>
                <p className="text-sm font-semibold truncate" style={{ color: '#0A0506' }}>{userName}</p>
                <p className="text-xs truncate" style={{ color: 'rgba(20,10,12,0.5)' }}>{userEmail}</p>
              </div>
              <ul className="py-1">
                <li>
                  <button
                    onClick={handleProfile}
                    className="w-full flex items-center px-4 py-2 text-sm transition-colors"
                    style={{ color: '#0A0506' }}
                    onMouseEnter={e => e.currentTarget.style.backgroundColor = '#F5EDE0'}
                    onMouseLeave={e => e.currentTarget.style.backgroundColor = 'transparent'}
                  >
                    <User className="w-4 h-4 mr-2" style={{ color: '#6D6E71' }} /> Meu Perfil
                  </button>
                </li>
                <li>
                  <button
                    onClick={handleLogoutClick}
                    className="w-full flex items-center px-4 py-2 text-sm transition-colors"
                    style={{ color: '#C8393F' }}
                    onMouseEnter={e => e.currentTarget.style.backgroundColor = 'rgba(200,57,63,0.06)'}
                    onMouseLeave={e => e.currentTarget.style.backgroundColor = 'transparent'}
                  >
                    <User className="w-4 h-4 mr-2" /> Sair
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
