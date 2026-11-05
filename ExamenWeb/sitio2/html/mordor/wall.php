<?php
// Muro de Mordor - Tablón de mensajes Orco
// VULNERABLE: Stored XSS with weak script filter

session_name("lotrCookie");
session_start();
require_once __DIR__ . '/../db.php';

$is_admin = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Admin Password Hash (SHA-256 of 'mmora')
$admin_hash = 'b11a4369e06c7fdc024523773175727144e058428416ca327f27a6f296315264';

// Check strict admin auth via HttpOnly cookie (safe from XSS)
$admin_auth = (isset($_COOKIE['admin_gate_pass']) && $_COOKIE['admin_gate_pass'] === $admin_hash);

// Handle admin authentication (mmora)
$auth_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_admin'])) {
    if ($_POST['unlock_pass'] === 'mmora') {
        $admin_auth = true;
        // Set HttpOnly cookie with HASH for 30 days
        setcookie('admin_gate_pass', $admin_hash, time() + (86400 * 30), "/", "", false, true);
        // Reload to apply
        header("Location: wall.php");
        exit();
    } else {
        $auth_error = "Contraseña incorrecta. Este incidente será reportado al Ojo.";
    }
}

// Handle admin delete actions (only if admin AND authenticated)
$success = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin && $admin_auth) {
    if (isset($_POST['delete_id'])) {
        $del_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM orc_messages WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $success = "Mensaje eliminado.";
    } elseif (isset($_POST['delete_all'])) {
        $conn->query("DELETE FROM orc_messages");
        $success = "Muro limpiado por completo.";
    }
}

// Handle normal message submission (available to everyone)
// BUT malicious students can try to post XSS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !isset($_POST['unlock_admin'])) {
    $author = $_POST['author'] ?? 'Orco Anónimo';
    $message = $_POST['message'] ?? '';

    // VULNERABLE: Weak filter - only blocks <script> tag
    if (preg_match("/<script/i", $message)) {
        $error = "¡El Ojo de Sauron bloquea tu magia oscura! ¡No se permiten scripts!";
    } else {
        // Store without sanitization
        $stmt = $conn->prepare("INSERT INTO orc_messages (author, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $author, $message);
        $stmt->execute();
    }
}

// Fetch messages
$messages = $conn->query("SELECT * FROM orc_messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👁️</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barad-dûr - Muro del Orco</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .message-board {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #5d4037;
            border-radius: 4px;
            padding: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .message {
            background: rgba(44, 26, 14, 0.9);
            border-left: 3px solid var(--lotr-gold);
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            position: relative;
        }

        .message .author {
            color: var(--lotr-gold);
            font-weight: bold;
            font-family: 'Cinzel', serif;
            font-size: 0.9rem;
        }

        .message .time {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            font-size: 0.7rem;
            color: #8b7355;
        }

        .message .text {
            color: var(--lotr-parchment);
            margin-top: 0.4rem;
            font-family: 'Fira Code', monospace;
            word-break: break-all;
        }

        .btn-delete {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: var(--lotr-red);
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.7;
            z-index: 10;
        }

        .btn-delete:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .admin-controls {
            background: rgba(180, 0, 0, 0.1);
            border: 1px solid var(--lotr-red);
            padding: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-badge {
            color: var(--lotr-red);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .btn-delete-all {
            background: var(--lotr-red);
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Cinzel', serif;
        }

        /* Admin Gatekeeper Styles */
        .admin-gate {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--lotr-red);
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="page-header">
            <div class="eye-of-sauron"></div>
            <h1>Muro del Orco</h1>
            <p class="subtitle">Canal de comunicación de las tropas</p>
        </div>

        <?php if ($is_admin && !$admin_auth): ?>
            <!-- ADMIN GATEKEEPER: Password required just to SEE the wall as admin -->
            <div class="content-box admin-gate">
                <h2 style="color: var(--lotr-red);">⛔ Área Restringida</h2>
                <p style="color: var(--lotr-text); margin-bottom: 1.5rem;">
                    Esta sección está habilitada solo para el Profesor.<br>
                    No forma parte del examen para administradores del sistema.
                </p>

                <?php if ($auth_error): ?>
                    <p style="color: var(--lotr-red); font-weight: bold; margin-bottom: 1rem;"><?php echo $auth_error; ?></p>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="unlock_admin" value="1">
                    <input type="password" name="unlock_pass" placeholder="Contraseña de Profesor"
                        style="padding: 0.5rem; border: 1px solid var(--lotr-gold); background: #2c1a0e; color: var(--lotr-gold); margin-right: 0.5rem;">
                    <button type="submit" class="btn-primary">🔓 Acceder</button>
                </form>
            </div>

            <p style="text-align: center;"><a href="../" class="btn-back">← Volver a la Torre</a></p>

            <div class="footer">
                Sistema de Seguridad de Barad-dûr &bull; Acceso Restringido
            </div>

        <?php else: ?>
            <!-- WALL CONTENT (Visible to Guests OR Authenticated Admins) -->

            <div class="content-box">
                <h2>📢 Escribir en el Muro</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nombre (Opcional):</label>
                        <input type="text" name="author" placeholder="Uruk-hai #4291">
                    </div>
                    <div class="form-group">
                        <label>Mensaje:</label>
                        <textarea name="message" rows="3" placeholder="¡Carne fresca en el menú!" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">⚔ Grabar Mensaje</button>
                </form>
            </div>

            <div class="content-box">
                <h2>📜 Mensajes del Muro</h2>

                <?php if ($is_admin && $admin_auth): ?>
                    <div class="admin-controls">
                        <span class="admin-badge">🔥 Modo Profesor: Control Total</span>
                        <form method="POST" action="" style="margin: 0;"
                            onsubmit="return confirm('¿Borrar TODOS los mensajes?');">
                            <input type="hidden" name="delete_all" value="1">
                            <button type="submit" class="btn-delete-all">🗑 Borrar Todo</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="message-board">
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="message">
                            <!-- Show delete button ONLY if admin AND authenticated -->
                            <?php if ($is_admin && $admin_auth): ?>
                                <form method="POST" action="" style="margin: 0; display: inline;"
                                    onsubmit="return confirm('¿Eliminar mensaje?');">
                                    <input type="hidden" name="delete_id" value="<?php echo intval($msg['id']); ?>">
                                    <button type="submit" class="btn-delete" title="Eliminar mensaje">✕</button>
                                </form>
                            <?php endif; ?>

                            <!-- §2 CORREGIDO (Stored XSS): salida escapada con htmlspecialchars() -->
                            <div class="author"><?php
                                // VULNERABLE original (sin escapar): echo $msg['author'];
                                echo htmlspecialchars($msg['author'], ENT_QUOTES, 'UTF-8');
                            ?></div>

                            <div class="text"><?php
                                // VULNERABLE original (sin escapar): echo $msg['message'];
                                echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
                            ?></div>

                            <div class="time"><?php echo $msg['created_at']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <p style="text-align: center;"><a href="../" class="btn-back">← Volver a la Torre</a></p>

            <div class="footer">
                Un Anillo para gobernarlos a todos &bull; Comunicaciones de Mordor &bull; Tercera Edad
            </div>

            <?php if ($is_admin && $admin_auth): ?>
                <script>
                    // Admin tools: Auto-refresh and alert prevention
                    window.alert = function () { };

                    // Auto-delete blocked messages
                    (function () {
                        const messages = document.querySelectorAll('.message');
                        messages.forEach(msg => {
                            const text = msg.querySelector('.text');
                            if (text && text.innerHTML.toLowerCase().includes('alert')) {
                                const form = msg.querySelector('form');
                                if (form) {
                                    const formData = new FormData(form);
                                    fetch('', { method: 'POST', body: formData });
                                    msg.style.opacity = '0.3';
                                    msg.style.borderColor = 'var(--lotr-red)';
                                }
                            }
                        });
                    })();

                    setTimeout(() => { location.reload(); }, 120000);
                </script>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>

</html>