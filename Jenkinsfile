pipeline {
    agent any

    stages {
        stage('Install Dependencies') {
            steps {
                // Install Composer dependencies
                echo 'Install dependencies...'
                sh 'php --version'
                echo 'PAth : ${WORKSPACE}'
                sh '/usr/bin/php ${WORKSPACE}/composer.phar install'
            }
        }
        stage('Run Tests') {
            steps {
                echo 'Run tests with phpunit...'
                sh 'vendor/bin/phpunit'
                xunit([
                        thresholds: [
                                failed ( failureThreshold: "0" ),
                                skipped ( unstableThreshold: "0" )
                        ],
                        tools: [
                                PHPUnit(pattern: 'build/logs/junit.xml', stopProcessingIfError: true, failIfNotNew: true)
                        ]
                ])
                publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: false,
                        keepAll: false,
                        reportDir: 'build/coverage',
                        reportFiles: 'index.html',
                        reportName: 'Coverage Report (HTML)',
                        reportTitles: ''
                ])
                publishCoverage adapters: [coberturaAdapter('build/logs/cobertura.xml')]
            }
        }
        stage('SonarQube analysis') {
            steps {
                echo 'Check Quality with Sonarqube'
                withSonarQubeEnv('SonarMonext') {
                    sh 'mvn org.sonarsource.scanner.maven:sonar-maven-plugin:3.7.0.1746:sonar'
                }
            }
        }
    }
}
