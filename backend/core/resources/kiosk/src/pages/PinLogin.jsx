import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import { useAuthStore } from '../store/authStore';
import { api } from '../api/client';
import { Delete, ArrowRight, Store, ChevronRight } from 'lucide-react';

export default function PinLogin() {
  const [step, setStep] = useState('ruc');
  const [ruc, setRuc] = useState('');
  const [pin, setPin] = useState('');
  const [sellerData, setSellerData] = useState(null);
  const [stores, setStores] = useState([]);
  const [selectedStore, setSelectedStore] = useState(null);
  const [loading, setLoading] = useState(false);
  const login = useAuthStore((s) => s.login);
  const navigate = useNavigate();

  const handleRucKey = (key) => {
    if (ruc.length < 11) setRuc(ruc + key);
  };

  const handleRucDelete = () => {
    setRuc(ruc.slice(0, -1));
  };

  const handlePinKey = (key) => {
    if (pin.length < 10) setPin(pin + key);
  };

  const handlePinDelete = () => {
    setPin(pin.slice(0, -1));
  };

  const handleRucSubmit = async () => {
    if (ruc.length !== 11) return toast.error('El RUC debe tener 11 digitos');
    setLoading(true);
    try {
      const res = await api.lookupRuc(ruc);
      setSellerData({ id: res.data.seller_id, name: res.data.seller_name });
      setStores(res.data.stores);
      if (res.data.stores.length === 1) {
        setSelectedStore(res.data.stores[0]);
        setStep('welcome');
      } else {
        setStep('branch');
      }
    } catch (e) {
      toast.error(e.message);
    } finally {
      setLoading(false);
    }
  };

  const handleBranchSelect = (store) => {
    setSelectedStore(store);
    setStep('welcome');
  };

  const handleWelcomeDone = () => {
    setStep('pin');
  };

  const handlePinSubmit = async () => {
    if (pin.length < 4) return toast.error('El PIN debe tener al menos 4 digitos');
    setLoading(true);
    try {
      const res = await api.login(selectedStore.id, pin);
      login(res.data);
      toast.success('Bienvenido ' + res.data.seller_name);
      navigate('/');
    } catch (e) {
      toast.error(e.message);
    } finally {
      setLoading(false);
    }
  };

  const handleBackToRuc = () => {
    setStep('ruc');
    setPin('');
    setSelectedStore(null);
    setSellerData(null);
    setStores([]);
  };

  const handleBackToBranch = () => {
    setStep('branch');
    setPin('');
  };

  if (step === 'welcome') {
    return <WelcomeScreen storeName={selectedStore.name} sellerName={sellerData?.name} onDone={handleWelcomeDone} />;
  }

  return (
    <div className="kiosk-page" style={{ justifyContent: 'center', alignItems: 'center', background: '#fff' }}>
      <div style={{ width: '100%', maxWidth: 420, textAlign: 'center', padding: '0 24px' }}>
        <div style={{ marginBottom: 48 }}>
          <div style={{
            width: 80,
            height: 80,
            borderRadius: '50%',
            background: 'var(--primary)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            margin: '0 auto 20px',
            boxShadow: '0 8px 24px rgba(226, 58, 58, 0.25)',
          }}>
            <span style={{ fontSize: 36, color: '#fff', fontWeight: 800 }}>K</span>
          </div>
          <h1 style={{ fontSize: 28, fontWeight: 800, color: 'var(--text)', marginBottom: 6, letterSpacing: -0.5 }}>
            Lizto Kiosco
          </h1>
          <p style={{ color: 'var(--text-secondary)', fontSize: 16, fontWeight: 500 }}>
            {step === 'ruc' && 'Ingresa tu RUC para comenzar'}
            {step === 'branch' && 'Selecciona tu sucursal'}
            {step === 'pin' && `PIN para ${selectedStore?.name}`}
          </p>
        </div>

        {step === 'ruc' && (
          <>
            <div className="input-group" style={{ textAlign: 'left', marginBottom: 28 }}>
              <label style={{ fontSize: 13, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
                RUC
              </label>
              <input
                type="text"
                placeholder="20123456789"
                value={ruc}
                readOnly
                style={{
                  textAlign: 'center',
                  fontSize: 22,
                  fontWeight: 700,
                  padding: '18px 16px',
                  borderRadius: 'var(--radius-sm)',
                  letterSpacing: 2,
                }}
              />
            </div>

            <div style={{
              display: 'flex',
              justifyContent: 'center',
              gap: 12,
              marginBottom: 32,
              height: 44,
            }}>
              {Array.from({ length: 11 }).map((_, i) => (
                <div key={i} style={{
                  width: 30,
                  height: 44,
                  borderRadius: 8,
                  background: i < ruc.length ? 'var(--primary)' : '#f1f5f9',
                  border: `2px solid ${i < ruc.length ? 'var(--primary)' : '#e2e8f0'}`,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'all 0.15s ease',
                  fontSize: 14,
                  fontWeight: 700,
                  color: '#fff',
                }}>
                  {i < ruc.length && ruc[i]}
                </div>
              ))}
            </div>

            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(3, 1fr)',
              gap: 12,
              marginBottom: 24,
            }}>
              {[1,2,3,4,5,6,7,8,9].map((k) => (
                <button
                  key={k}
                  style={{
                    background: '#f8fafc',
                    border: '2px solid #e2e8f0',
                    borderRadius: 16,
                    fontSize: 28,
                    fontWeight: 700,
                    color: 'var(--text)',
                    minHeight: 72,
                    cursor: 'pointer',
                    fontFamily: 'inherit',
                    transition: 'all 0.1s ease',
                  }}
                  onPointerDown={() => handleRucKey(String(k))}
                >
                  {k}
                </button>
              ))}
              <button
                style={{
                  background: '#f8fafc',
                  border: '2px solid #e2e8f0',
                  borderRadius: 16,
                  fontSize: 28,
                  fontWeight: 700,
                  color: 'var(--danger)',
                  minHeight: 72,
                  cursor: 'pointer',
                  fontFamily: 'inherit',
                  transition: 'all 0.1s ease',
                }}
                onPointerDown={handleRucDelete}
              >
                <Delete size={28} />
              </button>
              <button
                style={{
                  background: '#f8fafc',
                  border: '2px solid #e2e8f0',
                  borderRadius: 16,
                  fontSize: 28,
                  fontWeight: 700,
                  color: 'var(--text)',
                  minHeight: 72,
                  cursor: 'pointer',
                  fontFamily: 'inherit',
                  transition: 'all 0.1s ease',
                }}
                onPointerDown={() => handleRucKey('0')}
              >
                0
              </button>
              <button
                className="btn btn-primary"
                style={{
                  minHeight: 72,
                  borderRadius: 16,
                  fontSize: 18,
                  fontWeight: 700,
                  boxShadow: '0 4px 16px rgba(226, 58, 58, 0.35)',
                }}
                onPointerDown={handleRucSubmit}
                disabled={loading}
              >
                {loading ? '...' : <><span>Buscar</span> <ArrowRight size={22} /></>}
              </button>
            </div>
          </>
        )}

        {step === 'branch' && (
          <>
            <div style={{ marginBottom: 24 }}>
              <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                padding: '14px 18px',
                background: '#f8fafc',
                borderRadius: 14,
                marginBottom: 20,
                textAlign: 'left',
              }}>
                <Store size={20} style={{ color: 'var(--primary)', flexShrink: 0 }} />
                <div>
                  <div style={{ fontSize: 13, color: 'var(--text-secondary)', fontWeight: 500 }}>Vendedor</div>
                  <div style={{ fontSize: 16, fontWeight: 700, color: 'var(--text)' }}>{sellerData?.name}</div>
                </div>
              </div>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: 12, marginBottom: 24 }}>
              {stores.map((store) => (
                <button
                  key={store.id}
                  onPointerDown={() => handleBranchSelect(store)}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 16,
                    padding: '18px 20px',
                    background: '#fff',
                    border: '2px solid var(--border)',
                    borderRadius: 16,
                    cursor: 'pointer',
                    fontFamily: 'inherit',
                    textAlign: 'left',
                    transition: 'all 0.15s ease',
                  }}
                >
                  <div style={{
                    width: 52,
                    height: 52,
                    borderRadius: 14,
                    background: 'var(--primary-light)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    flexShrink: 0,
                  }}>
                    <Store size={24} style={{ color: 'var(--primary)' }} />
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: 17, fontWeight: 700, color: 'var(--text)', marginBottom: 2 }}>{store.name}</div>
                    {store.address && (
                      <div style={{ fontSize: 13, color: 'var(--text-secondary)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {store.address}
                      </div>
                    )}
                  </div>
                  <ChevronRight size={22} style={{ color: 'var(--text-light)', flexShrink: 0 }} />
                </button>
              ))}
            </div>

            <button
              onClick={handleBackToRuc}
              style={{
                background: 'none',
                border: 'none',
                color: 'var(--primary)',
                fontSize: 14,
                fontWeight: 700,
                cursor: 'pointer',
                fontFamily: 'inherit',
                padding: 8,
              }}
            >
              ← Cambiar RUC
            </button>
          </>
        )}

        {step === 'pin' && (
          <>
            <div style={{ marginBottom: 20 }}>
              <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: 12,
                padding: '14px 18px',
                background: '#f8fafc',
                borderRadius: 14,
                textAlign: 'left',
              }}>
                <Store size={20} style={{ color: 'var(--primary)', flexShrink: 0 }} />
                <div style={{ flex: 1 }}>
                  <div style={{ fontSize: 13, color: 'var(--text-secondary)', fontWeight: 500 }}>Sucursal</div>
                  <div style={{ fontSize: 16, fontWeight: 700, color: 'var(--text)' }}>{selectedStore?.name}</div>
                </div>
                <button
                  onClick={handleBackToBranch}
                  style={{
                    background: 'none',
                    border: 'none',
                    color: 'var(--primary)',
                    fontSize: 12,
                    fontWeight: 700,
                    cursor: 'pointer',
                    fontFamily: 'inherit',
                  }}
                >
                  Cambiar
                </button>
              </div>
            </div>

            <div style={{
              display: 'flex',
              justifyContent: 'center',
              gap: 16,
              marginBottom: 32,
              height: 60,
            }}>
              {Array.from({ length: Math.max(pin.length, 4) }).map((_, i) => (
                <div key={i} style={{
                  width: 56,
                  height: 60,
                  borderRadius: 14,
                  background: i < pin.length ? 'var(--primary)' : '#f1f5f9',
                  border: `3px solid ${i < pin.length ? 'var(--primary)' : '#e2e8f0'}`,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'all 0.15s ease',
                }}>
                  {i < pin.length && (
                    <div style={{
                      width: 14,
                      height: 14,
                      borderRadius: '50%',
                      background: '#fff',
                    }} />
                  )}
                </div>
              ))}
            </div>

            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(3, 1fr)',
              gap: 12,
              marginBottom: 24,
            }}>
              {[1,2,3,4,5,6,7,8,9].map((k) => (
                <button
                  key={k}
                  style={{
                    background: '#f8fafc',
                    border: '2px solid #e2e8f0',
                    borderRadius: 16,
                    fontSize: 28,
                    fontWeight: 700,
                    color: 'var(--text)',
                    minHeight: 72,
                    cursor: 'pointer',
                    fontFamily: 'inherit',
                    transition: 'all 0.1s ease',
                  }}
                  onPointerDown={() => handlePinKey(String(k))}
                >
                  {k}
                </button>
              ))}
              <button
                style={{
                  background: '#f8fafc',
                  border: '2px solid #e2e8f0',
                  borderRadius: 16,
                  fontSize: 28,
                  fontWeight: 700,
                  color: 'var(--danger)',
                  minHeight: 72,
                  cursor: 'pointer',
                  fontFamily: 'inherit',
                  transition: 'all 0.1s ease',
                }}
                onPointerDown={handlePinDelete}
              >
                <Delete size={28} />
              </button>
              <button
                style={{
                  background: '#f8fafc',
                  border: '2px solid #e2e8f0',
                  borderRadius: 16,
                  fontSize: 28,
                  fontWeight: 700,
                  color: 'var(--text)',
                  minHeight: 72,
                  cursor: 'pointer',
                  fontFamily: 'inherit',
                  transition: 'all 0.1s ease',
                }}
                onPointerDown={() => handlePinKey('0')}
              >
                0
              </button>
              <button
                className="btn btn-primary"
                style={{
                  minHeight: 72,
                  borderRadius: 16,
                  fontSize: 18,
                  fontWeight: 700,
                  boxShadow: '0 4px 16px rgba(226, 58, 58, 0.35)',
                }}
                onPointerDown={handlePinSubmit}
                disabled={loading}
              >
                {loading ? '...' : <><span>Entrar</span> <ArrowRight size={22} /></>}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

function WelcomeScreen({ storeName, sellerName, onDone }) {
  const [phase, setPhase] = useState('entering');

  useEffect(() => {
    const t1 = setTimeout(() => setPhase('visible'), 100);
    const t2 = setTimeout(() => setPhase('exiting'), 2800);
    const t3 = setTimeout(() => onDone(), 3800);
    return () => { clearTimeout(t1); clearTimeout(t2); clearTimeout(t3); };
  }, [onDone]);

  return (
    <div style={{
      position: 'fixed',
      inset: 0,
      zIndex: 9999,
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      overflow: 'hidden',
      background: 'linear-gradient(165deg, #f0fdf4 0%, #dcfce7 30%, #f0fdf4 55%, #ecfdf5 100%)',
      transition: 'opacity 0.8s ease',
      opacity: phase === 'exiting' ? 0 : 1,
    }}>
      {/* Decorative orbs */}
      <div style={{
        position: 'absolute',
        width: 600,
        height: 600,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(74,222,128,0.15) 0%, transparent 70%)',
        top: '-250px',
        right: '-200px',
        animation: 'wOrb 15s ease-in-out infinite',
      }} />
      <div style={{
        position: 'absolute',
        width: 450,
        height: 450,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(34,197,94,0.1) 0%, transparent 70%)',
        bottom: '-150px',
        left: '-100px',
        animation: 'wOrb 20s ease-in-out infinite reverse',
      }} />

      <div style={{
        position: 'relative',
        zIndex: 1,
        textAlign: 'center',
        padding: '0 32px',
        transform: phase === 'entering' ? 'scale(0.85) translateY(20px)' : phase === 'exiting' ? 'scale(1.05)' : 'scale(1) translateY(0)',
        opacity: phase === 'entering' ? 0 : phase === 'exiting' ? 0 : 1,
        transition: 'all 0.8s cubic-bezier(0.22, 1, 0.36, 1)',
      }}>
        <div style={{
          width: 100,
          height: 100,
          borderRadius: '50%',
          background: '#dcfce7',
          border: '2px solid #bbf7d0',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          margin: '0 auto 32px',
          animation: phase === 'visible' ? 'wPulse 2s ease-in-out infinite' : 'none',
        }}>
          <Store size={44} color="#16a34a" />
        </div>

        <h1 style={{
          fontSize: 'clamp(32px, 6vw, 44px)',
          fontWeight: 800,
          color: '#14532d',
          marginBottom: 10,
          letterSpacing: -1,
        }}>
          Bienvenido
        </h1>
        <p style={{
          fontSize: 'clamp(20px, 3.5vw, 26px)',
          fontWeight: 700,
          color: '#16a34a',
          marginBottom: 6,
        }}>
          {storeName}
        </p>
        {sellerName && (
          <p style={{
            fontSize: 15,
            color: '#86efac',
            fontWeight: 500,
            marginTop: 8,
          }}>
            {sellerName}
          </p>
        )}
      </div>

      <style>{`
        @keyframes wPulse {
          0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(22,163,74,0.15); }
          50% { transform: scale(1.05); box-shadow: 0 0 40px 10px rgba(22,163,74,0.08); }
        }
        @keyframes wOrb {
          0%, 100% { transform: translate(0, 0); }
          50% { transform: translate(20px, -15px); }
        }
      `}</style>
    </div>
  );
}
