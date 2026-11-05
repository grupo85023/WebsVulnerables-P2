<?php
session_name("StarWarsCookie");
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}
// Admin Check
$username = $_SESSION['username'] ?? '';
if ($username !== 'admin') {
    header("Location: fail.php");
    exit();
}
$output = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = $_POST['ip'];

    // Whitelist de comandes permeses per al CTF
    $allowed_commands = [
        'id', 'whoami', 'ls', 'cat', 'pwd', 'uname', 
        'hostname', 'find', 'dir', 'type', 'ipconfig', 
        'systeminfo', 'net', 'mysql'
    ];

    // Comprovem si hi ha caràcters d'injecció de comandes
    $has_injection = preg_match('/[;|&`$()]/', $ip);
    $blocked = false;

    if ($has_injection) {
        // Separem les comandes injectades (per exemple després d'un ;)
        $parts = preg_split('/[;|&]+/', $ip);
        
        // Saltem la primera part (el destí del ping) i comprovem la resta
        for ($i = 1; $i < count($parts); $i++) {
            $current_cmd = trim($parts[$i]);
            if (empty($current_cmd)) continue;

            // Netegem possibles subshells per analitzar només la comanda
            $clean_cmd = preg_replace('/^\$\(|\)$|^`|`$/', '', $current_cmd);
            $clean_cmd = trim($clean_cmd);
            if (empty($clean_cmd)) continue;

            // Obtenim la comanda base (la primera paraula)
            $base_cmd = strtolower(preg_split('/\s+/', $clean_cmd)[0]);
            $base_cmd = basename($base_cmd);

            // 1. Verificació de Whitelist
            if (!in_array($base_cmd, $allowed_commands)) {
                $blocked = true;
                break;
            }

            // 2. FILTRE ESPECÍFIC: Evitar lectura de fitxers PHP
            if ($base_cmd === 'cat') {
                // Comprovem si en tota la cadena de la comanda apareix ".php"
                if (stripos($current_cmd, '.php') !== false) {
                    $blocked = true;
                    break;
                }
            }
        }
    }

    if ($blocked) {
        $output = "╔══════════════════════════════════════════════════════╗\n";
        $output .= "║  ⚠  ALERTA DE SEGURIDAD IMPERIAL - NIVEL MÁXIMO  ⚠  ║\n";
        $output .= "╠══════════════════════════════════════════════════════╣\n";
        $output .= "║                                                      ║\n";
        $output .= "║   Se ha detectado un intento de ejecución de un      ║\n";
        $output .= "║   comando NO AUTORIZADO en la terminal imperial.     ║\n";
        $output .= "║                                                      ║\n";
        $output .= "║   >>> Notificando a Lord Vader... <<<                ║\n";
        $output .= "║                                                      ║\n";
        $output .= "║   \"Encuentro tu falta de obediencia...               ║\n";
        $output .= "║                           perturbadora.\"             ║\n";
        $output .= "║                           — Darth Vader              ║\n";
        $output .= "║                                                      ║\n";
        $output .= "║   Tu ID de terminal ha sido registrado.              ║\n";
        $output .= "║   Los Stormtroopers han sido enviados a tu           ║\n";
        $output .= "║   ubicación. No intentes escapar.                    ║\n";
        $output .= "║                                                      ║\n";
        $output .= "╚══════════════════════════════════════════════════════╝";
    } else {
        // Execució de la comanda original (Vulnerable si passa el filtre)
        $param = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? '-n' : '-c';
        // CORREGIDO: solo se acepta una IP válida y se escapa como argumento (sin inyección de comandos)
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $output = "Dirección IP no válida.";
        } else {
            $output = shell_exec("ping $param 1 " . escapeshellarg($ip));
        }
    }

    if (empty($output)) {
        $output = "Sin respuesta del objetivo. La nave puede haber saltado al hiperespacio.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⭐</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estrella de la Muerte - Escáner de Naves Rebeldes</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="terminal-container">
        <div class="terminal-header">
            <h1>📡 Escáner de Naves Rebeldes</h1>
            <a href="./" class="btn-logout">← Volver a Admin</a>
        </div>

        <div class="terminal-box">
            <div class="terminal-titlebar">
                <div class="terminal-dot red"></div>
                <div class="terminal-dot amber"></div>
                <div class="terminal-dot green"></div>
                <span>deathstar-scanner v1.3 — /admin/terminal.php</span>
            </div>

            <div class="terminal-body">
                <div class="terminal-info">
                    Introduce la dirección IP de una nave rebelde sospechosa para escanear sus señales de comunicación.
                    Esta herramienta envía un ping para verificar si el objetivo es alcanzable.
                </div>

                <form method="POST" action="">
                    <div class="terminal-input-row">
                        <input type="text" name="ip" placeholder="Introduce la IP del objetivo (ej: 192.168.1.1)" 
                               value="<?php echo isset($_POST['ip']) ? htmlspecialchars($_POST['ip']) : ''; ?>">
                        <button type="submit" class="btn-scan">⚡ Escanear</button>
                    </div>
                </form>

                <?php if ($output): ?>
                    <div class="terminal-output">
                        <pre><span class="prompt">deathstar@scanner:~$</span> ping -c 1 <?php echo htmlspecialchars($_POST['ip'] ?? ''); ?>

<?php echo htmlspecialchars($output); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            Imperio Galáctico &bull; Sistema de Detección Rebelde &bull; Clasificado
        </div>
    </div>
</body>
</html>
