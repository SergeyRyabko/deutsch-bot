<?php
/**
 * @team Features <ft@corp.badoo.com>
 * @component Core
 * @maintainer Sergey Ryabko <sergey.ryabko@corp.badoo.com>
 */

class PracticeCaseManager
{
    protected function getChatData(int $chat_id) : array
    {
        $Connection = $this->getConnection();
        $Statement = $Connection->prepare('select data, `set` from chats where chat_id = :chat_id');
        $Statement->execute(['chat_id' => $chat_id]);

        $data = $Statement->fetchAll();
        $data = reset($data);

        if (!$data) {
            throw new \Exception('Invalid pack');
        }

        $data['data'] = json_decode($data['data']);

        return $data;
    }

    public function extractSetFromCommand(string $command) : string
    {
        $set = '';
        if ($pos = strpos($command, ' ')) {
            $set = trim(substr($command, $pos + 1));
        }

        return $set;
    }

    protected function updateChatData(int $chat_id, array $data) : void
    {
        $Connection = $this->getConnection();
        $Statement = $Connection->prepare('update chats set data = :data where chat_id = :chat_id');
        $Statement->execute(['chat_id' => $chat_id, 'data' => json_encode($data)]);
    }

    protected function getVerbCases(string $query) : array
    {
        preg_match_all('/verbs(\d+)-(\d+)/', $query, $matches);
        $matches = array_column($matches ?? [], 0);

        if (count($matches) < 3 || $matches[1] > $matches[2]) {
            throw new \Exception('Invalid verb query');
        }

        $from = $matches[1] - 1;
        $to = $matches[2] - 1;
        $count = $to - $from + 1;

        $verbs = file_get_contents('data/verbs');
        if (!$verbs) {
            throw new \Exception('Verbs data not found');
        }

        $verbs = explode(PHP_EOL, $verbs);
        $verbs = array_slice($verbs, $from, $count);

        return array_filter($verbs);
    }

    public function getCases(string $query) : array
    {
        if (strpos($query, 'verbs') === 0) {
            return $this->getVerbCases($query);
        }

        if (!preg_match('/[\w-]+/', $query)) {
            throw new \Exception('Invalid pack name');
        }

        exec(sprintf('cat data/%s 2>/dev/null', $query), $output);

        return $output;
    }

    public function getCase(int $chat_id) : array
    {
        $data = $this->getChatData($chat_id);

        $cases = $this->getCases($data['set']);

        $iteration = min($data['data']);
        $keys = array_intersect($data['data'], [$iteration]);

        $is_new_iteration = count($keys) === count($cases);

        $cases = array_intersect_key($cases, $keys);
        $key = array_rand($cases);

        $case = explode('::', $cases[$key]);
        $case = trim(end($case));

        $data['data'][$key]++;
        $this->updateChatData($chat_id, $data['data']);

        return [$key, $case, $is_new_iteration];
    }

    public function getAnswer(int $chat_id, int $answer_key, string $message_text) : array
    {
        $data = $this->getChatData($chat_id);
        $cases = $this->getCases($data['set']);

        $case = explode('::', $cases[$answer_key]);
        $answer = trim(reset($case));

        $text = $message_text . PHP_EOL . $answer;

        return [$answer_key = -1, $text, $is_new_iteration = false];
    }

    public function startSession(int $chat_id, string $set)
    {
        $cases = $this->getCases($set);

        if (!$cases) {
            throw new \Exception('Invalid pack');
        }

        $data = array_fill_keys(array_keys($cases), 0);

        $Connection = $this->getConnection();
        $Statement = $Connection->prepare('insert into chats (chat_id, data, `set`) values (:chat_id, :data, :set) on duplicate key update `set` = :set, data = :data');
        $Statement->execute(
            [
                'chat_id' => $chat_id,
                'data' => json_encode($data),
                'set' => $set,
            ]
        );
    }

    protected function getConnection() : PDO
    {
        $servername = "localhost";
        $username = "admin";
        $password = "Testtest@1";

        $conn = new PDO("mysql:host=$servername;dbname=deutsch", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }
}