import React from 'react';
import './Card.css'; // Importe o arquivo CSS para este componente

function Card({ title, value }) {
  return (
    <div className="card">
      <h3>{title}</h3>
      <p>{value}</p>
    </div>
  );
}

export default Card; 