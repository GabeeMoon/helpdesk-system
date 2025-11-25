<?php
require_once 'configs/config.php';

$sql_file = 'database.sql';

if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Remove comentários para evitar erros no multi_query
    $lines = explode("\n", $sql_content);
    $sql_clean = "";
    foreach ($lines as $line) {
        if (substr(trim($line), 0, 2) != '--' && trim($line) != '') {
            $sql_clean .= $line . "\n";
        }
    }

    if ($conn->multi_query($sql_clean)) {
        echo "Banco de dados configurado com sucesso!";
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "Erro ao configurar banco de dados: " . $conn->error;
    }
} else {
    echo "Arquivo database.sql não encontrado.";
}
$conn->close();
?>
