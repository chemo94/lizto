import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';
import { useAuthStore } from '../store/authStore';
import { api } from '../api/client';
import { ArrowLeft, Trash2, Plus, Minus, ShoppingBag, Banknote, CreditCard } from 'lucide-react';
import { toast } from 'react-toastify';

export default function Cart() {
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [loading, setLoading] = useState(false);

  const items = useCartStore((s) => s.items);
  const orderType = useCartStore((s) => s.orderType);
  const tableName = useCartStore((s) => s.tableName);
  const customerName = useCartStore((s) => s.customerName);
  const deliveryAddress = useCartStore((s) => s.deliveryAddress);
  const paymentMethod = useCartStore((s) => s.paymentMethod);
  const notes = useCartStore((s) => s.notes);
  const removeItem = useCartStore((s) => s.removeItem);
  const updateQuantity = useCartStore((s) => s.updateQuantity);
  const setPaymentMethod = useCartStore((s) => s.setPaymentMethod);
  const getSubtotal = useCartStore((s) => s.getSubtotal);
  const clearCart = useCartStore((s) => s.clearCart);

  const storeId = useAuthStore((s) => s.storeId);
  const token = useAuthStore((s) => s.token);
  const navigate = useNavigate();

  useEffect(() => {
    api.paymentMethods(storeId, token).then((res) => {
      setPaymentMethods(res.data.payment_methods || []);
    });
  }, [storeId, token]);

  const subtotal = getSubtotal();
  const deliveryFee = orderType === 'delivery' ? 5.00 : 0;
  const total = subtotal + deliveryFee;

  const handleConfirm = async () => {
    if (items.length === 0) return;
    setLoading(true);
    try {
      const orderData = {
        store_id: storeId,
        order_type: orderType,
        items: items.map((i) => ({
          product_id: i.product_id,
          variation_id: i.variation_id,
          quantity: i.quantity,
          unit_price: i.unit_price,
          addons: i.addons?.map((a) => a.id) || [],
          notes: i.notes,
        })),
        payment_method: paymentMethod,
        subtotal,
        total,
        contact_name: customerName,
        delivery_address: deliveryAddress,
        notes,
      };
      const res = await api.createOrder(orderData, token);
      clearCart();
      navigate('/confirmed/' + res.data.order_id);
    } catch (e) {
      toast.error(e.message);
    } finally {
      setLoading(false);
    }
  };

  const orderLabel = orderType === 'delivery' ? 'Delivery' : orderType === 'dine_in' ? `Mesa: ${tableName}` : 'Para Llevar';

  return (
    <div className="kiosk-page">
      <div className="kiosk-header">
        <button className="btn btn-secondary btn-sm" onPointerDown={() => navigate('/menu')}>
          <ArrowLeft size={18} /> Menu
        </button>
        <h1>Mi Pedido ({orderLabel})</h1>
        <div />
      </div>

      <div className="kiosk-body" style={{ display: 'flex', gap: 20, flexWrap: 'wrap' }}>
        {/* Items list */}
        <div style={{ flex: 1, minWidth: 320 }}>
          {items.length === 0 ? (
            <div style={{ textAlign: 'center', padding: 60, color: 'var(--text-secondary)' }}>
              <ShoppingBag size={56} style={{ marginBottom: 16, opacity: 0.2 }} />
              <p style={{ fontSize: 18 }}>El carrito esta vacio</p>
            </div>
          ) : (
            items.map((item) => (
              <div key={item.id} style={{
                background: '#fff',
                borderRadius: 16,
                padding: '16px 18px',
                marginBottom: 12,
                border: '1px solid var(--border)',
                boxShadow: 'var(--shadow-sm)',
              }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start' }}>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 17, fontWeight: 700, color: 'var(--text)' }}>{item.product_name}</div>
                    {item.variation_name && (
                      <div style={{ fontSize: 13, color: 'var(--text-secondary)', marginTop: 2 }}>{item.variation_name}</div>
                    )}
                    {item.addons?.length > 0 && (
                      <div style={{ fontSize: 12, color: 'var(--text-secondary)', marginTop: 2 }}>
                        + {item.addons.map((a) => a.addon_name).join(', ')}
                      </div>
                    )}
                    <div style={{ fontSize: 16, fontWeight: 800, color: 'var(--primary)', marginTop: 6 }}>
                      S/ {((item.unit_price + (item.addons_total || 0)) * item.quantity).toFixed(2)}
                    </div>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <button
                      style={{
                        width: 40, height: 40, minWidth: 40,
                        background: '#f1f5f9', border: '2px solid #e2e8f0', borderRadius: 10,
                        cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontFamily: 'inherit',
                      }}
                      onPointerDown={() => updateQuantity(item.id, item.quantity - 1)}
                    >
                      <Minus size={16} />
                    </button>
                    <span style={{ fontSize: 18, fontWeight: 800, minWidth: 28, textAlign: 'center' }}>
                      {item.quantity}
                    </span>
                    <button
                      style={{
                        width: 40, height: 40, minWidth: 40,
                        background: '#f1f5f9', border: '2px solid #e2e8f0', borderRadius: 10,
                        cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontFamily: 'inherit',
                      }}
                      onPointerDown={() => updateQuantity(item.id, item.quantity + 1)}
                    >
                      <Plus size={16} />
                    </button>
                    <button
                      style={{
                        width: 40, height: 40, minWidth: 40,
                        background: 'var(--danger-light)', border: '2px solid #fecaca', borderRadius: 10,
                        cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontFamily: 'inherit',
                      }}
                      onPointerDown={() => removeItem(item.id)}
                    >
                      <Trash2 size={16} color="var(--danger)" />
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Summary + Payment */}
        <div style={{ width: 340, flexShrink: 0 }}>
          {/* Summary card */}
          <div style={{ background: '#fff', borderRadius: 20, padding: 24, marginBottom: 16, border: '1px solid var(--border)', boxShadow: 'var(--shadow-sm)' }}>
            <h3 style={{ fontSize: 16, fontWeight: 800, marginBottom: 16, color: 'var(--text)', textTransform: 'uppercase', letterSpacing: 0.5 }}>Resumen</h3>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 10, fontSize: 15 }}>
              <span style={{ color: 'var(--text-secondary)' }}>Subtotal</span>
              <span style={{ fontWeight: 600 }}>S/ {subtotal.toFixed(2)}</span>
            </div>
            {orderType === 'delivery' && (
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 10, fontSize: 15 }}>
                <span style={{ color: 'var(--text-secondary)' }}>Envio</span>
                <span style={{ fontWeight: 600 }}>S/ {deliveryFee.toFixed(2)}</span>
              </div>
            )}
            <div style={{
              display: 'flex', justifyContent: 'space-between',
              fontSize: 22, fontWeight: 800,
              borderTop: '2px solid var(--border)', paddingTop: 12, marginTop: 12,
            }}>
              <span>Total</span>
              <span style={{ color: 'var(--primary)' }}>S/ {total.toFixed(2)}</span>
            </div>
          </div>

          {/* Payment methods */}
          <div style={{ background: '#fff', borderRadius: 20, padding: 24, marginBottom: 16, border: '1px solid var(--border)', boxShadow: 'var(--shadow-sm)' }}>
            <h3 style={{ fontSize: 16, fontWeight: 800, marginBottom: 14, color: 'var(--text)', textTransform: 'uppercase', letterSpacing: 0.5 }}>Metodo de Pago</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              {paymentMethods.map((pm) => {
                const isActive = paymentMethod === String(pm.code) || (pm.code === 0 && paymentMethod === 'cash');
                return (
                  <button
                    key={pm.code}
                    onPointerDown={() => setPaymentMethod(pm.code === 0 ? 'cash' : String(pm.code))}
                    style={{
                      padding: '14px 18px',
                      background: isActive ? 'var(--primary)' : '#f8fafc',
                      border: `2px solid ${isActive ? 'var(--primary)' : '#e2e8f0'}`,
                      borderRadius: 14,
                      color: isActive ? '#fff' : 'var(--text)',
                      fontFamily: 'inherit',
                      fontSize: 16,
                      fontWeight: 700,
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      gap: 12,
                      transition: 'all 0.15s',
                    }}
                  >
                    {pm.code === 0 ? <Banknote size={20} /> : <CreditCard size={20} />}
                    {pm.name}
                  </button>
                );
              })}
            </div>
          </div>

          {/* Confirm button */}
          <button
            className="btn btn-primary"
            onPointerDown={handleConfirm}
            disabled={loading || items.length === 0}
            style={{ fontSize: 20, fontWeight: 800, borderRadius: 16, minHeight: 72, boxShadow: '0 6px 20px rgba(226, 58, 58, 0.35)' }}
          >
            {loading ? 'Procesando...' : 'Confirmar Pedido'}
          </button>
        </div>
      </div>
    </div>
  );
}
