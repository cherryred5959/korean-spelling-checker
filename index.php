<?php

declare(strict_types = 1);

require_once 'vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$promise = (new \GuzzleHttp\Client())->requestAsync('post', 'https://slack.com/api/' . 'rtm.connect', [
    'form_params' => [
        'token' => 'xoxb-301661248850-IUa5c349uMINly5tZtaCg3Fo',
    ]
]);

$loop->futureTick(function () use ($promise) {
    $promise->wait();
});

$deferred = new \React\Promise\Deferred();
$promise->then(function (\Psr\Http\Message\ResponseInterface $response) use ($deferred) {
    $deferred->resolve($response);
});

$deferred->promise()->then(function (\Psr\Http\Message\ResponseInterface $response) {
    $result = json_decode($response->getBody()->getContents());
    $url = null;
    foreach ($result as $idx => $value) {
        if ($idx === 'url') {
            $url = $value;
        }
    }

    Ratchet\Client\connect($url)->then(function (\Ratchet\Client\WebSocket $conn) {
        $conn->on('message', function (\Ratchet\RFC6455\Messaging\Message $msg) use ($conn) {
            $payload = (array)json_decode($msg->getPayload());
            $text = $payload['text'] ?? '';

            if (!empty($payload) && !empty($payload['channel']) && empty($payload['subtype']) && !empty($payload['type']) && $payload['type'] === 'message' && empty($payload['bot_id'])) {
                $client = new \Goutte\Client();
                $crawler = $client->request('POST', 'http://speller.cs.pusan.ac.kr/PnuWebSpeller/lib/check.asp', [
                    'text1' => $text,
                ]);

                $response = $client->getResponse();
                $messageInfo = [];

                if ($response instanceof \Symfony\Component\BrowserKit\Response) {
                    if ($response->getStatus() === 200) {
                        $crawler->filter('.tableErrCorrect')->each(function (\Symfony\Component\DomCrawler\Crawler $node) use (&$messageInfo) {
                            $key = $node->filter('.tdErrWord')->first()->text();
                            if (!empty($key) && !isset($messageInfo[$key])) {
                                array_push($messageInfo, [
                                    'errorWord' => $key,
                                    'replaceWord' => $node->filter('.tdReplace')->first()->text(),
                                    'errorNote' => $node->filter('.tdETNor')->first()->text(),
                                ]);
                            }
                        });
                    }
                }

                $attachments = [];
                if (!empty($messageInfo)) {
                    $fields = [];
                    foreach ($messageInfo as $idx => $info) {
                        array_push($fields, [
                            'title' => "{$info['errorWord']} => {$info['replaceWord']}",
                            'value' => $info['errorNote'],
                            'short' => false,
                        ]);
                    }

                    array_push($attachments, [
                        'text' => "<@{$payload['user']}> 엣헴, 엣헴! 이리오너라!!",
                        'color' => 'danger',
                        'fields' => $fields,
                    ]);
                }

                if (!empty($attachments)) {
                    $httpClient = new \GuzzleHttp\Client([
                        'headers' => [
                            'content_type' => 'application/json',
                        ]
                    ]);

                    $httpClient->post('https://slack.com/api/chat.postMessage', [
                        'form_params' => [
                            'token' => 'xoxb-301661248850-IUa5c349uMINly5tZtaCg3Fo',
                            'channel' => $payload['channel'],
                            'attachments' => json_encode($attachments, JSON_UNESCAPED_UNICODE),
                            'as_user' => true,
                            'link_names' => true,
                        ],
                    ]);
                }
            }
        });
    });
});

$loop->run();
