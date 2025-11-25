<?php
// Definições de Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_chamados');

// Definições de Caminho
define('BASE_URL', 'http://localhost/sistema-chamados2/');

// Iniciar Sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Autoloader para Classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Funções Auxiliares
function limparInput($data) {
    return trim(htmlspecialchars($data));
}

function verificarLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit();
    }
}
?>
