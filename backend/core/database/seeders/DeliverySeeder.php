<?php

namespace Database\Seeders;

use App\Models\GeneralCategory;
use App\Models\SubCategory;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductAddon;
use App\Models\Favor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeliverySeeder extends Seeder
{
    public function run()
    {
        // ── Create delivery permissions ──
        $permissions = [
            'delivery.dashboard'    => 'Dashboard Delivery',
            'delivery.orders'       => 'Gestionar Pedidos',
            'delivery.favors'       => 'Gestionar Favores',
            'delivery.stores'       => 'Gestionar Tiendas',
            'delivery.categories'   => 'Gestionar Categorías',
            'delivery.refunds'      => 'Gestionar Reembolsos',
            'delivery.commission'   => 'Configurar Comisión',
            'delivery.wallets'      => 'Gestionar Billeteras',
        ];

        foreach ($permissions as $name => $group) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['group_name' => $group]);
        }

        // ── Assign to admin role ──
        $role = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($role) {
            $role->givePermissionTo(array_keys($permissions));
        }
        // ── General Categories ──
        $restaurantes = GeneralCategory::create([
            'name'       => 'Restaurantes',
            'slug'       => 'restaurantes',
            'image'      => null,
            'sort_order' => 1,
            'status'     => 1,
        ]);

        $farmacia = GeneralCategory::create([
            'name'       => 'Farmacia',
            'slug'       => 'farmacia',
            'image'      => null,
            'sort_order' => 2,
            'status'     => 1,
        ]);

        $favores = GeneralCategory::create([
            'name'       => 'Servicio de Favores',
            'slug'       => 'servicio-de-favores',
            'image'      => null,
            'sort_order' => 3,
            'status'     => 1,
        ]);

        $mascotas = GeneralCategory::create([
            'name'       => 'Mascotas',
            'slug'       => 'mascotas',
            'image'      => null,
            'sort_order' => 4,
            'status'     => 1,
        ]);

        $licorerias = GeneralCategory::create([
            'name'       => 'Licorerías',
            'slug'       => 'licorerias',
            'image'      => null,
            'sort_order' => 5,
            'status'     => 1,
        ]);

        $superMarkets = GeneralCategory::create([
            'name'       => 'Super/Mini Markets',
            'slug'       => 'super-mini-markets',
            'image'      => null,
            'sort_order' => 6,
            'status'     => 1,
        ]);

        // ── Sub Categories ──
        $comidaRapida = SubCategory::create([
            'general_category_id' => $restaurantes->id,
            'name'                => 'Comida Rápida',
            'sort_order'          => 1,
            'status'              => 1,
        ]);

        $comidaChina = SubCategory::create([
            'general_category_id' => $restaurantes->id,
            'name'                => 'Comida China',
            'sort_order'          => 2,
            'status'              => 1,
        ]);

        $medicinas = SubCategory::create([
            'general_category_id' => $farmacia->id,
            'name'                => 'Medicinas',
            'sort_order'          => 1,
            'status'              => 1,
        ]);

        // ── Seller ──
        $seller = Seller::create([
            'name'     => 'Tienda Demo',
            'email'    => 'seller@demo.com',
            'password' => Hash::make('password'),
            'phone'    => '999888777',
            'status'   => 1,
        ]);

        // ── Store ──
        $store = Store::create([
            'seller_id'        => $seller->id,
            'sub_category_id'  => $comidaRapida->id,
            'name'             => 'Burger House',
            'image'            => null,
            'cover_image'      => null,
            'description'      => 'Las mejores hamburguesas de la ciudad',
            'address'          => 'Av. Larco 456, Miraflores',
            'delivery_fee'     => 5.00,
            'min_order_amount' => 15.00,
            'is_open'          => 1,
            'opening_time'     => '08:00:00',
            'closing_time'     => '22:00:00',
            'latitude'         => -12.1234,
            'longitude'        => -77.0300,
            'preparation_time' => 20,
            'status'           => 1,
        ]);

        // ── Store Categories ──
        $masPedidos = StoreCategory::create([
            'store_id'   => $store->id,
            'name'       => 'Más Pedidos',
            'sort_order' => 1,
            'status'     => 1,
        ]);

        $bebidas = StoreCategory::create([
            'store_id'   => $store->id,
            'name'       => 'Bebidas',
            'sort_order' => 2,
            'status'     => 1,
        ]);

        // ── Products ──
        $burger = Product::create([
            'store_id'       => $store->id,
            'store_category_id' => $masPedidos->id,
            'name'           => 'Hamburguesa Clásica',
            'description'    => 'Carne 150g, lechuga, tomate, queso',
            'image'          => null,
            'price'          => 18.00,
            'discount_price' => 15.00,
            'sort_order'     => 1,
            'status'         => 1,
        ]);

        $papas = Product::create([
            'store_id'       => $store->id,
            'store_category_id' => $masPedidos->id,
            'name'           => 'Papas Fritas',
            'description'    => 'Porción grande con salsas',
            'image'          => null,
            'price'          => 10.00,
            'sort_order'     => 2,
            'status'         => 1,
        ]);

        $incaKola = Product::create([
            'store_id'       => $store->id,
            'store_category_id' => $bebidas->id,
            'name'           => 'Inca Kola',
            'description'    => 'Botella 500ml',
            'image'          => null,
            'price'          => 5.00,
            'sort_order'     => 1,
            'status'         => 1,
        ]);

        // ── Product Variations ──
        ProductVariation::create([
            'product_id' => $burger->id,
            'name'       => 'Clásica (150g)',
            'price'      => 15.00,
            'status'     => 1,
        ]);

        ProductVariation::create([
            'product_id' => $burger->id,
            'name'       => 'Doble (300g)',
            'price'      => 22.00,
            'status'     => 1,
        ]);

        // ── Product Addons ──
        ProductAddon::create([
            'product_id' => $burger->id,
            'name'       => 'Queso extra',
            'price'      => 2.00,
            'status'     => 1,
        ]);

        ProductAddon::create([
            'product_id' => $burger->id,
            'name'       => 'Tocino',
            'price'      => 3.50,
            'status'     => 1,
        ]);

        ProductAddon::create([
            'product_id' => $burger->id,
            'name'       => 'Huevo frito',
            'price'      => 2.50,
            'status'     => 1,
        ]);

        echo "✅ DeliverySeeder ejecutado: categorías, tienda, productos, variaciones y addons creados.\n";
    }
}
