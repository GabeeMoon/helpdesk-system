<?php
// Definições de Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_chamados');

// Definições de Caminho
define('BASE_URL', 'http://localhost/sistema-chamados2/');

// Conexão com Banco de Dados
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

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
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

function verificarLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit();
    }
}
?>
