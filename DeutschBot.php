<?php
/**
 * @team Features <ft@corp.badoo.com>
 * @component Core
 * @maintainer Sergey Ryabko <sergey.ryabko@corp.badoo.com>
 */

require_once('DeutschBotApi.php');

class DeutschBot
{
    private $Api;

    public function __construct(\TelegramBot\Api\BotApi $Api)
    {
        $this->Api = new DeutschBotApi($Api);
    }

    public function getApi() : DeutschBotApi
    {
        return $this->Api;
    }

    /**
     * Print
     *
     * @return Closure
     */
    public function start() : Closure
    {
        $Bot = $this;
        return function (\TelegramBot\Api\Types\Message $Message) use ($Bot) {
            try {
                if (!$Message->getChat()) {
                    return;
                }

                $Bot->getApi()->start($Message->getChat()->getId());
            } catch (\Exception $Exception) {
                $Bot->handleException($Message->getChat()->getId(), $Exception->getMessage());
            }
        };
    }

    public function print() : Closure
    {
        $Bot = $this;
        return function (\TelegramBot\Api\Types\Message $Message) use ($Bot) {
            try {
                if (!$Message->getChat()) {
                    return;
                }

                $Bot->getApi()->print($Message->getChat()->getId(), $Message->getText());
            } catch (\Exception $Exception) {
                $Bot->handleException($Message->getChat()->getId(), $Exception->getMessage());
            }
        };
    }

    public function practice() : Closure
    {
        $Bot = $this;
        return function (\TelegramBot\Api\Types\Message $Message) use ($Bot) {
            try {
                if (!$Message->getChat()) {
                    return;
                }

                $Bot->getApi()->practice($Message->getChat()->getId(), $Message->getText());
            } catch (\Exception $Exception) {
                $Bot->handleException($Message->getChat()->getId(), $Exception->getMessage());
            }
        };
    }

    public function verbs() : Closure
    {
        $Bot = $this;
        return function (\TelegramBot\Api\Types\Message $Message) use ($Bot) {
            try {
                if (!$Message->getChat()) {
                    return;
                }

                $Bot->getApi()->verbs($Message->getChat()->getId(), $Message->getText());
            } catch (\Exception $Exception) {
                $Bot->handleException($Message->getChat()->getId(), $Exception->getMessage());
            }
        };
    }

    public function update() : Closure
    {
        $Bot = $this;
        return function (\TelegramBot\Api\Types\Update $Update) use ($Bot) {
            $Query = $Update->getCallbackQuery();

            if (!$Query || !$Query->getMessage() || !$Query->getMessage()->getChat()) {
                return;
            }

            try {
                $Bot->getApi()->update(
                    $Query->getMessage()->getChat()->getId(),
                    $Query->getData(),
                    $Query->getMessage()->getText(),
                    $Query->getMessage()->getMessageId(),
                    $Query->getId()
                );
            } catch (\Exception $Exception) {
                $Bot->handleException($Query->getMessage()->getChat()->getId(), $Exception->getMessage());
            }
        };
    }

    public function handleException($chat_id, string $message) : void
    {
        try {
            $message = mb_substr($message, 0, 4096);
            $this->Api->sendMessage($chat_id, $message);
        } catch (\Exception $Exception) {
            // suppress
        }
    }
}