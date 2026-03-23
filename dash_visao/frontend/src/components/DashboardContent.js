import React from 'react';
import Card from './Card';
import './DashboardContent.css'; // Importe o arquivo CSS para este componente

function DashboardContent() {
  return (
    <div className="dashboard-content">
      <div className="cards-grid">
        <Card title="Total de Vendas" value="R$ 10.000" />
        <Card title="Novos Usuários" value="50" />
        <Card title="Produtos em Estoque" value="120" />
        <Card title="Média de Pedidos" value="R$ 50" />
        {/* Adicione mais cards conforme necessário */}
      </div>
      {/* Outros elementos do conteúdo do painel podem ir aqui */}
    </div>
  );
}

export default DashboardContent; 