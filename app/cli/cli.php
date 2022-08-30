<?php

if ($argc == 1) return;

define('ROOT_DIR', dirname(__DIR__));

require_once 'vendor/autoload.php';

use Garden\Cli\Cli;

$cli = Cli::create()
    ->command('server')
    ->description('Start PHP server.')
    ->opt('port:p', 'Set server port (default = 8000).', false, 'integer');
$args = $cli->parse($argv);
$command = $args->getCommand();

switch ($command) {
    case 'server':
        echo "\e[33;1mSymnext development server.\e[0m\n";
        $port = $args->getOpt('port') ?? '8000';
        system("php -S localhost:$port -t public");
        break;
}
