<?php
// index.php - Main Router & Entry Point FoodieGo REST API
// Dibuat untuk Tugas Pemrograman Mobile - Pak Bonda

// 1. CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/FoodController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/RestaurantController.php';
require_once __DIR__ . '/controllers/CartController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/AddressController.php';
require_once __DIR__ . '/controllers/PromoController.php';

// 2. Normalisasi URI & Method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Buang script directory jika berada di subfolder (misal /food_order_api/...)
if ($scriptName !== '/' && $scriptName !== '\\' && strpos($uri, $scriptName) === 0) {
    $uri = substr($uri, strlen($scriptName));
}

// Fallback untuk query parameter ?route=...
if (isset($_GET['route'])) {
    $uri = '/' . ltrim($_GET['route'], '/');
}

$uri = '/' . trim($uri, '/');
if (empty($uri) || $uri === '/' || $uri === '/test_dashboard.html') {
    // Tampilkan interactive test dashboard jika diakses root atau /test_dashboard.html
    if (file_exists(__DIR__ . '/test_dashboard.html')) {
        header('Content-Type: text/html; charset=utf-8');
        include __DIR__ . '/test_dashboard.html';
        exit;
    } else {
        Response::json([
            'api_name' => 'FoodieGo REST API',
            'version' => '1.0.0',
            'status' => 'online',
            'author' => 'Tugas Pak Bonda - Kelas F',
            'documentation' => '/test_dashboard.html'
        ], 200, 'FoodieGo API Server is Running');
    }
}

$method = $_SERVER['REQUEST_METHOD'];
// Dukungan method spoofing untuk form test
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// 3. Routing Table
// Format regex pattern => [Controller, Method]
$routes = [
    // Auth & Profile
    'POST#^/api/auth/login$#'              => ['AuthController', 'login'],
    'POST#^/api/auth/register$#'           => ['AuthController', 'register'],
    'GET#^/api/profile$#'                  => ['AuthController', 'getProfile'],
    'PUT#^/api/profile$#'                  => ['AuthController', 'updateProfile'],

    // Categories
    'GET#^/api/categories$#'               => ['CategoryController', 'getAll'],
    'GET#^/api/categories/(\d+)$#'         => ['CategoryController', 'getById'],
    'POST#^/api/categories$#'              => ['CategoryController', 'create'],
    'PUT#^/api/categories/(\d+)$#'         => ['CategoryController', 'update'],
    'DELETE#^/api/categories/(\d+)$#'      => ['CategoryController', 'delete'],

    // Restaurants
    'GET#^/api/restaurants$#'              => ['RestaurantController', 'getAll'],
    'GET#^/api/restaurants/(\d+)$#'        => ['RestaurantController', 'getById'],
    'POST#^/api/restaurants$#'             => ['RestaurantController', 'create'],
    'PUT#^/api/restaurants/(\d+)$#'        => ['RestaurantController', 'update'],
    'DELETE#^/api/restaurants/(\d+)$#'     => ['RestaurantController', 'delete'],

    // Foods (Menu Makanan)
    'GET#^/api/foods$#'                    => ['FoodController', 'getAll'],
    'GET#^/api/foods/(\d+)$#'              => ['FoodController', 'getById'],
    'POST#^/api/foods$#'                   => ['FoodController', 'create'],
    'PUT#^/api/foods/(\d+)$#'              => ['FoodController', 'update'],
    'DELETE#^/api/foods/(\d+)$#'           => ['FoodController', 'delete'],

    // Cart (Keranjang)
    'GET#^/api/cart$#'                     => ['CartController', 'getCart'],
    'POST#^/api/cart/add$#'                => ['CartController', 'addToCart'],
    'PUT#^/api/cart/update/(\d+)$#'        => ['CartController', 'updateCartItem'],
    'DELETE#^/api/cart/remove/(\d+)$#'     => ['CartController', 'removeItem'],
    'DELETE#^/api/cart/clear$#'            => ['CartController', 'clearCart'],

    // Orders & Tracking
    'GET#^/api/orders$#'                   => ['OrderController', 'getAll'],
    'GET#^/api/orders/(\d+)$#'             => ['OrderController', 'getById'],
    'POST#^/api/orders/checkout$#'         => ['OrderController', 'checkout'],
    'PUT#^/api/orders/(\d+)/status$#'      => ['OrderController', 'updateStatus'],

    // Addresses
    'GET#^/api/addresses$#'                => ['AddressController', 'getAll'],
    'POST#^/api/addresses$#'               => ['AddressController', 'create'],
    'PUT#^/api/addresses/(\d+)$#'          => ['AddressController', 'update'],
    'PUT#^/api/addresses/(\d+)/default$#'  => ['AddressController', 'setDefault'],
    'DELETE#^/api/addresses/(\d+)$#'       => ['AddressController', 'delete'],

    // Promos (Banners & Vouchers)
    'GET#^/api/banners$#'                  => ['PromoController', 'getBanners'],
    'POST#^/api/banners$#'                 => ['PromoController', 'createBanner'],
    'GET#^/api/vouchers$#'                 => ['PromoController', 'getVouchers'],
];

// 4. Dispatch Request
$matched = false;

foreach ($routes as $routePattern => $handler) {
    [$routeMethod, $pattern] = explode('#', $routePattern, 2);
    $pattern = '#' . $pattern;

    if ($method === $routeMethod && preg_match($pattern, $uri, $matches)) {
        array_shift($matches); // Hilangkan elemen index 0 (full match)
        [$controllerName, $action] = $handler;

        $controller = new $controllerName();
        call_user_func_array([$controller, $action], $matches);
        $matched = true;
        break;
    }
}

if (!$matched) {
    Response::error("Endpoint '$uri' dengan method '$method' tidak ditemukan.", 404, [
        'requested_uri' => $uri,
        'requested_method' => $method,
        'help' => 'Kunjungi /test_dashboard.html untuk melihat daftar seluruh endpoint yang tersedia.'
    ]);
}
