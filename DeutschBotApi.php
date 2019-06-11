<?php
/**
 * @team Features <ft@corp.badoo.com>
 * @component Core
 * @maintainer Sergey Ryabko <sergey.ryabko@corp.badoo.com>
 */

require_once "PracticeCaseManager.php";

class DeutschBotApi
{
    /**
     * Api
     *
     * @var \TelegramBot\Api\BotApi
     */
    private $Api;

    public function __construct(\TelegramBot\Api\BotApi $Api)
    {
        $this->Api = $Api;
    }

    /**
     * Print
     *
     * @param int $chat_id Chat_id
     * @param string $text Text
     *
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @throws \Exception
     */
    public function print(int $chat_id, string $text) : void
    {
        $Manager = new PracticeCaseManager();

        $set = $Manager->extractSetFromCommand($text);
        if (!$set) {
            throw new \Exception('No pack provided');
        }

        $cases = $Manager->getCases($set);
        $answer = implode($cases, PHP_EOL);
        $this->Api->sendMessage($chat_id, $answer);
    }

    /**
     * Practice
     *
     * @param int $chat_id Chat_id
     * @param string $input Input
     *
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @throws \Exception
     */
    public function practice(int $chat_id, string $input) : void
    {
        $CaseManager = new PracticeCaseManager();

        $set = $CaseManager->extractSetFromCommand($input);

        if (!$set) {
            throw new \Exception('No pack provided');
        }

        $CaseManager->startSession($chat_id, $set);

        list($key, $text) = $CaseManager->getCase($chat_id);

        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Show', 'callback_data' => $key],
                ],
            ]
        );

        $this->sendMessage($chat_id, $text, $keyboard);
    }

    /**
     * Start
     *
     * @param int $chat_id Chat_id
     *
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function start(int $chat_id) : void
    {
        $answer = "/packs to see all available packs\n/practice <pack name> to practice\n/print <pack name> to print the content";

        exec("cd data ; find . -type f -printf '%P\n' | grep -v ^verbs$ | sort", $output);

        $buttons = [];
        foreach ($output as $file) {
            $buttons[] = [['text' => "/practice $file"], ['text' => "/print $file"]];
        }

        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttons, true, true);
        $this->sendMessage($chat_id, $answer, $keyboard);
    }

    /**
     * Verbs
     *
     * @param int $chat_id Chat_id
     * @param string $input Input
     *
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     * @throws \Exception
     */
    public function verbs(int $chat_id, string $input) : void
    {
        $input = trim(str_replace('/verbs', '', $input));
        $input = preg_replace('/\D+/', '-', $input);
        $boundaries = explode('-', $input);
        if (count($boundaries) !== 2 || !is_numeric($boundaries[0]) || !is_numeric($boundaries[1])) {
            throw new \Exception('Invalid verb query');
        }

        $query = sprintf('/practice verbs%s-%s', $boundaries[0], $boundaries[1]);

        $this->practice($chat_id, $query);
    }

    /**
     * Update
     *
     * @param int $chat_id Chat_id
     * @param int $answer_key Answer_key
     * @param string $message_text Message_text
     * @param int $message_id Message_id
     * @param int $query_id Query_id
     *
     * @return void
     * @throws \Exception
     */
    public function update(int $chat_id, int $answer_key, string $message_text, int $message_id, int $query_id) : void
    {
        $CaseManager = new PracticeCaseManager();
        $is_answer = $answer_key >= 0;

        $button = $is_answer ? 'Next' : 'Show';
        list($answer_key, $text, $is_new_iteration) = $is_answer ? $CaseManager->getAnswer($chat_id, $answer_key, $message_text) : $CaseManager->getCase($chat_id);

        $alert_text = $is_new_iteration ? 'New iteration' : null;

        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => $button, 'callback_data' => $answer_key],
                ],
            ]
        );

        $this->Api->editMessageText($chat_id, $message_id, $text, null, false, $keyboard);
        $this->Api->answerCallbackQuery($query_id, $alert_text, $is_new_iteration);
    }

    /**
     * Send message
     *
     * @param int $chat_id Chat_id
     * @param string $text Text
     * @param \TelegramBot\Api\BaseType $keyboard Keyboard
     *
     * @return void
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public function sendMessage(int $chat_id, string $text, \TelegramBot\Api\BaseType $keyboard = null) : void
    {
        $this->Api->sendMessage($chat_id, $text, null, false, null, $keyboard);
    }
}