<?php
$langFile = 'resources/lang/es.json';
$translations = json_decode(file_get_contents($langFile), true);

$newTranslations = [
    "Total Commission Paid" => "Comisión total pagada",
    "Total Tips Earning" => "Ganancia total por propinas",
    "Average Earning Per Ride" => "Ganancia promedio por viaje",
    "Today Earning" => "Ganancia de hoy",
    "This Week Earning" => "Ganancia de esta semana",
    "This Month Earning" => "Ganancia de este mes",
    "This Year Earning" => "Ganancia de este año",
    "Spent" => "Gastado",
    "Total Spending Amount" => "Monto total de gasto",
    "Total Earning" => "Ganancia total",
    "Earning" => "Ganancia",
    "Spent Amount" => "Monto gastado",
    "Earning Amount" => "Monto ganado",
    "Ride Summary" => "Resumen del viaje",
    "Ride Details" => "Detalles del viaje"
];

$translations = array_merge($translations, $newTranslations);

file_put_contents($langFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Successfully updated translations for earnings and spending.";
