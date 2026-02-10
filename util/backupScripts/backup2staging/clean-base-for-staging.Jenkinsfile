pipeline {
    agent any

    options{
        buildDiscarder(logRotator(numToKeepStr:'30'))
        ansiColor('xterm')
        timestamps()
        disableConcurrentBuilds()
    }

    stages {
        stage('builder') {
            steps {
                sh '/var/lib/jenkins/workspace/Frontend/deploy-frontend/util/backupScripts/backup2staging/spotBackup.sh'
                build wait: false, job: '../deploy-dev-desktop'
                build wait: false, job: '../deploy-staging'
            }
        }
    }

    post {
        always {
            sh '/var/lib/jenkins/workspace/Frontend/deploy-frontend/util/backupScripts/backup2staging/spotCancel.py'
        }
        failure {
            slackSend message: "clean-base-for-staging failed: ${env.JOB_NAME} ${env.BUILD_NUMBER} (<${env.BUILD_URL}|Open>)", color: 'danger'
        }
    }

}