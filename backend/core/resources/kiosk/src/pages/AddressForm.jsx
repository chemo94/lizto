import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';
import { ArrowLeft, MapPin } from 'lucide-react';

export default function AddressForm() {
  const setCustomer = useCartStore((s) => s.setCustomer);
  const setAddress = useCartStore((s) => s.setAddress);
  const navigate = useNavigate();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddressState] = useState('');
  const [notes, setNotes] = useState('');

  const handleContinue = () => {
    if (!name.trim()) return;
    if (!address.trim()) return;
    setCustomer(name.trim(), phone.trim());
    setAddress(address.trim(), null, null);
    useCartStore.getState().setNotes(notes);
    navigate('/menu');
  };

  return (
    <div className="kiosk-page">
      <div className="kiosk-header">
        <button className="btn btn-secondary btn-sm" onPointerDown={() => navigate('/')}>
          <ArrowLeft size={18} /> Volver
        </button>
        <h1>Direccion de Entrega</h1>
        <div />
      </div>

      <div className="kiosk-body" style={{ display: 'flex', justifyContent: 'center' }}>
        <div style={{ width: '100%', maxWidth: 500 }}>
          {/* Icon */}
          <div style={{ textAlign: 'center', marginBottom: 32 }}>
            <div style={{
              width: 80,
              height: 80,
              borderRadius: 20,
              background: 'var(--primary-light)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
            }}>
              <MapPin size={40} color="var(--primary)" />
            </div>
            <h2 style={{ fontSize: 22, fontWeight: 800, color: 'var(--text)' }}>Datos del Cliente</h2>
            <p style={{ color: 'var(--text-secondary)', fontSize: 15, marginTop: 4 }}>Completa la información para el delivery</p>
          </div>

          <div className="input-group">
            <label style={{ fontSize: 13, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
              Nombre del cliente
            </label>
            <input
              type="text"
              placeholder="Ej: Juan Perez"
              value={name}
              onChange={(e) => setName(e.target.value)}
              style={{ fontSize: 18, padding: '16px 18px' }}
            />
          </div>

          <div className="input-group">
            <label style={{ fontSize: 13, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
              Telefono
            </label>
            <input
              type="tel"
              placeholder="Ej: 999888777"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              style={{ fontSize: 18, padding: '16px 18px' }}
            />
          </div>

          <div className="input-group">
            <label style={{ fontSize: 13, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
              Direccion
            </label>
            <input
              type="text"
              placeholder="Calle, numero, distrito"
              value={address}
              onChange={(e) => setAddressState(e.target.value)}
              style={{ fontSize: 18, padding: '16px 18px' }}
            />
          </div>

          <div className="input-group">
            <label style={{ fontSize: 13, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
              Notas (opcional)
            </label>
            <textarea
              placeholder="Ej: Departamento 301, timbre azul"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              rows={3}
              style={{ resize: 'none', fontSize: 16, padding: '14px 18px' }}
            />
          </div>

          <button
            className="btn btn-primary"
            onPointerDown={handleContinue}
            disabled={!name.trim() || !address.trim()}
            style={{ borderRadius: 16, minHeight: 68, fontSize: 18, marginTop: 8 }}
          >
            Continuar al Menu
          </button>
        </div>
      </div>
    </div>
  );
}
