<?php

require_once "vendor/autoload.php";
require_once "TelegramBot.php";
require_once "DeutschBot.php";
require_once "Token.php";

$bot = new TelegramBot($token);
$Api = $bot->getApi();
$Bot = new DeutschBot($Api);

$bot->command($command = 'start', $Bot->$command());
$bot->command($command = 'print', $Bot->$command());
$bot->command($command = 'practice', $Bot->$command());
$bot->command($command = 'verbs', $Bot->$command());

$bot->on(
    $Bot->update(),
    function () {
        return true;
    }
);

$bot->run();