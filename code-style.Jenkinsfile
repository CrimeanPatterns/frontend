pipeline {
    agent { label 'test-runner' }

    environment {
        COMPOSE_PROJECT_NAME = "${env.JOB_NAME}-${env.BUILD_ID}"
    }

    options{
        buildDiscarder(logRotator(numToKeepStr:'3'))
        disableConcurrentBuilds()
        ansiColor('xterm')
    }

    stages {
        stage('check code style') {
            steps {
                sh 'docker run -u `id -u $USER` --entrypoint bash -v `pwd`:/www/awardwallet --rm --workdir /www/awardwallet docker.awardwallet.com/php7/debug /www/awardwallet/tests/_scripts/check-code-style.sh'
            }
        }
    }

}