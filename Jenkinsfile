pipeline {
    agent any

    stages {

        stage('Checkout code') {
            steps {
                script {
                    git branch: 'pipeline',
                            credentialsId: 'tgenty',
                            url: 'https://github.com/PaylineByMonext/payline-php-sdk.git'
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                // Install Composer dependencies
                echo 'Running PHP tests...'
                sh 'php82 -v'
                echo 'Install dependencies...'
                sh 'cd $WORKSPACE'
                echo 'Installing project composer dependencies...'
                sh "chmod +x -R $WORKSPACE/composer.phar"
                sh '$WORKSPACE/composer.phar install --no-progress'
            }
        }
        stage('Run Tests') {
            agent {
                docker {
                    image 'allebb/phptestrunner-74:latest'
                    args '-u root:sudo'
                }
            }
            steps {
                echo 'Running PHP 7.4 tests...'
                sh 'php -v'
                echo 'Installing Composer'
                sh 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer'
                echo 'Installing project composer dependencies...'
                sh 'cd $WORKSPACE && composer install --no-progress'
                echo 'Running PHPUnit tests...'
                sh 'php $WORKSPACE/vendor/bin/phpunit tests'
//                sh 'php $WORKSPACE/vendor/bin/phpunit --coverage-html $WORKSPACE/report/clover --coverage-clover $WORKSPACE/report/clover.xml --log-junit $WORKSPACE/report/junit.xml'
                sh 'chmod -R a+w $PWD && chmod -R a+w $WORKSPACE'
                junit 'build/coverage/*.xml'
            }
//            steps {
//                echo 'Running PHP 7.4 tests...'
//                sh 'php $WORKSPACE/vendor/bin/phpunit tests'
//                xunit([
//                        thresholds: [
//                                failed ( failureThreshold: "0" ),
//                                skipped ( unstableThreshold: "0" )
//                        ],
//                        tools: [
//                                PHPUnit(pattern: 'build/logs/junit.xml', stopProcessingIfError: true, failIfNotNew: true)
//                        ]
//                ])
//                publishHTML([
//                        allowMissing: false,
//                        alwaysLinkToLastBuild: false,
//                        keepAll: false,
//                        reportDir: 'build/coverage/html',
//                        reportFiles: 'index.html',
//                        reportName: 'Coverage Report (HTML)',
//                        reportTitles: ''
//                ])
//                publishCoverage adapters: [coberturaAdapter('build/coverage/xml/index.xml')]
//            }
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
