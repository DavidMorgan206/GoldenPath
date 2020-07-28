pipeline {
    agent {
        dockerfile {
        filename 'Dockerfile'
        }
    }
    stages {
        stage('Unit Tests') {
            steps {
                    sh label: 'Codeception Tests', script: '''
                    cd /repo/test/codeception
                    ./vendor/codeception/codeception/codecept run unit --xml
                    cd tests/_output
                    cp * $WORKSPACE
                    '''
            }
            post {
                always {
                junit '*.xml'
                }
            }
        }
    }
}

