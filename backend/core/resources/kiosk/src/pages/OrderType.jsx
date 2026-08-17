import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';
import { useAuthStore } from '../store/authStore';
import { Truck, ShoppingBag, UtensilsCrossed, ClipboardList, LogOut } from 'lucide-react';

const TYPES = [
  { key: 'delivery', label: 'Delivery', icon: Truck, desc: 'Enviar a dirección', accent: '#16a34a', accentBg: '#dcfce7' },
  { key: 'takeaway', label: 'Para Llevar', icon: ShoppingBag, desc: 'Recoger en tienda', accent: '#ca8a04', accentBg: '#fef9c3' },
  { key: 'dine_in', label: 'En Mesa', icon: UtensilsCrossed, desc: 'Servir en mesa', accent: '#0284c7', accentBg: '#e0f2fe' },
];

export default function OrderType() {
  const setOrderType = useCartStore((s) => s.setOrderType);
  const storeName = useAuthStore((s) => s.storeName);
  const token = useAuthStore((s) => s.token);
  const logout = useAuthStore((s) => s.logout);
  const navigate = useNavigate();
  const [entered, setEntered] = useState(false);

  useEffect(() => {
    const t = setTimeout(() => setEntered(true), 50);
    return () => clearTimeout(t);
  }, []);

  const select = (type) => {
    setOrderType(type);
    if (type === 'dine_in') navigate('/tables');
    else if (type === 'delivery') navigate('/address');
    else navigate('/menu');
  };

  const handleLogout = async () => {
    try { await api.logout(token); } catch {}
    logout();
    navigate('/login');
  };

  return (
    <div style={{
      position: 'fixed',
      inset: 0,
      display: 'flex',
      flexDirection: 'column',
      overflow: 'hidden',
      background: 'linear-gradient(165deg, #f0fdf4 0%, #dcfce7 30%, #f0fdf4 55%, #ecfdf5 100%)',
    }}>
      {/* Subtle decorative elements */}
      <div style={{
        position: 'absolute',
        width: 700,
        height: 700,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(74,222,128,0.12) 0%, transparent 70%)',
        top: '-300px',
        right: '-200px',
        animation: 'orbFloat 20s ease-in-out infinite',
      }} />
      <div style={{
        position: 'absolute',
        width: 500,
        height: 500,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%)',
        bottom: '-200px',
        left: '-150px',
        animation: 'orbFloat 25s ease-in-out infinite reverse',
      }} />

      {/* Header */}
      <div style={{
        position: 'relative',
        zIndex: 2,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '16px 28px',
        background: 'rgba(255,255,255,0.7)',
        backdropFilter: 'blur(16px)',
        borderBottom: '1px solid rgba(22,163,74,0.1)',
        flexShrink: 0,
      }}>
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 800, color: '#14532d', margin: 0, letterSpacing: -0.3 }}>Lizto Kiosco</h1>
          <div style={{ fontSize: 13, color: '#4ade80', fontWeight: 600, marginTop: 2 }}>{storeName}</div>
        </div>
        <div style={{ display: 'flex', gap: 10 }}>
          <button
            onClick={() => navigate('/active')}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 8,
              padding: '10px 18px',
              background: '#fff',
              border: '1.5px solid #bbf7d0',
              borderRadius: 12,
              color: '#16a34a',
              fontSize: 14,
              fontWeight: 700,
              cursor: 'pointer',
              fontFamily: 'inherit',
              transition: 'all 0.2s',
              boxShadow: '0 1px 3px rgba(22,163,74,0.08)',
            }}
          >
            <ClipboardList size={18} /> Pedidos
          </button>
          <button
            onClick={handleLogout}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: 42,
              height: 42,
              padding: 0,
              background: '#fff',
              border: '1.5px solid #e2e8f0',
              borderRadius: 12,
              color: '#94a3b8',
              cursor: 'pointer',
              transition: 'all 0.2s',
            }}
          >
            <LogOut size={18} />
          </button>
        </div>
      </div>

      {/* Body */}
      <div style={{
        position: 'relative',
        zIndex: 2,
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        padding: '32px 24px 24px',
        overflow: 'auto',
      }}>
        {/* Title */}
        <div style={{
          textAlign: 'center',
          marginBottom: 56,
          transform: entered ? 'translateY(0)' : 'translateY(24px)',
          opacity: entered ? 1 : 0,
          transition: 'all 0.7s cubic-bezier(0.22, 1, 0.36, 1)',
        }}>
          <h2 style={{
            fontSize: 'clamp(28px, 5vw, 40px)',
            fontWeight: 800,
            color: '#14532d',
            marginBottom: 10,
            textAlign: 'center',
            letterSpacing: -1,
          }}>
            Nuevo Pedido
          </h2>
          <p style={{
            color: '#4ade80',
            fontSize: 'clamp(15px, 2.5vw, 18px)',
            textAlign: 'center',
            fontWeight: 600,
          }}>
            Selecciona el tipo de pedido
          </p>
        </div>

        {/* Cards */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))',
          gap: 20,
          width: '100%',
          maxWidth: 860,
        }}>
          {TYPES.map((t, idx) => (
            <button
              key={t.key}
              onPointerDown={() => select(t.key)}
              style={{
                background: '#fff',
                border: '1.5px solid #e2e8f0',
                borderRadius: 24,
                padding: '48px 24px 40px',
                cursor: 'pointer',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: 20,
                transition: 'all 0.3s cubic-bezier(0.22, 1, 0.36, 1)',
                transform: entered ? 'translateY(0)' : 'translateY(30px)',
                opacity: entered ? 1 : 0,
                transitionDelay: `${0.1 + idx * 0.1}s`,
                boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
                position: 'relative',
                overflow: 'hidden',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.borderColor = t.accent;
                e.currentTarget.style.transform = 'translateY(-6px)';
                e.currentTarget.style.boxShadow = `0 20px 40px ${t.accent}18, 0 4px 12px rgba(0,0,0,0.06)`;
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.borderColor = '#e2e8f0';
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)';
              }}
            >
              {/* Top accent line */}
              <div style={{
                position: 'absolute',
                top: 0,
                left: 0,
                right: 0,
                height: 3,
                background: `linear-gradient(90deg, transparent, ${t.accent}, transparent)`,
                opacity: 0,
                transition: 'opacity 0.3s',
              }} />

              <div style={{
                width: 88,
                height: 88,
                borderRadius: 22,
                background: t.accentBg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.3s',
              }}>
                <t.icon size={40} color={t.accent} strokeWidth={1.5} />
              </div>

              <div>
                <div style={{
                  fontSize: 21,
                  fontWeight: 800,
                  color: '#1e293b',
                  marginBottom: 6,
                  letterSpacing: -0.3,
                }}>
                  {t.label}
                </div>
                <div style={{
                  fontSize: 14,
                  color: '#94a3b8',
                  fontWeight: 500,
                }}>
                  {t.desc}
                </div>
              </div>
            </button>
          ))}
        </div>
      </div>

      <style>{`
        @keyframes orbFloat {
          0%, 100% { transform: translate(0, 0); }
          50% { transform: translate(30px, -20px); }
        }
      `}</style>
    </div>
  );
}
