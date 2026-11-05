<?php
// Login de Mordor - Portal de Sauron
// §8 CORREGIDO (Session Hijacking): cookie de sesión protegida
// VULNERABLE original (cookie legible por JavaScript -> robable vía XSS):
//   // Sesiones PHP reales (sin httpOnly para permitir robo de cookie vía XSS)
//   ini_set('session.cookie_httponly', 0);
ini_set('session.cookie_httponly', 1);   // la cookie deja de ser accesible desde document.cookie
ini_set('session.cookie_samesite', 'Strict');
session_name("lotrCookie");
session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: private/");
    exit();
}

require_once 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: private/");
            exit();
        } else {
            $error = "Credenciales incorrectas. El Ojo de Sauron no te reconoce.";
        }
    } else {
        $error = "Debes introducir nombre y contraseña.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👁️</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barad-dûr - Portal de Sauron</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-box {
            max-width: 450px;
            margin: 0 auto;
        }

        .login-box .form-group input {
            text-align: center;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .login-ring {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border: 3px solid var(--lotr-gold);
            border-radius: 50%;
            position: relative;
            box-shadow: 0 0 30px var(--lotr-gold-glow), inset 0 0 20px var(--lotr-gold-glow);
            animation: ring-glow 3s ease-in-out infinite;
        }

        .login-ring::after {
            content: '💍';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.8rem;
        }

        @keyframes ring-glow {

            0%,
            100% {
                box-shadow: 0 0 30px var(--lotr-gold-glow), inset 0 0 20px var(--lotr-gold-glow);
            }

            50% {
                box-shadow: 0 0 50px var(--lotr-gold-glow), 0 0 80px rgba(255, 213, 79, 0.2), inset 0 0 30px var(--lotr-gold-glow);
            }
        }

        .inscription {
            text-align: center;
            font-style: italic;
            color: var(--lotr-gold-dim);
            font-size: 0.8rem;
            margin-top: 1.5rem;
            opacity: 0.6;
            line-height: 1.6;
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="page-header">
            <div class="eye-of-sauron"></div>
            <h1>Portal de Sauron</h1>
            <p class="subtitle">Acceso restringido &bull; Comandantes de Mordor</p>
        </div>

        <div class="content-box login-box">
            <div class="login-ring"></div>
            <h2 style="text-align: center; border: none;">⚔ Identifícate, Servidor</h2>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Nombre de Servidor</label>
                    <input type="text" name="username" placeholder="Tu nombre en la lengua oscura..." autocomplete="off"
                        required>
                </div>
                <div class="form-group">
                    <label>Palabra de Paso</label>
                    <input type="password" name="password" placeholder="La contraseña del Señor Oscuro..." required>
                </div>
                <button type="submit" class="btn-primary btn-login">🔥 Entrar a la Torre</button>
            </form>

            <p class="inscription">
                Ash nazg durbatulûk, ash nazg gimbatul,<br>
                ash nazg thrakatulûk, agh burzum-ishi krimpatul.
            </p>
        </div>

        <p style="text-align: center;"><a href="./" class="btn-back">← Volver a la Torre</a></p>

        <div class="footer">
            Barad-dûr &bull; Mordor &bull; Solo los servidores del Señor Oscuro pueden entrar
        </div>
    </div>
</body>

</html>