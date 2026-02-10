pipeline {
    agent any

    environment {
        COMPOSE_PROJECT_NAME = "deploy-frontend"
    }

    options{
        buildDiscarder(logRotator(numToKeepStr:'30'))
        ansiColor('xterm')
        timestamps()
        disableConcurrentBuilds()
    }

    stages {
        stage('pull') {
            // will build on x86-builder, because jenkins has old docker version
            agent { label 'x86' }
            steps {
                buildName "${env.BUILD_ID} ${branch}"
                slackSend message: "started ${env.JOB_NAME} ${env.BUILD_NUMBER} ${branch} (<${env.BUILD_URL}|Open>)"
                sh '''
                GIT_REVISION=`git rev-parse --short HEAD`
                echo "GIT_REVISION=$GIT_REVISION" > BUILD_INFO
                echo "BRANCH=$branch" >> BUILD_INFO
                '''
                dir ('web/lib') {
                    git 'git@github.com:AwardWallet/lib.git'
                }
                dir ('engine') {
                    git 'git@github.com:AwardWallet/engine.git'
                }
                sshagent(['test-runner-2']) {
                    sh '''
                    git branch
                    push=true docker/prod/bake.sh
                    '''
                }
                script {
                    def fileContent = readFile 'BUILD_INFO'
                    fileContent.split('\n').each { line ->
                        def (key, value) = line.split('=')
                        env[key.trim()] = value.trim()
                    }
                }
                addBadge icon: '/plugin/badge/images/info.gif', link: "/job/Frontend/job/rollback-frontend/parambuild/?web=${env.WEB_REVISION}&workers=${env.WORKERS_REVISION}", text: 'Rollback to this deploy'
            }
        }
        stage('builder') {
            steps {
                dir('../deploy-frontend') {
                    sh '''
                    scp x86-builder.infra.awardwallet.com:/opt/jenkins-agent/workspace/Frontend/deploy-frontend/build/active_image build/active_image
                    scp x86-builder.infra.awardwallet.com:/opt/jenkins-agent/workspace/Frontend/deploy-frontend/BUILD_INFO BUILD_INFO
                    aws ecr get-login-password | docker login --username AWS --password-stdin 718278292471.dkr.ecr.us-east-1.amazonaws.com
                    docker-compose pull --quiet
                    docker-compose up -d
                    docker-compose run --rm php app/console aw:ssm-warmup-cache -vv
                    echo 718278292471.dkr.ecr.us-east-1.amazonaws.com/frontend/fpm:builder >>build/active_image
                    python3 /var/lib/jenkins/repositories/serverscripts/docker/delete-old-local-images.py '718278292471.dkr.ecr.us-east-1.amazonaws.com/frontend/fpm' 30
                    python3 /var/lib/jenkins/repositories/serverscripts/docker/delete-old-local-images.py '718278292471.dkr.ecr.us-east-1.amazonaws.com/frontend/worker' 30
                    '''
                }
            }
        }
    }

    post {
        success {
            slackSend message: "successfully deployed ${env.JOB_NAME} ${env.BUILD_NUMBER} ${branch} (<${env.BUILD_URL}|Open>)", color: 'good'
        }
        failure {
            slackSend message: "deploy failed: ${env.JOB_NAME} ${env.BUILD_NUMBER} (<${env.BUILD_URL}|Open>)", color: 'danger'
        }
        aborted {
            slackSend message: "deploy aborted: ${env.JOB_NAME} ${env.BUILD_NUMBER} (<${env.BUILD_URL}|Open>)", color: 'danger'
        }
    }

}