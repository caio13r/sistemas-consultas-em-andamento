import React from 'react';

const BackgroundEffect: React.FC = () => (
  <>
    <style>{`
      @keyframes float1 { 0%,100% { transform: translate(0,0) rotate(0deg); } 50% { transform: translate(30px,-40px) rotate(180deg); } }
      @keyframes float2 { 0%,100% { transform: translate(0,0) rotate(0deg); } 50% { transform: translate(-40px,30px) rotate(-180deg); } }
      @keyframes float3 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(20px,50px) scale(1.2); } }
      @keyframes pulse1 { 0%,100% { opacity:0.15; transform:scale(1); } 50% { opacity:0.3; transform:scale(1.15); } }
    `}</style>
    <div className="fixed inset-0 pointer-events-none overflow-hidden" style={{ zIndex: 0 }}>
      <div className="absolute w-32 h-32 border-2 rounded-2xl" style={{ borderColor: 'rgba(122,30,38,0.08)', top:'15%', left:'8%', animation:'float1 25s ease-in-out infinite', transform:'rotate(45deg)' }} />
      <div className="absolute w-20 h-20 border-2 rounded-xl" style={{ borderColor: 'rgba(122,30,38,0.07)', bottom:'30%', right:'12%', animation:'float2 22s ease-in-out infinite' }} />
      <div className="absolute w-16 h-16 border-2 rounded-lg" style={{ borderColor: 'rgba(154,40,50,0.06)', top:'60%', left:'35%', animation:'float3 19s ease-in-out infinite' }} />
      <div className="absolute w-24 h-24 border-2 rounded-full" style={{ borderColor: 'rgba(122,30,38,0.07)', top:'8%', right:'30%', animation:'float2 18s ease-in-out infinite' }} />
      <div className="absolute w-40 h-40 border-2 rounded-3xl" style={{ borderColor: 'rgba(92,21,25,0.05)', bottom:'12%', left:'20%', animation:'float1 28s ease-in-out infinite', transform:'rotate(30deg)' }} />
      <div className="absolute w-12 h-12 border-2 rounded-md" style={{ borderColor: 'rgba(154,40,50,0.08)', top:'40%', right:'8%', animation:'float3 16s ease-in-out infinite', transform:'rotate(60deg)' }} />
      <div className="absolute w-28 h-28 border-2 rounded-full" style={{ borderColor: 'rgba(122,30,38,0.05)', bottom:'40%', left:'5%', animation:'pulse1 14s ease-in-out infinite' }} />
      <div className="absolute w-10 h-10 border-2 rounded-lg" style={{ borderColor: 'rgba(184,138,86,0.1)', top:'25%', left:'45%', animation:'float2 20s ease-in-out infinite', transform:'rotate(15deg)' }} />
      <div className="absolute w-36 h-36 border-2 rounded-2xl" style={{ borderColor: 'rgba(92,21,25,0.04)', top:'5%', left:'60%', animation:'float3 30s ease-in-out infinite', transform:'rotate(20deg)' }} />
      <div className="absolute w-14 h-14 border-2 rounded-full" style={{ borderColor: 'rgba(122,30,38,0.07)', bottom:'15%', right:'40%', animation:'float1 17s ease-in-out infinite' }} />
      <div className="absolute w-8 h-8 border-2 rounded-md" style={{ borderColor: 'rgba(184,138,86,0.08)', top:'70%', right:'25%', animation:'pulse1 10s ease-in-out infinite 1s', transform:'rotate(45deg)' }} />
      <div className="absolute w-44 h-44 border-2 rounded-full" style={{ borderColor: 'rgba(92,21,25,0.03)', top:'45%', left:'70%', animation:'float2 26s ease-in-out infinite' }} />
      <div className="absolute w-18 h-18 border-2 rounded-xl" style={{ borderColor: 'rgba(122,30,38,0.06)', bottom:'8%', right:'5%', animation:'float3 21s ease-in-out infinite', transform:'rotate(35deg)' }} />
    </div>
  </>
);

export default BackgroundEffect;
