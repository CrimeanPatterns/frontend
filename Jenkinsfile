pipeline {
    agent any

    environment {
        COMPOSE_PROJECT_NAME = "${env.JOB_NAME}-${env.BUILD_ID}"
    }

    options{
        buildDiscarder(logRotator(numToKeepStr:'3'))
        ansiColor('xterm')
        timestamps()
        disableConcurrentBuilds()
    }

    stages {
        stage('cleanup'){
            steps {
                sh 'rm -Rf app/cache/*'
                sh 'rm -f tests/acceptance/*Guy.php'
                sh 'rm -f tests/functional/*Guy.php'
                sh 'rm -f tests/functional-symfony/*Guy.php'
                sh 'rm -f tests/unit/*Guy.php'
                sh 'mkdir -p tests/_log'
                sh 'rm -Rf tests/_log/*'
                sh 'rm -f app/config/parameters.yml'
            }
        }
        stage('prepare docker') {
            steps {
                sh 'echo LOCAL_USER_ID=`id -u $USER` >.env'
                sh 'echo COMPOSE_FILE=docker-compose.yml:docker-compose-tests.yml >>.env'
                sh 'docker-compose pull'
                sh 'docker-compose up -d'
            }
        }
        stage('prepare code') {
            steps {
                sh 'docker-compose exec --user user -T php sudo chown user:user /home/user'
                sh 'set pipefail; docker-compose exec --user user -T ./install-vendors.sh --prefer-source 2>&1 | grep -v Ambiguous'
                sh 'docker-compose exec --user user -T php app/console doctrine:migrations:migrate --no-interaction'
                sh 'docker-compose exec --user user -T php vendor/bin/codecept build'
                sh 'docker-compose exec --user user -T php docker/codeceptSplitTests.php'
            }
        }
        stage('warmup') {
            parallel {
                stage('warmup dev'){
                    steps {
                        sh 'docker-compose exec --user user -T php app/console cache:warmup'
                    }
                }
                stage('warmup codeception'){
                    steps {
                        sh 'docker-compose exec --user user -T php app/console cache:warmup --env=codeception --no-debug'
                    }
                }
            }
        }
        stage('run tests') {
            parallel {
                stage('group1'){
                    steps {
                        sh 'docker-compose exec --user user -T php vendor/bin/codecept run --xml=../_data/paracept/out_group_1.xml -g paracept_1 --skip-group slow --skip-group unstable --no-interaction'
                    }
                }
                stage('group2'){
                    steps {
                        sh 'docker-compose exec --user user -T php vendor/bin/codecept run --xml=../_data/paracept/out_group_2.xml -g paracept_2 --skip-group slow --skip-group unstable --no-interaction'
                    }
                }
                stage('group3'){
                    steps {
                        sh 'docker-compose exec --user user -T php vendor/bin/codecept run --xml=../_data/paracept/out_group_3.xml -g paracept_3 --skip-group slow --skip-group unstable --no-interaction'
                    }
                }
            }
        }
    }

    post {
        always {
            sh "git diff tests/_data/GoogleApiRequests.json >tests/_log/GoogleApiRequests.json.patch"
            archiveArtifacts allowEmptyArchive: true, artifacts: 'tests/_log/*'
            archiveArtifacts allowEmptyArchive: true, artifacts: 'tests/_data/paracept/out_group_*.xml'
            archiveArtifacts allowEmptyArchive: true, artifacts: 'tests/_data/GoogleApiRequests.json'
            sh "docker-compose down -v"
        }
    }
}