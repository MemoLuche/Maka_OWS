<?php

/**

 * API Proxy para Google Maps

 * Este archivo actúa como intermediario entre el cliente y Google Maps API

 * manteniendo la API key segura en el servidor

 */



session_start();

require_once 'config/conexion.php';



// Verificar que el usuario esté autenticado

if (!isset($_SESSION['usuario_id'])) {

    http_response_code(401);

    echo json_encode(['error' => 'No autorizado']);

    exit;

}



header('Content-Type: application/json');



// API Key de Google Maps (mantener segura, nunca exponer al cliente)

define('GOOGLE_MAPS_API_KEY', 'XXXXX');



$action = $_GET['action'] ?? '';



switch ($action) {

    case 'geocode':

        // Geocodificar una dirección

        $address = $_GET['address'] ?? '';

        

        if (empty($address)) {

            http_response_code(400);

            echo json_encode(['error' => 'Dirección requerida']);

            exit;

        }

        

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' 

               . urlencode($address) 

               . '&key=' . GOOGLE_MAPS_API_KEY;

        

        $response = file_get_contents($url);

        echo $response;

        break;

        

    case 'staticmap':

        // Generar URL de mapa estático

        $lat = $_GET['lat'] ?? '';

        $lng = $_GET['lng'] ?? '';

        $zoom = $_GET['zoom'] ?? '15';

        $size = $_GET['size'] ?? '600x400';

        

        if (empty($lat) || empty($lng)) {

            http_response_code(400);

            echo json_encode(['error' => 'Coordenadas requeridas']);

            exit;

        }

        

        $mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?'

                . 'center=' . urlencode($lat . ',' . $lng)

                . '&zoom=' . urlencode($zoom)

                . '&size=' . urlencode($size)

                . '&markers=color:red%7C' . urlencode($lat . ',' . $lng)

                . '&key=' . GOOGLE_MAPS_API_KEY;

        

        echo json_encode(['url' => $mapUrl]);

        break;

        

    case 'directions':

        // Obtener direcciones entre dos puntos

        $origin = $_GET['origin'] ?? '';

        $destination = $_GET['destination'] ?? '';

        

        if (empty($origin) || empty($destination)) {

            http_response_code(400);

            echo json_encode(['error' => 'Origen y destino requeridos']);

            exit;

        }

        

        $url = 'https://maps.googleapis.com/maps/api/directions/json?'

               . 'origin=' . urlencode($origin)

               . '&destination=' . urlencode($destination)

               . '&key=' . GOOGLE_MAPS_API_KEY;

        

        $response = file_get_contents($url);

        echo $response;

        break;

        

    default:

        http_response_code(400);

        echo json_encode(['error' => 'Acción no válida']);

        break;

}


