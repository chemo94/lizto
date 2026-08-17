import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useAuthStore = create(
  persist(
    (set) => ({
      token: null,
      sellerId: null,
      sellerName: null,
      storeId: null,
      storeName: null,

      login: (data) =>
        set({
          token: data.token,
          sellerId: data.seller_id,
          sellerName: data.seller_name,
          storeId: data.store_id,
          storeName: data.store_name,
        }),

      logout: () =>
        set({
          token: null,
          sellerId: null,
          sellerName: null,
          storeId: null,
          storeName: null,
        }),
    }),
    { name: 'kiosk-auth' }
  )
);
