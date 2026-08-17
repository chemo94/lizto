<?php
$langFile = 'resources/lang/es.json';
$translations = json_decode(file_get_contents($langFile), true);

$newTranslations = [
    "Manage Vehicle Model" => "Administrar modelo de vehículo",
    "Manage Vehicle Year" => "Administrar año de vehículo",
    "Manage Vehicle Color" => "Administrar color de vehículo",
    "Drivers Ranking" => "Ranking de conductores",
    "Manage Admin" => "Administrar administradores",
    "Role & Permissions" => "Roles y permisos",
    "main" => "Principal",
    "people" => "Personas",
    "analysis" => "Análisis",
    "finance" => "Finanzas",
    "verification" => "Verificación",
    "report" => "Reportes",
    "settings" => "Configuración",
    "front end" => "Interfaz",
    "system" => "Sistema",
    "others" => "Otros"
];

$translations = array_merge($translations, $newTranslations);

file_put_contents($langFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Successfully updated translations for menu and sections.";
