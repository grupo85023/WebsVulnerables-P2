pipeline {
    agent any

    options {
        timestamps()
        buildDiscarder(logRotator(numToKeepStr: '15'))
        disableConcurrentBuilds()
    }

    // Disparo automático: Jenkins consulta GitHub cada 2 minutos y lanza el
    // pipeline si hay commits nuevos (GitHub no puede llegar a la VM para un webhook).
    triggers {
        pollSCM('H/2 * * * *')
    }

    environment {
        SEMGREP_IMAGE = 'semgrep/semgrep:1.182.1'   // versión fija: resultados reproducibles
    }

    stages {
        stage('Checkout') {
            steps {
                // El workspace persiste entre builds: borrar los informes del build anterior
                sh 'rm -f semgrep.json semgrep.sarif semgrep.txt'
                checkout scm
                sh 'git log -1 --format="Commit %h de %an: %s"'
            }
        }

        stage('SAST: Semgrep') {
            steps {
                script {
                    // El contenedor de Semgrep solo recibe el código (no jenkins_home ni docker.sock)
                    def rc = sh(returnStatus: true, script: '''
                        docker run --rm -v "$WORKSPACE":/src -w /src ${SEMGREP_IMAGE} \
                          semgrep scan \
                            --config p/php --config p/owasp-top-ten \
                            --metrics=off --exclude db_data \
                            --json-output=semgrep.json \
                            --sarif-output=semgrep.sarif \
                            --text-output=semgrep.txt \
                            --error --text .
                    ''')
                    // --error: código 1 si hay hallazgos -> UNSTABLE; otro código -> fallo real de la herramienta
                    if (rc == 1) {
                        unstable('Semgrep ha encontrado vulnerabilidades')
                    } else if (rc != 0) {
                        error("Semgrep ha fallado con código ${rc}")
                    }
                }
            }
        }

        stage('Informe') {
            steps {
                archiveArtifacts artifacts: 'semgrep.json,semgrep.sarif,semgrep.txt', fingerprint: true
                recordIssues(
                    tools: [sarif(pattern: 'semgrep.sarif', id: 'semgrep', name: 'Semgrep')],
                    enabledForFailure: true
                )
            }
        }
    }

    post {
        always {
            sh 'echo "===== Resumen ====="; grep -A6 "Scan Summary" semgrep.txt || true'
        }
    }
}
