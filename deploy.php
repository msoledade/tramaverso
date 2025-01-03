<?php

namespace Deployer;

require 'recipe/statamic.php';

// Config

set('repository', '');

set('bin/php', function () {
    return '/usr/bin/php';
});

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('159.89.84.178')
    ->set('remote_user', 'msoledade')
    ->set('port', 22)
    ->set('deploy_path', '/var/www/tramaverso.com.br');

// Hooks

after('deploy:failed', 'deploy:unlock');
