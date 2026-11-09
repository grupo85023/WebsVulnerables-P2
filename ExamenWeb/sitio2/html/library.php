<?php
// VULNERABLE: Path Traversal - No sanitization of file parameter
$file = $_GET['file'] ?? 'historia.txt';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👁️</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barad-dûr - Biblioteca</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="main-container">
        <div class="page-header">
            <div class="eye-of-sauron"></div>
            <h1>La Biblioteca</h1>
            <p class="subtitle">Pergaminos Antiguos de Mordor</p>
        </div>

        <div class="content-box">
            <h2>📜 Leyendo:
                <?php echo htmlspecialchars($file); ?>
            </h2>
            <div class="code-display">
                <?php
                // SECURE: Sanitization implemented
                // 1. basename() prevents directory traversal (../)
                // 2. Extension check ensures only .txt files are read
                
                $raw_file = $_GET['file'] ?? 'historia.txt';
                $file = basename($raw_file); // Remove path components
                
                // Enforce .txt extension
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'txt') {
                    echo "Error: Solo se permite leer pergaminos de texto (.txt).";
                } else {
                    $path = "texts/" . $file;

                    if (file_exists($path)) {
                        echo htmlspecialchars(file_get_contents($path));
                    } else {
                        echo "El pergamino '" . $file . "' no se ha encontrado en los archivos de Mordor."; // CORREGIDO: salida escapada (XSS)
                    }
                }
                ?>
            </div>
        </div>

        <div class="content-box">
            <h2>📚 Pergaminos Disponibles</h2>
            <ul class="scroll-list">
                <li><a href="library.php?file=historia.txt">La Historia del Señor Oscuro</a></li>
                <li><a href="library.php?file=armies.txt">Los Ejércitos de Mordor</a></li>
                <li><a href="library.php?file=rings.txt">Los Anillos de Poder</a></li>
                <br>
                <!-- Archivos Adicionales (Rabbit Holes) -->
                <li><a href="lore/prophecy.php" style="color: #a8a8a8;">La Profecía de los Anillos</a></li>
                <li><a href="lore/shadows.php" style="color: #a8a8a8;">Informe de Fronteras</a></li>
                <li><a href="lore/shelob.php" style="color: #a8a8a8;">Advertencia: Cirith Ungol</a></li>
                <li><a href="lore/palantir.php" style="color: #a8a8a8;">Inventario de Palantiri</a></li>
            </ul>
        </div>

        <p style="text-align: center;"><a href="./" class="btn-back">← Volver a la Torre</a></p>

        <div class="footer">
            Un Anillo para gobernarlos a todos &bull; Archivos de Barad-dûr &bull; Tercera Edad
        </div>
    </div>
</body>

</html>