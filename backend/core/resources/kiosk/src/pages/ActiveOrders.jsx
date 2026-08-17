import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import { api } from '../api/client';
import { ArrowLeft, RefreshCw, Clock, Package } from 'lucide-react';

const STATUS_MAP = {
  pending: { label: 'Pendiente', color: '#d97706', bg: '#fef3c7' },
  confirmed: { label: 'Confirmado', color: '#3b82f6', bg: '#dbeafe' },
  preparing: { label: 'Preparando', color: '#9333ea', bg: '#f3e8ff' },
  ready: { label: 'Listo', color: '#16a34a', bg: '#dcfce7' },
  on_way: { label: 'En Camino', color: '#3b82f6', bg: '#dbeafe' },
  delivered: { label: 'Entregado', color: '#16a34a', bg: '#dcfce7' },
  cancelled: { label: 'Cancelado', color: '#ef4444', bg: '#fee2e2' },
};

export default function ActiveOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  const storeId = useAuthStore((s) => s.storeId);
  const token = useAuthStore((s) => s.token);
  const navigate = useNavigate();

  const load = async () => {
    setLoading(true);
    try {
      const res = await api.activeOrders(storeId, token);
      setOrders(res.data.orders || []);
    } catch {}
    setLoading(false);
  };

  useEffect(() => { load(); }, [storeId, token]);

  const timeAgo = (date) => {
    const diff = Math.floor((Date.now() - new Date(date).getTime()) / 60000);
    if (diff < 1) return 'Ahora';
    if (diff < 60) return `${diff}m`;
    return `${Math.floor(diff / 60)}h ${diff % 60}m`;
  };

  return (
    <div className="kiosk-page">
      <div className="kiosk-header">
        <button className="btn btn-secondary btn-sm" onPointerDown={() => navigate('/')}>
          <ArrowLeft size={18} /> Volver
        </button>
        <h1>Pedidos Activos</h1>
        <button className="btn btn-secondary btn-sm" onPointerDown={load} style={{ gap: 6 }}>
          <RefreshCw size={18} /> Actualizar
        </button>
      </div>

      <div className="kiosk-body">
        {loading ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-secondary)', fontSize: 18 }}>Cargando...</div>
        ) : orders.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-secondary)' }}>
            <Package size={56} style={{ marginBottom: 16, opacity: 0.2 }} />
            <p style={{ fontSize: 18 }}>No hay pedidos activos hoy</p>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 16 }}>
            {orders.map((o) => {
              const st = STATUS_MAP[o.status] || STATUS_MAP.pending;
              return (
                <div key={o.id} style={{
                  background: '#fff',
                  borderRadius: 20,
                  padding: 20,
                  border: '1px solid var(--border)',
                  boxShadow: 'var(--shadow-sm)',
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: 12 }}>
                    <div>
                      <div style={{ fontSize: 18, fontWeight: 800, color: 'var(--text)' }}>#{o.order_no}</div>
                      <div style={{ fontSize: 14, color: 'var(--text-secondary)', marginTop: 2 }}>
                        {o.contact_name || 'Sin nombre'}
                      </div>
                    </div>
                    <span style={{
                      fontSize: 12, fontWeight: 700,
                      padding: '6px 14px',
                      borderRadius: 20,
                      background: st.bg,
                      color: st.color,
                      textTransform: 'uppercase',
                      letterSpacing: 0.3,
                    }}>
                      {st.label}
                    </span>
                  </div>

                  <div style={{ fontSize: 14, color: 'var(--text-secondary)', marginBottom: 12 }}>
                    {o.items?.length || 0} items
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontSize: 22, fontWeight: 800, color: 'var(--primary)' }}>
                      S/ {Number(o.total).toFixed(2)}
                    </span>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 13, color: 'var(--text-secondary)' }}>
                      <Clock size={14} />
                      {timeAgo(o.created_at)}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
