import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';
import { useAuthStore } from '../store/authStore';
import { api } from '../api/client';
import { ArrowLeft, Users, MapPin } from 'lucide-react';

export default function TableSelect() {
  const [areas, setAreas] = useState([]);
  const [selectedArea, setSelectedArea] = useState(null);
  const [loading, setLoading] = useState(true);
  const storeId = useAuthStore((s) => s.storeId);
  const token = useAuthStore((s) => s.token);
  const setTable = useCartStore((s) => s.setTable);
  const navigate = useNavigate();

  useEffect(() => {
    api.tables(storeId, token).then((res) => {
      const data = res.data.areas || [];
      setAreas(data);
      if (data.length === 1) setSelectedArea(data[0]);
      setLoading(false);
    }).catch(() => setLoading(false));
  }, [storeId, token]);

  const selectTable = (table) => {
    if (table.status === 'occupied') return;
    setTable(table.id, table.name);
    navigate('/menu');
  };

  const selectedAreaData = areas.find((a) => a.id === selectedArea?.id);

  return (
    <div className="kiosk-page">
      <div className="kiosk-header">
        <button className="btn btn-secondary btn-sm" onPointerDown={() => {
          if (selectedArea && areas.length > 1) setSelectedArea(null);
          else navigate('/');
        }}>
          <ArrowLeft size={18} /> {selectedArea && areas.length > 1 ? 'Areas' : 'Volver'}
        </button>
        <h1>{selectedArea ? selectedArea.name : 'Seleccionar Mesa'}</h1>
        <div />
      </div>

      <div className="kiosk-body">
        {loading ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-secondary)', fontSize: 18 }}>
            Cargando mesas...
          </div>
        ) : areas.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-secondary)', fontSize: 18 }}>
            No hay mesas configuradas
          </div>
        ) : !selectedArea && areas.length > 1 ? (
          /* AREA SELECTION */
          <div>
            <div style={{ textAlign: 'center', marginBottom: 32 }}>
              <h2 style={{ fontSize: 26, fontWeight: 800, color: 'var(--text)', marginBottom: 6 }}>
                Selecciona un area
              </h2>
              <p style={{ color: 'var(--text-secondary)', fontSize: 16 }}>
                Elige la zona del restaurante
              </p>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 16 }}>
              {areas.map((area) => {
                const freeCount = area.tables.filter((t) => t.status !== 'occupied').length;
                const totalCount = area.tables.length;
                return (
                  <button
                    key={area.id}
                    onPointerDown={() => setSelectedArea(area)}
                    style={{
                      background: '#fff',
                      border: '3px solid var(--border)',
                      borderRadius: 20,
                      padding: '32px 20px',
                      cursor: 'pointer',
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      gap: 14,
                      transition: 'all 0.15s',
                      boxShadow: 'var(--shadow-sm)',
                    }}
                  >
                    <div style={{
                      width: 72,
                      height: 72,
                      borderRadius: 18,
                      background: freeCount > 0 ? 'var(--primary-light)' : 'var(--danger-light)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}>
                      <MapPin size={36} color={freeCount > 0 ? 'var(--primary)' : 'var(--danger)'} />
                    </div>
                    <div style={{ fontSize: 20, fontWeight: 800, color: 'var(--text)' }}>{area.name}</div>
                    <div style={{ fontSize: 14, color: 'var(--text-secondary)', fontWeight: 600 }}>
                      {freeCount} de {totalCount} libres
                    </div>
                  </button>
                );
              })}
            </div>
          </div>
        ) : (
          /* TABLE GRID */
          <div>
            {areas.length > 1 && !selectedArea && (
              <div style={{ marginBottom: 20 }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: 10, marginBottom: 16 }}>
                  {areas.map((a) => (
                    <button
                      key={a.id}
                      onPointerDown={() => setSelectedArea(a)}
                      style={{
                        padding: '10px 16px',
                        background: selectedArea?.id === a.id ? 'var(--primary)' : '#f1f5f9',
                        border: 'none',
                        borderRadius: 10,
                        color: selectedArea?.id === a.id ? '#fff' : 'var(--text)',
                        fontFamily: 'inherit',
                        fontSize: 14,
                        fontWeight: 700,
                        cursor: 'pointer',
                      }}
                    >
                      {a.name}
                    </button>
                  ))}
                </div>
              </div>
            )}

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: 14 }}>
              {(selectedAreaData?.tables || areas.flatMap((a) => a.tables)).map((t) => {
                const isFree = t.status !== 'occupied';
                return (
                  <button
                    key={t.id}
                    onPointerDown={() => selectTable(t)}
                    style={{
                      background: isFree ? '#fff' : '#fef2f2',
                      border: `3px solid ${isFree ? '#e2e8f0' : '#fecaca'}`,
                      borderRadius: 18,
                      padding: '28px 14px',
                      cursor: isFree ? 'pointer' : 'not-allowed',
                      opacity: isFree ? 1 : 0.5,
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      gap: 10,
                      transition: 'all 0.15s',
                      boxShadow: isFree ? 'var(--shadow-sm)' : 'none',
                    }}
                  >
                    <div style={{
                      width: 56,
                      height: 56,
                      borderRadius: 14,
                      background: isFree ? 'var(--success-light)' : 'var(--danger-light)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}>
                      <Users size={28} color={isFree ? 'var(--success)' : 'var(--danger)'} />
                    </div>
                    <div style={{ fontSize: 18, fontWeight: 800, color: 'var(--text)' }}>{t.name}</div>
                    <div style={{
                      fontSize: 12,
                      fontWeight: 700,
                      color: isFree ? 'var(--success)' : 'var(--danger)',
                      textTransform: 'uppercase',
                      letterSpacing: 0.5,
                    }}>
                      {isFree ? 'Libre' : 'Ocupada'}
                    </div>
                    <div style={{ fontSize: 12, color: 'var(--text-secondary)' }}>
                      {t.capacity} personas
                    </div>
                  </button>
                );
              })}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
