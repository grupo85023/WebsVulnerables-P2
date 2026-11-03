pipeline {
    agent any

    options {
        timestamps()
        buildDiscarder(logRotator(numToKeepStr: '10'))
        disableConcurrentBuilds()   // el laboratorio usa nombres de contenedor y puertos fijos
    }

    parameters {
        choice(name: 'ZAP_MODE', choices: ['baseline', 'full'],
               description: 'baseline: spider autenticado + análisis pasivo (rápido). full: añade escaneo activo autenticado, máx. 20 min por sitio.')
    }

    // Disparo automático por commit: Jenkins consulta GitHub cada 2 minutos
    triggers {
        pollSCM('H/2 * * * *')
    }

    environment {
        SEMGREP_IMAGE   = 'semgrep/semgrep:latest'
        ZAP_IMAGE       = 'ghcr.io/zaproxy/zaproxy:stable'
        ZAP_PORT        = '8099'
        ZAP_ACTIVE_MINS = '20'
        LAB_PROJECT     = 'examenweb-ci'
        LAB_COMPOSE     = 'docker compose -p examenweb-ci -f ExamenWeb/docker-compose.yml -f compose.ci.yml'
        // sitio:puerto en el host (puertos distintos a los del laboratorio original).
        // sitio4 no tiene código en el repositorio (su html/ está vacío), por eso no se escanea.
        LAB_SITES       = 'sitio1:55340 sitio2:55350 sitio3:55360'
    }

    stages {
        stage('Checkout') {
            steps {
                // El workspace persiste entre builds: borrar los resultados del anterior para que
                // Semgrep no analice los informes de ZAP ni se archiven ficheros viejos.
                sh 'rm -rf zap semgrep.json semgrep.sarif semgrep.txt compose.ci.yml'
                checkout scm   // mismo repo que la tarea: evita builds en bucle con pollSCM
                // Los datos de MySQL (db_data) deben quedar exactamente como en el repositorio: MySQL
                // modifica ficheros con seguimiento y crea otros nuevos (redo logs, binlogs); si se
                // mezclan con los restaurados por Git, InnoDB no arranca ("Missing redo log file").
                sh '''
                    git config --global --add safe.directory "$WORKSPACE" || true
                    git clean -fdx -- ExamenWeb
                    git checkout -- ExamenWeb
                '''
            }
        }

        stage('SAST: Semgrep') {
            steps {
                script {
                    // Jenkins corre en un contenedor: se comparte su volumen con el
                    // contenedor de Semgrep (--volumes-from) para que vea el workspace.
                    def rc = sh(returnStatus: true, script: '''
                        docker run --rm --volumes-from jenkins -w "$WORKSPACE" ${SEMGREP_IMAGE} \
                          semgrep scan \
                            --config p/php --config p/owasp-top-ten \
                            --metrics=off --exclude db_data --exclude zap \
                            --json-output=semgrep.json \
                            --sarif-output=semgrep.sarif \
                            --text-output=semgrep.txt \
                            --error --text .
                    ''')
                    // Con --error Semgrep devuelve 1 cuando hay hallazgos: el build queda UNSTABLE, no FAILED
                    if (rc == 1) {
                        unstable('Semgrep ha encontrado vulnerabilidades')
                    } else if (rc != 0) {
                        error("Semgrep ha fallado con código ${rc}")
                    }
                }
            }
        }

        stage('Desplegar laboratorio') {
            steps {
                // Override del compose del repositorio: nombres de contenedor y puertos propios
                // para no chocar con el laboratorio que pueda estar corriendo en el host.
                writeFile file: 'compose.ci.yml', text: '''
services:
  db1: { container_name: ci_mysql_examen_1, restart: "no" }
  db2: { container_name: ci_mysql_examen_2, restart: "no" }
  db3: { container_name: ci_mysql_examen_3, restart: "no" }
  db4: { container_name: ci_mysql_examen_4, restart: "no" }
  web1: { container_name: ci_web_examen_1, ports: !override ["55340:80"] }
  web2: { container_name: ci_web_examen_2, ports: !override ["55350:80"] }
  web3: { container_name: ci_web_examen_3, ports: !override ["55360:80"] }
  web4: { container_name: ci_web_examen_4, ports: !override ["55370:80"] }
'''
                sh '''
                    ${LAB_COMPOSE} up -d --build
                    echo "Esperando a las bases de datos..."
                    for n in 1 2 3 4; do
                        for i in $(seq 1 30); do
                            docker exec ci_mysql_examen_$n mysqladmin ping -uroot -proot_pass_examen$n --silent 2>/dev/null && break
                            sleep 2
                        done
                        docker exec ci_mysql_examen_$n mysqladmin ping -uroot -proot_pass_examen$n --silent 2>/dev/null || { echo "MySQL $n no responde"; exit 1; }
                    done
                    echo "Comprobando los sitios web..."
                    for s in ${LAB_SITES}; do
                        port=${s#*:}
                        code=$(docker run --rm --network host ${ZAP_IMAGE} curl -s -o /dev/null -w '%{http_code}' http://localhost:$port/)
                        echo "  ${s%%:*} -> http://localhost:$port -> HTTP $code"
                    done
                '''
            }
        }

        stage('DAST: OWASP ZAP') {
            steps {
                script {
                    sh 'rm -rf zap && mkdir -p zap && chmod 777 zap'   // ZAP corre como usuario zap (uid 1000)
                    def full = (params.ZAP_MODE == 'full')
                    // Cómo se autentica ZAP en cada sitio: formulario de login, cuerpo del POST,
                    // regex que indica sesión iniciada / cerrada y URL de logout a excluir del escaneo.
                    // Las credenciales viven en Jenkins (Credentials: zap-sitio1, zap-sitio2, zap-sitio3)
                    // y llegan al contenedor de ZAP como variables de entorno, nunca escritas en disco.
                    def sitios = [
                        [nombre: 'sitio1', puerto: '55340', cred: 'zap-sitio1', login: 'index.php',
                         cuerpo: 'user={%username%}&pass={%password%}',
                         dentro: '\\\\Qlogout.php\\\\E', fuera: 'name=\\"pass\\"', logout: 'logout\\\\.php.*'],
                        [nombre: 'sitio2', puerto: '55350', cred: 'zap-sitio2', login: 'login.php',
                         cuerpo: 'username={%username%}&password={%password%}',
                         dentro: '\\\\Qlogout.php\\\\E', fuera: 'name=\\"password\\"', logout: 'logout\\\\.php.*'],
                        [nombre: 'sitio3', puerto: '55360', cred: 'zap-sitio3', login: 'login.php',
                         cuerpo: 'username={%username%}&password={%password%}',
                         dentro: '\\\\Qlogout=1\\\\E', fuera: 'name=\\"password\\"', logout: 'login\\\\.php\\\\?logout.*'],
                    ]
                    def hayAlertas = false
                    sitios.each { s ->
                        def base = "http://localhost:${s.puerto}"
                        def modo = full ? 'full' : 'baseline'
                        // Plan del Automation Framework de ZAP
                        def activo = full ? """
  - type: activeScan
    parameters: { context: ${s.nombre}, user: usuario, maxScanDurationInMins: ${env.ZAP_ACTIVE_MINS} }""" : ''
                        def informes = ''
                        ['traditional-html': 'html', 'traditional-json': 'json', 'traditional-xml': 'xml', 'traditional-md': 'md'].each { tpl, ext ->
                            informes += """
  - type: report
    parameters: { template: ${tpl}, reportDir: /zap/wrk, reportFile: ${s.nombre}, reportTitle: "ZAP ${s.nombre} (${modo})" }
    risks: [info, low, medium, high]"""
                        }
                        writeFile file: "zap/plan-${s.nombre}.yaml", text: """env:
  contexts:
    - name: ${s.nombre}
      urls: ["${base}"]
      includePaths: ["${base}.*"]
      excludePaths: ["${base}/${s.logout}"]
      authentication:
        method: form
        parameters:
          loginPageUrl: "${base}/${s.login}"
          loginRequestUrl: "${base}/${s.login}"
          loginRequestBody: "${s.cuerpo}"
        verification:
          method: response
          loggedInRegex: "${s.dentro}"
          loggedOutRegex: "${s.fuera}"
      sessionManagement:
        method: cookie
      users:
        - name: usuario
          credentials:
            username: "\${ZAP_USER}"
            password: "\${ZAP_PASS}"
  parameters: { failOnError: true, failOnWarning: false, progressToStdout: true }
jobs:
  - type: spider
    parameters: { context: ${s.nombre}, user: usuario, maxDuration: 1 }
  - type: passiveScan-wait${activo}${informes}
"""
                        echo "ZAP ${modo}${full ? ' (activo, máx. ' + env.ZAP_ACTIVE_MINS + ' min)' : ''} contra ${s.nombre} (${base}), autenticado"
                        withCredentials([usernamePassword(credentialsId: s.cred, usernameVariable: 'ZAP_USER', passwordVariable: 'ZAP_PASS')]) {
                            def rc = sh(returnStatus: true, script: """
                                docker run --rm --name ci_zap --network host -e ZAP_USER -e ZAP_PASS \\
                                  -v "\$WORKSPACE/zap":/zap/wrk:rw ${ZAP_IMAGE} \\
                                  zap.sh -cmd -port ${ZAP_PORT} -autorun /zap/wrk/plan-${s.nombre}.yaml \\
                                  | tee zap/${s.nombre}.log
                            """)
                            if (rc != 0) { error("ZAP ha fallado en ${s.nombre} con código ${rc}") }
                        }
                        // Resumen por riesgo a partir del informe JSON (riskcode: 3 High, 2 Medium, 1 Low, 0 Info)
                        def resumen = sh(returnStdout: true, script: """
                            for r in 3 2 1 0; do printf '%s ' \$(grep -c '"riskcode": *"'\$r'"' zap/${s.nombre}.json); done
                        """).trim().split(' ').collect { it as int }
                        echo "${s.nombre}: High=${resumen[0]} Medium=${resumen[1]} Low=${resumen[2]} Info=${resumen[3]}"
                        if (resumen[0] + resumen[1] + resumen[2] > 0) { hayAlertas = true }
                    }
                    if (hayAlertas) { unstable('ZAP ha encontrado alertas') }
                }
            }
        }

        stage('Informes') {
            steps {
                archiveArtifacts artifacts: 'semgrep.json,semgrep.sarif,semgrep.txt,zap/*.html,zap/*.json,zap/*.xml,zap/*.md', fingerprint: true
                recordIssues(
                    tools: [sarif(pattern: 'semgrep.sarif', id: 'semgrep', name: 'Semgrep')],
                    enabledForFailure: true
                )
                publishHTML(target: [
                    reportName: 'ZAP DAST',
                    reportDir: 'zap',
                    reportFiles: 'sitio1.html,sitio2.html,sitio3.html',
                    reportTitles: 'Sitio 1,Sitio 2,Sitio 3',
                    keepAll: true, alwaysLinkToLastBuild: true, allowMissing: true
                ])
            }
        }
    }

    post {
        always {
            // Apagar ZAP y el laboratorio pase lo que pase (si la etapa de despliegue no llegó a crear
            // el override, no hay nada que apagar)
            sh 'docker rm -f ci_zap 2>/dev/null || true'
            sh '[ -f compose.ci.yml ] && ${LAB_COMPOSE} down --remove-orphans || true'
            sh '''
                echo "===== Resumen ====="
                if [ -f semgrep.json ]; then
                    echo "Semgrep: $(grep -o '"check_id"' semgrep.json | wc -l) hallazgos"
                    grep -o '"severity": *"[A-Z]*"' semgrep.json | sort | uniq -c || true
                fi
                for f in zap/sitio1.json zap/sitio2.json zap/sitio3.json; do
                    if [ -f "$f" ]; then
                        echo "$f: High=$(grep -c '"riskcode": *"3"' $f) Medium=$(grep -c '"riskcode": *"2"' $f) Low=$(grep -c '"riskcode": *"1"' $f)"
                    fi
                done
            '''
        }
    }
}
