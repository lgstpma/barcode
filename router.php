<?php
/**
 * Router para el servidor embebido de PHP (php -S).
 * Sirve archivos estáticos y PHP desde la raíz del proyecto.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri === '' || $uri === false) {
	$uri = '/';
}
$file = __DIR__ . $uri;

// Archivo estático o PHP existente
if ($uri !== '/' && is_file($file)) {
	return false;
}

// /ruta → /ruta.php
if ($uri !== '/' && is_file($file . '.php')) {
	require $file . '.php';
	return true;
}

// Directorio con index
if (is_dir($file)) {
	$idx = rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
	if (is_file($idx)) {
		require $idx;
		return true;
	}
}

if ($uri === '/') {
	require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
	return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "404 Not Found: " . $uri;
return true;
