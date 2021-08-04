<?php
namespace Deployer;

require 'recipe/laravel.php';
require 'recipe/rsync.php';


// Project name
set('application', getenv('CI_PROJECT_NAME'));
//Default host.
set('default_stage','production');

set('ssh_multiplexing',true);
set('use_relative_symlinks', false);

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false);

set('rsync_src',function(){
    return __DIR__;
});


add('rsync', [
    'exclude' => [
        '.git',
        '/.env',
        '/storage/',
        '/vendor/',
        '/node_modules/',
        '.gitlab-ci.yml',
        'deploy.php',
    ],
]);

task('deploy:secrets', function () {
    file_put_contents(__DIR__ . '/.env', getenv('DOT_ENV'));
    upload('.env', get('deploy_path') . '/shared');
});


set('allow_anonymous_stats', false);

// Hosts
host(getenv('CI_HOST_NAME'))    
    ->hostname(getenv('CI_HOST')) 
    ->stage('production')
    ->user(getenv('CI_USER'))
    ->set('deploy_path', '~/public_html/{{application}}');

// Tasks
// Unlock after failed deploy
after('deploy:failed', 'deploy:unlock'); 

desc('Deploy the application');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync', 
    'deploy:secrets', 
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link', 
    'artisan:view:cache',  
    'artisan:config:cache',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);

//Reset the php service, only neccessary when running multiple version of PHP within VM.
desc('Rerun the php service.');
task('reset-service', function(){
  run('sudo /bin/systemctl restart php7.4-fpm');
  writeln('Restarted the PHP service on the remote host.');
});