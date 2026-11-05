<?php
session_name("StarWarsCookie");
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Database connection
require_once __DIR__ . '/db.php';

$error = "";

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    // VULNERABLE: SQL Injection - No prepared statements, no sanitization
    // CORREGIDO: sentencia preparada, los datos van separados de la consulta SQL
    $stmt = $conn->prepare("SELECT	 * FROM users WHERE user = ? AND pass = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $row['user'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "ACCESO DENEGADO - Credenciales Imperiales no válidas";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⭐</text></svg>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estrella de la Muerte - Terminal de Acceso Imperial</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <div class="death-star-logo"></div>
        <h1 class="login-title">Estrella de la Muerte</h1>
        <p class="login-subtitle">Terminal de Acceso Imperial v2.4</p>

        <form class="login-form" method="POST" action="">
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>ID de Oficial</label>
                <input type="text" name="user" placeholder="Introduce tu ID de Oficial" required>
            </div>

            <div class="form-group">
                <label>Código de Acceso</label>
                <input type="password" name="pass" placeholder="Introduce tu Código de Acceso" required>
            </div>

            <button type="submit" class="btn-login">Autenticar</button>
        </form>

        <button type="button" class="btn-instructions" onclick="openCrawl()">📋 Instrucciones del Examen</button>

        <div class="footer">
            Imperio Galáctico &bull; Nivel de Seguridad: Máximo &bull; El acceso no autorizado será reportado a Lord
            Vader
        </div>
    </div>

    <!-- Star Wars Crawl Modal -->
    <div id="crawlModal" class="crawl-modal">
        <button class="crawl-close" onclick="closeCrawl()">✕ CERRAR TRANSMISIÓN</button>

        <div class="crawl-intro" id="crawlIntro">
            <p>Hace mucho tiempo, en una galaxia<br>muy, muy lejana....</p>
        </div>

        <div class="crawl-logo" id="crawlLogo">
            <h1>EXAMEN DE<br>CIBERSEGURIDAD WEB</h1>
        </div>

        <div class="crawl-wrapper" id="crawlWrapper">
            <div class="crawl-perspective">
                <div class="crawl-content" id="crawlContent">
                    <h2>EPISODIO I</h2>
                    <h3>LA AMENAZA REBELDE</h3>
                    <p>
                        La galaxia se encuentra en un estado de caos.
                        El Imperio Galáctico ha construido la estación
                        de batalla más poderosa jamás concebida:
                        la ESTRELLA DE LA MUERTE.
                    </p>
                    <p>
                        Sin embargo, un grupo de agentes rebeldes
                        ha conseguido infiltrarse en los sistemas
                        informáticos de la estación. Tú eres uno
                        de esos agentes encubiertos.
                    </p>
                    <p>
                        Tu misión es clara: debes atravesar
                        las defensas del sistema imperial,
                        encontrar el código de apertura de la
                        trampilla de ventilación térmica del
                        reactor y abrirla.
                    </p>
                    <p>
                        Una vez abierta la trampilla, deberás
                        transmitir las coordenadas al Escuadrón
                        Rojo para que puedan lanzar un torpedo
                        de protones al núcleo del reactor y
                        destruir la Estrella de la Muerte.
                    </p>
                    <p>
                        Cada FLAG que captures es una prueba
                        de tu infiltración y una victoria
                        para la Alianza Rebelde.
                    </p>
                    <p class="crawl-instructions">
                        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                    </p>
                    <h3>OBJETIVOS DE LA MISIÓN</h3>
                    <p>
                        1. Vulnera la terminal de acceso
                        imperial y consigue entrar al
                        sistema sin credenciales.
                    </p>
                    <p>
                        2. Explora los sistemas internos
                        de la estación y sus directorios
                        ocultos.
                    </p>
                    <p>
                        3. Encuentra el código clasificado
                        de apertura de la trampilla de
                        ventilación térmica del reactor.
                    </p>
                    <p>
                        4. Abre la trampilla y transmite
                        la señal al Escuadrón Rojo para
                        destruir la Estrella de la Muerte.
                    </p>
                    <p>
                        5. Captura todas las FLAGS y
                        documenta cada vulnerabilidad
                        descubierta en tu informe rebelde.
                    </p>
                    <p class="crawl-instructions">
                        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                    </p>
                    <p>
                        Que la Fuerza te acompañe, agente.
                        La Alianza Rebelde confía en ti.
                    </p>
                </div>
            </div>
        </div>

        <!-- Playback Controls -->
        <div class="crawl-controls" id="crawlControls">
            <button class="crawl-ctrl-btn" onclick="crawlRewind()" title="Rebobinar">⏪</button>
            <button class="crawl-ctrl-btn" id="btnPlayPause" onclick="crawlPlayPause()" title="Pausar">⏸</button>
            <button class="crawl-ctrl-btn" onclick="crawlForward()" title="Adelantar">⏩</button>
        </div>
    </div>

    <script>
        // Crawl animation state
        let crawlPosition = 100; // percentage from top (100 = bottom start)
        let crawlSpeed = 1;      // 1 = normal, 0 = paused, 3 = fast, -3 = rewind
        let crawlPlaying = false;
        let crawlAnimFrame = null;
        let crawlLastTime = null;
        let introTimeouts = [];

        const CRAWL_DURATION = 60000; // 60s for full crawl
        const CRAWL_START = 100;      // start position %
        const CRAWL_END = -250;       // end position %
        const CRAWL_RANGE = CRAWL_START - CRAWL_END; // total % to travel

        function openCrawl() {
            const modal = document.getElementById('crawlModal');
            const intro = document.getElementById('crawlIntro');
            const logo = document.getElementById('crawlLogo');
            const wrapper = document.getElementById('crawlWrapper');
            const content = document.getElementById('crawlContent');
            const controls = document.getElementById('crawlControls');

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Reset state
            crawlPosition = CRAWL_START;
            crawlSpeed = 1;
            crawlPlaying = false;
            crawlLastTime = null;
            if (crawlAnimFrame) cancelAnimationFrame(crawlAnimFrame);
            introTimeouts.forEach(t => clearTimeout(t));
            introTimeouts = [];

            intro.style.opacity = '0';
            logo.style.opacity = '0';
            logo.style.transform = 'scale(1)';
            wrapper.style.opacity = '0';
            controls.style.opacity = '0';
            content.style.top = CRAWL_START + '%';

            updatePlayPauseBtn();

            // Phase 1: "A long time ago..." text
            introTimeouts.push(setTimeout(() => {
                intro.style.opacity = '1';
            }, 500));

            // Phase 2: Fade out intro
            introTimeouts.push(setTimeout(() => {
                intro.style.opacity = '0';
            }, 4500));

            // Phase 3: Show logo
            introTimeouts.push(setTimeout(() => {
                logo.style.opacity = '1';
                logo.style.transform = 'scale(1)';
                introTimeouts.push(setTimeout(() => {
                    logo.style.transform = 'scale(0.3)';
                    logo.style.opacity = '0';
                }, 2000));
            }, 5500));

            // Phase 4: Start crawl
            introTimeouts.push(setTimeout(() => {
                wrapper.style.opacity = '1';
                controls.style.opacity = '1';
                crawlPlaying = true;
                crawlSpeed = 1;
                updatePlayPauseBtn();
                crawlLastTime = performance.now();
                crawlAnimFrame = requestAnimationFrame(animateCrawl);
            }, 8500));
        }

        function animateCrawl(timestamp) {
            if (!crawlPlaying && crawlSpeed === 0) {
                crawlAnimFrame = requestAnimationFrame(animateCrawl);
                crawlLastTime = timestamp;
                return;
            }

            if (crawlLastTime === null) crawlLastTime = timestamp;
            const delta = timestamp - crawlLastTime;
            crawlLastTime = timestamp;

            // Calculate how much to move per ms
            const movePerMs = (CRAWL_RANGE / CRAWL_DURATION) * crawlSpeed;
            crawlPosition -= movePerMs * delta;

            // Clamp position
            if (crawlPosition > CRAWL_START) crawlPosition = CRAWL_START;
            if (crawlPosition < CRAWL_END) crawlPosition = CRAWL_END;

            document.getElementById('crawlContent').style.top = crawlPosition + '%';

            crawlAnimFrame = requestAnimationFrame(animateCrawl);
        }

        function crawlPlayPause() {
            if (crawlSpeed !== 0) {
                // Pause
                crawlSpeed = 0;
                crawlPlaying = false;
            } else {
                // Play at normal speed
                crawlSpeed = 1;
                crawlPlaying = true;
            }
            updatePlayPauseBtn();
        }

        function crawlForward() {
            crawlSpeed = 3;
            crawlPlaying = true;
            updatePlayPauseBtn();
        }

        function crawlRewind() {
            crawlSpeed = -3;
            crawlPlaying = true;
            updatePlayPauseBtn();
        }

        function updatePlayPauseBtn() {
            const btn = document.getElementById('btnPlayPause');
            if (crawlSpeed === 0) {
                btn.textContent = '▶';
                btn.title = 'Reproducir';
            } else {
                btn.textContent = '⏸';
                btn.title = 'Pausar';
            }
        }

        function closeCrawl() {
            const modal = document.getElementById('crawlModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            crawlPlaying = false;
            crawlSpeed = 0;
            if (crawlAnimFrame) cancelAnimationFrame(crawlAnimFrame);
            introTimeouts.forEach(t => clearTimeout(t));
            introTimeouts = [];
        }

        // Close with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeCrawl();
        });
    </script>
</body>

</html>
