<?php
/**
 * @team Features <ft@corp.badoo.com>
 * @component Core
 * @maintainer Sergey Ryabko <sergey.ryabko@corp.badoo.com>
 */

class TelegramBot extends \TelegramBot\Api\Client
{
    public function getApi() : \TelegramBot\Api\BotApi
    {
        return $this->api;
    }
}