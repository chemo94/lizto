<?php

namespace Database\Seeders;

use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    public function run()
    {
        $subcats = [
            1 => ['Pollerías', 'Pizzas', 'Hamburguesas', 'Salchipapas', 'Broaster', 'Parrillas & Carnes', 'Alitas', 'Anticuchos', 'Chifa', 'Menús del Día', 'Comida Criolla', 'Postres & Helados', 'Cafetería & Jugos', 'Makis & Sushi', 'Cevicherías & Mariscos'],
            2 => ['Medicinas', 'Cuidado Personal', 'Bebés & Maternidad', 'Vitaminas & Suplementos', 'Primeros Auxilios', 'Cuidado de la Piel', 'Higiene & Salud', 'Cuidado Bucal'],
            3 => ['Mandados Urgentes', 'Compras Rápidas', 'Recojo & Envíos', 'Trámites & Pagos', 'Transporte de Paquetes'],
            4 => ['Alimento para Perros', 'Alimento para Gatos', 'Snacks & Premios', 'Antipulgas & Farmacia', 'Higiene & Arena', 'Juguetes & Accesorios'],
            5 => ['Cervezas', 'Vinos & Espumantes', 'Piscos & Destilados', 'Whisky & Ron', 'Bebidas & Gaseosas', 'Hielo & Snacks', 'Cócteles Listos'],
            6 => ['Abarrotes & Despensa', 'Frutas & Verduras', 'Lácteos & Huevos', 'Panadería & Desayuno', 'Carnes & Embutidos', 'Bebidas & Snacks', 'Limpieza & Hogar']
        ];

        foreach ($subcats as $catId => $names) {
            $i = 1;
            foreach ($names as $name) {
                SubCategory::updateOrCreate(
                    ['general_category_id' => $catId, 'name' => $name],
                    ['status' => 1, 'sort_order' => $i++]
                );
            }
        }
    }
}
