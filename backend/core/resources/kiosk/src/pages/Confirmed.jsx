import { useParams, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { useAuthStore } from '../store/authStore';
import { CheckCircle, Home, ClipboardList } from 'lucide-react';

export default function Confirmed() {
  const { orderId } = useParams();
  const storeName = useAuthStore((s) => s.storeName);
  const navigate = useNavigate();
  const [show, setShow] = useState(false);

  useEffect(() => {
    setTimeout(() => setShow(true), 100);
  }, []);

  return (
    <div className="kiosk-page" style={{ justifyContent: 'center', alignItems: 'center', background: '#fff' }}>
      <div style={{
        textAlign: 'center',
        maxWidth: 420,
        opacity: show ? 1 : 0,
        transform: show ? 'scale(1) translateY(0)' : 'scale(0.8) translateY(20px)',
        transition: 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)',
        padding: '0 24px',
      }}>
        {/* Success circle */}
        <div style={{
          width: 120,
          height: 120,
          borderRadius: '50%',
          background: 'var(--success-light)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          margin: '0 auto 28px',
          boxShadow: '0 8px 32px rgba(22, 163, 74, 0.2)',
        }}>
          <CheckCircle size={64} color="var(--success)" strokeWidth={2.5} />
        </div>

        <h1 style={{ fontSize: 34, fontWeight: 800, marginBottom: 8, color: 'var(--text)', letterSpacing: -0.5 }}>
          Pedido Confirmado
        </h1>
        <p style={{ color: 'var(--text-secondary)', fontSize: 18, marginBottom: 8, fontWeight: 500 }}>
          {storeName}
        </p>
        <p style={{ fontSize: 24, fontWeight: 800, color: 'var(--primary)', marginBottom: 40 }}>
          #{orderId}
        </p>

        <div style={{ display: 'flex', gap: 14, justifyContent: 'center' }}>
          <button
            className="btn btn-primary"
            style={{ width: 'auto', padding: '18px 40px', borderRadius: 16, fontSize: 18, minHeight: 64 }}
            onPointerDown={() => navigate('/')}
          >
            <Home size={20} /> Nuevo Pedido
          </button>
          <button
            className="btn btn-secondary"
            style={{ width: 'auto', padding: '18px 40px', borderRadius: 16, fontSize: 18, minHeight: 64 }}
            onPointerDown={() => navigate('/active')}
          >
            <ClipboardList size={20} /> Ver Pedidos
          </button>
        </div>
      </div>
    </div>
  );
}
