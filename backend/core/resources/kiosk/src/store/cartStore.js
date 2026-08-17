import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useCartStore = create(
  persist(
    (set, get) => ({
      orderType: null,
      tableId: null,
      tableName: null,
      customerName: '',
      customerPhone: '',
      deliveryAddress: '',
      deliveryLat: null,
      deliveryLng: null,
      items: [],
      paymentMethod: 'cash',
      notes: '',

      setOrderType: (type) => set({ orderType: type }),
      setTable: (id, name) => set({ tableId: id, tableName: name }),
      setCustomer: (name, phone) => set({ customerName: name, customerPhone: phone }),
      setAddress: (address, lat, lng) => set({ deliveryAddress: address, deliveryLat: lat, deliveryLng: lng }),
      setPaymentMethod: (method) => set({ paymentMethod: method }),
      setNotes: (notes) => set({ notes }),

      addItem: (product, variation, addons, quantity, notes) => {
        const items = get().items;
        set({
          items: [
            ...items,
            {
              id: Date.now(),
              product_id: product.id,
              product_name: product.name,
              product_image: product.image,
              variation_id: variation?.id || null,
              variation_name: variation?.name || null,
              unit_price: variation?.price ?? product.price,
              addons: addons || [],
              addons_total: (addons || []).reduce((sum, a) => sum + (a.price || 0), 0),
              quantity: quantity || 1,
              notes: notes || '',
            },
          ],
        });
      },

      removeItem: (itemId) =>
        set({ items: get().items.filter((i) => i.id !== itemId) }),

      updateQuantity: (itemId, qty) =>
        set({
          items: get().items.map((i) =>
            i.id === itemId ? { ...i, quantity: Math.max(1, qty) } : i
          ),
        }),

      getSubtotal: () =>
        get().items.reduce((sum, i) => {
          const itemTotal = (i.unit_price + (i.addons_total || 0)) * i.quantity;
          return sum + itemTotal;
        }, 0),

      getItemCount: () =>
        get().items.reduce((sum, i) => sum + i.quantity, 0),

      clearCart: () =>
        set({
          items: [],
          orderType: null,
          tableId: null,
          tableName: null,
          customerName: '',
          customerPhone: '',
          deliveryAddress: '',
          deliveryLat: null,
          deliveryLng: null,
          paymentMethod: 'cash',
          notes: '',
        }),
    }),
    { name: 'kiosk-cart' }
  )
);
