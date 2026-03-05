import React, { useState, useRef, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { ChevronDown, User, LogOut, Settings } from 'lucide-react';

interface UserDropdownProps {
  userName: string;
  userEmail?: string;
  onLogout: () => void;
  onEditProfile: () => void;
  headerHeight?: number;
}

const MENU_WIDTH = 224;

const UserDropdown: React.FC<UserDropdownProps> = ({ userName, userEmail, onLogout, onEditProfile, headerHeight = 64 }) => {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);
  const dropdownMenuRef = useRef<HTMLDivElement>(null);
  const [menuStyles, setMenuStyles] = useState<React.CSSProperties>({});

  const toggleDropdown = () => setIsOpen(!isOpen);

  useEffect(() => {
    const handleClickOutside = (event: PointerEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node) &&
        buttonRef.current &&
        !buttonRef.current.contains(event.target as Node) &&
        dropdownMenuRef.current &&
        !dropdownMenuRef.current.contains(event.target as Node)
      ) {
        setIsOpen(false);
      }
    };
    document.addEventListener('pointerdown', handleClickOutside);
    return () => document.removeEventListener('pointerdown', handleClickOutside);
  }, []);

  useEffect(() => {
    if (isOpen && buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      const top = headerHeight + 8;
      const left = Math.min(rect.right - MENU_WIDTH, window.innerWidth - MENU_WIDTH - 8);
      setMenuStyles({
        position: 'fixed',
        top,
        left: left < 8 ? 8 : left,
        zIndex: 9999,
        width: MENU_WIDTH,
      });
    }
  }, [isOpen, headerHeight]);

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        ref={buttonRef}
        onMouseDown={e => e.stopPropagation()}
        onClick={toggleDropdown}
        className="flex items-center space-x-2 p-2 rounded-xl bg-white hover:bg-system-gray-dark transition-all duration-200 ease-in-out hover:shadow-sm"
      >
        <div className="w-8 h-8 rounded-full bg-system-wine flex items-center justify-center text-white font-bold">
          {userName ? userName.charAt(0).toUpperCase() : '?'}
        </div>
        <span className="text-sm font-medium text-gray-700">{userName}</span>
        <ChevronDown className={`h-4 w-4 text-gray-500 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && ReactDOM.createPortal(
        <div
          ref={dropdownMenuRef}
          className="bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden"
          style={menuStyles}
        >
          <div className="p-2 border-b border-gray-100">
            <p className="text-sm font-semibold text-gray-800 truncate">{userName}</p>
            <p className="text-xs text-gray-500 truncate">{userEmail || 'email@example.com'}</p>
          </div>
          <ul className="py-1">
            <li>
              <button
                onClick={() => { onEditProfile(); setIsOpen(false); }}
                className="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
              >
                <User className="w-4 h-4 mr-3 text-gray-500" />
                Editar Perfil--
              </button>
            </li>
            <li>
              <button
                onClick={() => setIsOpen(false)}
                className="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
              >
                <Settings className="w-4 h-4 mr-3 text-gray-500" />
                Preferências
              </button>
            </li>
            <li>
              <button
                onClick={() => { onLogout(); setIsOpen(false); }}
                className="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
              >
                <LogOut className="w-4 h-4 mr-3" />
                Sair
              </button>
            </li>
          </ul>
        </div>,
        document.body
      )}
    </div>
  );
};

export default UserDropdown; 