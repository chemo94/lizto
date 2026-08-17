import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cartStore';
import { useAuthStore } from '../store/authStore';
import { api } from '../api/client';
import { ShoppingCart, Plus, Minus, X, Search, ShoppingBag } from 'lucide-react';
import { toast } from 'react-toastify';

const CAT_ICONS = ['🍔', '🍕', '🍗', '🌮', '🥗', '🍰', '🥤', '☕', '🍽️'];

export default function Menu() {
  const [categories, setCategories] = useState([]);
  const [store, setStore] = useState(null);
  const [activeCat, setActiveCat] = useState(null);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [qty, setQty] = useState(1);
  const [selectedVariation, setSelectedVariation] = useState(null);
  const [selectedAddons, setSelectedAddons] = useState([]);
  const [itemNotes, setItemNotes] = useState('');
  const [search, setSearch] = useState('');

  const storeId = useAuthStore((s) => s.storeId);
  const token = useAuthStore((s) => s.token);
  const addItem = useCartStore((s) => s.addItem);
  const cartItems = useCartStore((s) => s.items);
  const navigate = useNavigate();

  useEffect(() => {
    api.menu(storeId, token).then((res) => {
      setCategories(res.data.categories || []);
      setStore(res.data.store);
      if (res.data.categories?.length > 0) setActiveCat(res.data.categories[0].id);
    });
  }, [storeId, token]);

  const allProducts = categories.flatMap((c) => c.products || []);
  const products = search
    ? allProducts.filter((p) => p.name.toLowerCase().includes(search.toLowerCase()))
    : categories.find((c) => c.id === activeCat)?.products || [];

  const openProduct = (p) => {
    setSelectedProduct(p);
    setQty(1);
    setSelectedVariation(p.variations?.length > 0 ? p.variations[0] : null);
    setSelectedAddons([]);
    setItemNotes('');
  };

  const toggleAddon = (addon) => {
    setSelectedAddons((prev) =>
      prev.find((a) => a.id === addon.id)
        ? prev.filter((a) => a.id !== addon.id)
        : [...prev, addon]
    );
  };

  const handleAdd = () => {
    if (!selectedProduct) return;
    addItem(selectedProduct, selectedVariation, selectedAddons, qty, itemNotes);
    toast.success(`${selectedProduct.name} agregado`);
    setSelectedProduct(null);
  };

  const totalCart = cartItems.reduce((sum, i) => sum + (i.unit_price + (i.addons_total || 0)) * i.quantity, 0);
  const countCart = cartItems.reduce((sum, i) => sum + i.quantity, 0);

  return (
    <div className="kiosk-page" style={{ overflow: 'hidden' }}>
      {/* Top bar */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '12px 20px',
        background: '#fff',
        borderBottom: '1px solid var(--border)',
        flexShrink: 0,
        gap: 16,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <div style={{
            width: 40, height: 40, borderRadius: 10,
            background: 'var(--primary)', display: 'flex',
            alignItems: 'center', justifyContent: 'center',
          }}>
            <span style={{ color: '#fff', fontWeight: 800, fontSize: 18 }}>K</span>
          </div>
          <div>
            <div style={{ fontSize: 16, fontWeight: 800, color: 'var(--text)' }}>{store?.name || 'Kiosco'}</div>
            <div style={{ fontSize: 11, color: 'var(--text-secondary)', fontWeight: 500 }}>KIOSK</div>
          </div>
        </div>

        {/* Search */}
        <div style={{ position: 'relative', flex: 1, maxWidth: 300 }}>
          <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-light)' }} />
          <input
            type="text"
            placeholder="Buscar producto..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            style={{
              padding: '10px 14px 10px 36px',
              background: '#f1f5f9',
              border: '2px solid transparent',
              borderRadius: 10,
              fontSize: 14,
              fontWeight: 500,
              width: '100%',
              outline: 'none',
              color: 'var(--text)',
              fontFamily: 'inherit',
            }}
          />
        </div>

        {/* Cart button */}
        <button
          onPointerDown={() => navigate('/cart')}
          style={{
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '10px 16px', background: countCart > 0 ? 'var(--primary)' : '#f1f5f9',
            border: 'none', borderRadius: 12, cursor: 'pointer',
            fontFamily: 'inherit', fontWeight: 700, fontSize: 14,
            color: countCart > 0 ? '#fff' : 'var(--text)',
            position: 'relative', transition: 'all 0.15s',
          }}
        >
          <ShoppingCart size={18} />
          {countCart > 0 && (
            <>
              <span>{countCart}</span>
              <span style={{ fontSize: 13, fontWeight: 800 }}>S/ {totalCart.toFixed(2)}</span>
            </>
          )}
        </button>
      </div>

      {/* Main content: sidebar + products */}
      <div style={{ display: 'flex', flex: 1, overflow: 'hidden' }}>
        {/* Sidebar categorías estilo KFC */}
        <div style={{
          width: 100,
          background: '#fff',
          borderRight: '1px solid var(--border)',
          overflowY: 'auto',
          flexShrink: 0,
          padding: '8px 0',
          WebkitOverflowScrolling: 'touch',
        }}>
          {categories.map((cat, idx) => {
            const isActive = activeCat === cat.id && !search;
            const firstProduct = cat.products?.[0];
            const thumb = firstProduct?.image;
            return (
              <button
                key={cat.id}
                onPointerDown={() => { setActiveCat(cat.id); setSearch(''); }}
                style={{
                  width: '100%',
                  padding: '10px 6px',
                  background: isActive ? 'var(--primary-light)' : 'transparent',
                  border: 'none',
                  borderLeft: isActive ? '3px solid var(--primary)' : '3px solid transparent',
                  cursor: 'pointer',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: 4,
                  transition: 'all 0.15s',
                }}
              >
                <div style={{
                  width: 56,
                  height: 56,
                  borderRadius: 14,
                  overflow: 'hidden',
                  background: '#f8fafc',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  border: isActive ? '2px solid var(--primary)' : '2px solid transparent',
                }}>
                  {thumb ? (
                    <img src={thumb} alt={cat.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    <span style={{ fontSize: 24 }}>{CAT_ICONS[idx % CAT_ICONS.length]}</span>
                  )}
                </div>
                <span style={{
                  fontSize: 11,
                  fontWeight: isActive ? 700 : 500,
                  color: isActive ? 'var(--primary)' : 'var(--text-secondary)',
                  textAlign: 'center',
                  lineHeight: 1.2,
                }}>
                  {cat.name}
                </span>
              </button>
            );
          })}
        </div>

        {/* Products grid */}
        <div style={{ flex: 1, overflowY: 'auto', padding: 16, WebkitOverflowScrolling: 'touch' }}>
          <h2 style={{ fontSize: 22, fontWeight: 800, color: 'var(--text)', marginBottom: 16 }}>
            {search ? `Resultados: "${search}"` : categories.find((c) => c.id === activeCat)?.name || 'Menu'}
          </h2>

          {products.length === 0 ? (
            <div style={{ textAlign: 'center', padding: 40, color: 'var(--text-secondary)', fontSize: 16 }}>
              No hay productos
            </div>
          ) : (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(170px, 1fr))', gap: 14 }}>
              {products.map((p) => {
                const price = p.variations?.length > 0
                  ? Math.min(...p.variations.map((v) => v.price))
                  : p.price;
                const inCart = cartItems.filter((i) => i.product_id === p.id).reduce((s, i) => s + i.quantity, 0);
                return (
                  <button
                    key={p.id}
                    onPointerDown={() => openProduct(p)}
                    style={{
                      background: '#fff',
                      border: '2px solid var(--border)',
                      borderRadius: 18,
                      overflow: 'hidden',
                      cursor: 'pointer',
                      textAlign: 'left',
                      display: 'flex',
                      flexDirection: 'column',
                      transition: 'all 0.15s',
                      boxShadow: 'var(--shadow-sm)',
                      position: 'relative',
                    }}
                  >
                    {/* Image */}
                    <div style={{
                      width: '100%',
                      aspectRatio: '1',
                      background: '#f8fafc',
                      overflow: 'hidden',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}>
                      {p.image ? (
                        <img src={p.image} alt={p.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                      ) : (
                        <span style={{ fontSize: 40 }}>🍽️</span>
                      )}
                    </div>

                    {/* Cart badge */}
                    {inCart > 0 && (
                      <div style={{
                        position: 'absolute', top: 8, right: 8,
                        background: 'var(--primary)', color: '#fff',
                        width: 26, height: 26, borderRadius: '50%',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 12, fontWeight: 800,
                        border: '2px solid #fff',
                      }}>
                        {inCart}
                      </div>
                    )}

                    {/* Info */}
                    <div style={{ padding: '10px 12px 12px' }}>
                      <div style={{ fontSize: 14, fontWeight: 700, lineHeight: 1.3, color: 'var(--text)', marginBottom: 4 }}>
                        {p.name}
                      </div>
                      <div style={{
                        display: 'inline-block',
                        padding: '4px 10px',
                        background: 'var(--primary)',
                        borderRadius: 8,
                        fontSize: 14,
                        fontWeight: 800,
                        color: '#fff',
                      }}>
                        S/ {Number(price).toFixed(2)}
                      </div>
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* Bottom bar */}
      {countCart > 0 && (
        <div style={{
          padding: '12px 20px',
          background: '#fff',
          borderTop: '1px solid var(--border)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexShrink: 0,
          gap: 12,
        }}>
          <button
            onPointerDown={() => {
              if (confirm('Cancelar pedido actual?')) {
                useCartStore.getState().clearCart();
                toast.info('Pedido cancelado');
              }
            }}
            style={{
              padding: '12px 24px',
              background: '#fef2f2',
              border: '2px solid #fecaca',
              borderRadius: 12,
              color: 'var(--danger)',
              fontFamily: 'inherit',
              fontSize: 15,
              fontWeight: 700,
              cursor: 'pointer',
            }}
          >
            Cancelar Pedido
          </button>

          <button
            onPointerDown={() => navigate('/cart')}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 12,
              padding: '14px 28px',
              background: 'var(--primary)',
              border: 'none',
              borderRadius: 14,
              color: '#fff',
              fontFamily: 'inherit',
              fontSize: 17,
              fontWeight: 800,
              cursor: 'pointer',
              boxShadow: '0 4px 16px rgba(226, 58, 58, 0.3)',
            }}
          >
            <div style={{
              width: 28, height: 28, borderRadius: 8,
              background: 'rgba(255,255,255,0.2)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}>
              <ShoppingBag size={16} />
            </div>
            <span>{countCart} items</span>
            <span style={{ fontSize: 14, opacity: 0.8 }}>|</span>
            <span>S/ {totalCart.toFixed(2)}</span>
            <span style={{
              width: 32, height: 32, borderRadius: '50%',
              background: '#fff', color: 'var(--primary)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontSize: 18, fontWeight: 800, marginLeft: 4,
            }}>
              →
            </span>
          </button>
        </div>
      )}

      {/* Product detail modal */}
      {selectedProduct && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          zIndex: 1000, padding: 16,
        }}
          onPointerDown={(e) => { if (e.target === e.currentTarget) setSelectedProduct(null); }}
        >
          <div style={{
            background: '#fff',
            borderRadius: 24,
            width: '100%',
            maxWidth: 440,
            maxHeight: '90vh',
            overflowY: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
          }}>
            {/* Product image */}
            <div style={{
              width: '100%',
              height: 200,
              background: '#f8fafc',
              borderRadius: '24px 24px 0 0',
              overflow: 'hidden',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              position: 'relative',
            }}>
              {selectedProduct.image ? (
                <img src={selectedProduct.image} alt={selectedProduct.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
              ) : (
                <span style={{ fontSize: 64 }}>🍽️</span>
              )}
              <button
                style={{
                  position: 'absolute', top: 12, right: 12,
                  width: 40, height: 40, borderRadius: '50%',
                  background: 'rgba(255,255,255,0.9)', border: 'none',
                  cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}
                onPointerDown={() => setSelectedProduct(null)}
              >
                <X size={20} color="var(--text)" />
              </button>
            </div>

            <div style={{ padding: '20px 24px 24px' }}>
              <h2 style={{ fontSize: 22, fontWeight: 800, marginBottom: 4, color: 'var(--text)' }}>
                {selectedProduct.name}
              </h2>
              <div style={{ fontSize: 20, fontWeight: 800, color: 'var(--primary)', marginBottom: 20 }}>
                S/ {Number(selectedVariation?.price ?? selectedProduct.price).toFixed(2)}
              </div>

              {/* Variations */}
              {selectedProduct.variations?.length > 0 && (
                <div style={{ marginBottom: 20 }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--text-secondary)', marginBottom: 10, textTransform: 'uppercase', letterSpacing: 0.5 }}>
                    Tamaño
                  </div>
                  <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
                    {selectedProduct.variations.map((v) => (
                      <button
                        key={v.id}
                        onPointerDown={() => setSelectedVariation(v)}
                        style={{
                          padding: '12px 20px',
                          background: selectedVariation?.id === v.id ? 'var(--primary)' : '#f1f5f9',
                          border: `2px solid ${selectedVariation?.id === v.id ? 'var(--primary)' : '#e2e8f0'}`,
                          borderRadius: 12,
                          color: selectedVariation?.id === v.id ? '#fff' : 'var(--text)',
                          fontFamily: 'inherit',
                          fontSize: 15,
                          fontWeight: 700,
                          cursor: 'pointer',
                          transition: 'all 0.15s',
                        }}
                      >
                        {v.name} - S/ {Number(v.price).toFixed(2)}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {/* Addons */}
              {selectedProduct.addons?.length > 0 && (
                <div style={{ marginBottom: 20 }}>
                  <div style={{ fontSize: 12, fontWeight: 700, color: 'var(--text-secondary)', marginBottom: 10, textTransform: 'uppercase', letterSpacing: 0.5 }}>
                    Extras
                  </div>
                  {selectedProduct.addons.map((a) => {
                    const isSelected = selectedAddons.find((x) => x.id === a.id);
                    return (
                      <button
                        key={a.id}
                        onPointerDown={() => toggleAddon(a)}
                        style={{
                          width: '100%',
                          padding: '14px 16px',
                          background: isSelected ? 'var(--primary-light)' : '#f8fafc',
                          border: `2px solid ${isSelected ? 'var(--primary)' : '#e2e8f0'}`,
                          borderRadius: 12,
                          color: 'var(--text)',
                          fontFamily: 'inherit',
                          fontSize: 15,
                          fontWeight: 600,
                          textAlign: 'left',
                          cursor: 'pointer',
                          marginBottom: 8,
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          transition: 'all 0.15s',
                        }}
                      >
                        <span>{a.name}</span>
                        <span style={{ color: 'var(--primary)', fontWeight: 700 }}>+ S/ {Number(a.price).toFixed(2)}</span>
                      </button>
                    );
                  })}
                </div>
              )}

              {/* Notes */}
              <div className="input-group" style={{ marginBottom: 20 }}>
                <label style={{ fontSize: 12, fontWeight: 700, color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: 0.5 }}>
                  Notas (opcional)
                </label>
                <input
                  type="text"
                  placeholder="Ej: Sin cebolla"
                  value={itemNotes}
                  onChange={(e) => setItemNotes(e.target.value)}
                  style={{ fontSize: 16, padding: '14px 16px' }}
                />
              </div>

              {/* Quantity + Add */}
              <div style={{ display: 'flex', gap: 14, alignItems: 'center' }}>
                <div style={{
                  display: 'flex', alignItems: 'center', gap: 4,
                  background: '#f1f5f9', borderRadius: 14, padding: '4px 8px',
                }}>
                  <button
                    style={{
                      width: 52, height: 52, minWidth: 52,
                      background: '#fff', border: '2px solid #e2e8f0', borderRadius: 12,
                      cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                      fontFamily: 'inherit', fontSize: 18, fontWeight: 700,
                    }}
                    onPointerDown={() => setQty(Math.max(1, qty - 1))}
                  >
                    <Minus size={22} />
                  </button>
                  <span style={{ fontSize: 24, fontWeight: 800, minWidth: 44, textAlign: 'center' }}>{qty}</span>
                  <button
                    style={{
                      width: 52, height: 52, minWidth: 52,
                      background: '#fff', border: '2px solid #e2e8f0', borderRadius: 12,
                      cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center',
                      fontFamily: 'inherit', fontSize: 18, fontWeight: 700,
                    }}
                    onPointerDown={() => setQty(qty + 1)}
                  >
                    <Plus size={22} />
                  </button>
                </div>
                <button
                  className="btn btn-primary"
                  style={{ flex: 1, borderRadius: 14, minHeight: 60, fontSize: 17 }}
                  onPointerDown={handleAdd}
                >
                  Agregar - S/ {(((selectedVariation?.price ?? selectedProduct.price) + selectedAddons.reduce((s, a) => s + a.price, 0)) * qty).toFixed(2)}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
