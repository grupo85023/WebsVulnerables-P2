<?php
// Visor de Mensajes de Tripulación
// VULNERABLE: Muestra los mensajes sin sanitizar - XSS Stored
require_once __DIR__ . '/../db.php';

// HARDENING: Protección con contraseña
$auth_cookie = 'st_logs_auth';
$auth_pass = 'mmora';
$auth_hash = hash('sha256', $auth_pass . 'salt_starfleet');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_pass'])) {
    if ($_POST['unlock_pass'] === $auth_pass) {
        setcookie($auth_cookie, $auth_hash, time() + 3600, '/', '', false, true);
        header("Location: index.php");
        exit();
    } else {
        $error = "Código de acceso incorrecto.";
    }
}

if (!isset($_COOKIE[$auth_cookie]) || $_COOKIE[$auth_cookie] !== $auth_hash) {
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Acceso Restringido - Registros</title>
        <link rel="stylesheet" href="../style.css">
    </head>

    <body style="display:flex; justify-content:center; align-items:center; height:100vh; background-color:#000;">
        <div
            style="text-align:center; padding:2rem; border:2px solid var(--lcars-red); border-radius:10px; background:#111; color:var(--lcars-red);">
            <h2 style="font-family:sans-serif; text-transform:uppercase; letter-spacing:2px;">⚠ Acceso Clasificado</h2>
            <p>Introduce el código de autorización de Starfleet.</p>
            <?php if (isset($error))
                echo "<p style='color:white'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="unlock_pass" style="padding:0.5rem; margin-top:1rem;" autofocus>
                <br>
                <button type="submit"
                    style="margin-top:1rem; padding:0.5rem 1rem; background:var(--lcars-red); color:black; border:none; font-weight:bold; cursor:pointer;">ACCEDER</button>
            </form>
            <p style="margin-top:2rem;"><a href="../index.php" style="color:var(--lcars-orange); text-decoration:none;">←
                    Volver al Puente</a></p>
        </div>
    </body>

    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🖖</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USS Enterprise - Registro de Comunicaciones</title>
    <link rel="stylesheet" href="../style.css">
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
            <div class="panel orange">REGISTRO<br>COMMS</div>
            <div class="panel blue">ANALIZAR</div>
            <div class="panel purple">FILTRAR</div>
            <div class="panel yellow">EXPORTAR</div>
            <div class="panel teal">ESTADÍSTICAS</div>
            <div class="panel red">ALERTAS</div>
        </div>

        <!-- Contenido Principal -->
        <div class="lcars-content">
            <div class="page-header">
                <h1>Registro de Comunicaciones</h1>
                <p class="subtitle">Mensajes de Tripulación — Canal Abierto de la Flota Estelar</p>
            </div>

            <div class="nav-links">
                <a href="../index.php" class="nav-link">← Puente</a>
                <a href="?refresh=1" class="nav-link">🔄 Actualizar</a>
                <form method="POST"
                    onsubmit="return confirm('⚠ ALERTA: ¿Está seguro de que desea purgar todo el registro de comunicaciones? esta acción es irreversible.');"
                    style="display:inline;">
                    <input type="hidden" name="clear_logs" value="1">
                    <button type="submit" class="nav-link"
                        style="border-color:var(--lcars-red); color:var(--lcars-red); cursor:pointer; background:transparent; font-family:inherit; font-size:0.9rem;">🗑
                        Purgar Todo</button>
                </form>
            </div>

            <?php
            // Lógica de borrado
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
                $conn->query("TRUNCATE TABLE crew_messages"); // Borrar todo
                echo '<div class="alert-box info">✓ Registro de comunicaciones purgado correctamente.</div>';
            }
            ?>

            <script>
                // Auto-refresh cada 30 segundos
                setTimeout(function () {
                    window.location.href = window.location.href.split('?')[0];
                }, 30000);
            </script>

            <div class="alert-box warning">
                ⚠ Este canal de comunicaciones es monitorizado por el Alto Mando de Starfleet.
            </div>

            <div class="content-box">
                <h2>📡 Mensajes de Tripulación</h2>
                <div class="log-viewer">
                    <?php
                    $result = $conn->query("SELECT * FROM crew_messages ORDER BY id DESC");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // VULNERABLE: Sin sanitización - XSS Stored
                            // El autor y el mensaje se muestran directamente sin htmlspecialchars
                            echo "<div class='log-line'>";
                               echo "<span style='color: var(--lcars-orange);'>[Stardate " . htmlspecialchars($row['stardate']) . "]</span> ";
   echo "<strong>" . htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') . ":</strong> ";
   echo htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
                            echo "</div>";
                        }
                    } else {
                        echo "<div class='log-line' style='color: var(--lcars-text-dim);'>No hay mensajes en el registro de comunicaciones.</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="content-box">
                <h2>📊 Estadísticas del Canal</h2>
                <?php
                $total = $conn->query("SELECT COUNT(*) as total FROM crew_messages")->fetch_assoc()['total'];
                echo "<p>Total de mensajes: <strong style='color: var(--lcars-orange);'>$total</strong></p>";
                echo "<p style='color: var(--lcars-text-dim); font-size: 0.85rem;'>Canal: <strong>Comunicaciones Abiertas NCC-1701</strong></p>";
                ?>
            </div>
        </div>
    </div>

    <!-- LCARS Bottom Bar -->
    <div class="lcars-bar-bottom">
        <div class="lcars-elbow-bottom">COMMS</div>
        <div class="lcars-strip-bottom">
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
            <div class="block"></div>
        </div>
    </div>
</body>

</html>
