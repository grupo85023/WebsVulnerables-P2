<?php
// VULNERABLE: Path Traversal via lang parameter
$lang = $_GET['lang'] ?? 'es.php';
$allowed_langs = ['es.php', 'en.php'];
if (!in_array($lang, $allowed_langs, true)) {
    $lang = 'es.php';
}

require_once __DIR__ . '/db.php';

// Guardar mensaje de tripulación (VULNERABLE: sin sanitización - XSS stored)
$msg_success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['author'], $_POST['message'])) {
    $author = $_POST['author'];   // SIN sanitizar - vulnerable a XSS stored
    $message = $_POST['message']; // SIN sanitizar - vulnerable a XSS stored

    // HARDENING: Bloquear 'alert' para forzar ataques más complejos
    if (stripos($message, 'alert') !== false || stripos($message, 'location') !== false) {
        $msg_success = "<span style='color: var(--lcars-red);'>⚠ ERROR DE PROTOCOLO: El uso de 'alert' o redirecciones está prohibido por Starfleet.</span>";
    } else {
        $stardate = '2266.' . rand(10, 99);
        $stmt = $conn->prepare("INSERT INTO crew_messages (author, message, stardate) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $author, $message, $stardate);
        $stmt->execute();
        $msg_success = "Mensaje enviado al registro de comunicaciones.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖖</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USS Enterprise - Terminal del Puente</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- LCARS Top Bar -->
    <div class="lcars-bar-top">
        <div class="lcars-elbow-top">NCC-1701</div>
        <div class="lcars-strip-top">
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
        </div>
    </div>

    <div class="lcars-layout">
        <!-- LCARS Sidebar -->
        <div class="lcars-sidebar">
            <a href="management/" class="panel orange" style="display:block; text-decoration:none;">SISTEMAS</a>
            <div class="panel blue">SENSORES</div>
            <a href="lang/" class="panel purple" style="display:block; text-decoration:none;">COMMS</a>
            <div class="panel yellow">TÁCTICO</div>
            <a href="shuttle/" class="panel teal" style="display:block; text-decoration:none;">MOTOR</a>
            <div class="panel red">ALERTAS</div>
            <div class="panel blue">MÉDICO</div>
            <a href="wanted/" class="panel orange" style="display:block; text-decoration:none;">SEGURIDAD</a>
        </div>

        <!-- Contenido Principal -->
        <div class="lcars-content">
            <div class="page-header">
                <h1>USS Enterprise</h1>
                <p class="subtitle">Terminal del Puente — Interfaz de Mando de la Flota Estelar</p>
            </div>

            <div class="nav-links">
                <!-- <a href="logs/" class="nav-link">📋 Registro de Comunicaciones</a> -->
                <a href="login.php" class="nav-link">🔐 Acceso Oficial</a>
            </div>

            <div class="content-box">
                <h2>Selección de Idioma</h2>
                <div class="lang-selector">
                    <a href="?lang=en.php"
                        class="lang-btn <?php echo $lang === 'en.php' ? 'active' : ''; ?>">English</a>
                    <a href="?lang=es.php"
                        class="lang-btn <?php echo $lang === 'es.php' ? 'active' : ''; ?>">Español</a>
                </div>
            </div>

            <div class="content-box">
                <h2>Información de la Nave</h2>
                <?php
                // VULNERABLE: Direct file inclusion - no path sanitization
                // Student can use: ?lang=../../../../flag.txt
                include("lang/" . $lang);
                ?>
            </div>

            <div class="content-box">
                <h2>Registro de Tripulación</h2>
                <?php
                $result = $conn->query("SELECT * FROM crew ORDER BY id");
                if ($result && $result->num_rows > 0) {
                    echo '<table class="data-table">';
                    echo '<tr><th>ID</th><th>Nombre</th><th>Rango</th><th>Nivel de Acceso</th></tr>';
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['rank_title']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['clearance']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                ?>
            </div>

            <!-- VULNERABLE: Formulario de mensajes - XSS Stored -->
            <div class="content-box">
                <h2>📡 Comunicaciones de Tripulación</h2>
                <p style="color: var(--lcars-text-dim); margin-bottom: 1rem; font-size: 0.9rem;">
                    Envía un mensaje al registro de comunicaciones de la nave.
                </p>
                <?php if ($msg_success): ?>
                    <div class="alert-box"
                        style="border-color: var(--lcars-teal); color: var(--lcars-teal); margin-bottom: 1rem;">
                        ✓ <?php echo $msg_success; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div style="display: flex; flex-direction: column; gap: 0.7rem;">
                        <input type="text" name="author" placeholder="Tu nombre / rango (ej: Teniente Uhura)"
                            style="padding: 0.6rem 0.8rem; background: #0a0a1a; border: 1px solid #333; color: var(--lcars-text); border-radius: 4px; font-family: inherit;"
                            required>
                        <textarea name="message" placeholder="Mensaje para el registro de comunicaciones..." rows="3"
                            style="padding: 0.6rem 0.8rem; background: #0a0a1a; border: 1px solid #333; color: var(--lcars-text); border-radius: 4px; font-family: inherit; resize: vertical;"
                            required></textarea>
                        <button type="submit"
                            style="padding: 0.6rem 1.2rem; background: var(--lcars-orange); color: #000; border: none; border-radius: 4px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; align-self: flex-start;">
                            📤 Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LCARS Bottom Bar -->
    <div class="lcars-bar-bottom">
        <div class="lcars-elbow-bottom">STARFLEET</div>
        <div class="lcars-strip-bottom">
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
        </div>
    </div>
</body>

</html>
