import React from 'react';
import './Navbar.css'; // Importe o arquivo CSS para este componente

function Navbar() {
  return (
    <div className="navbar">
      <div className="search-bar">
        <input type="text" placeholder="Buscar..." />
        <button>Buscar</button>
      </div>
      <div className="user-info">
        <span>Olá, Usuário</span>
        {/* Adicione aqui um ícone de usuário ou imagem */}
      </div>
    </div>
  );
}

export default Navbar; 