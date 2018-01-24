<?php
/**
 * TODO 자연어 처리, env 분리
 */
declare(strict_types = 1);

require_once 'vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

$server = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) {
    if (($request->getParsedBody()['user_name'] ?? '') !== 'slackbot') {
        $client = new \Goutte\Client();
        $crawler = $client->request('POST', 'http://speller.cs.pusan.ac.kr/PnuWebSpeller/lib/check.asp', [
            'text1' => $request->getParsedBody()['text'] ?? ''
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

        if (!empty($messageInfo)) {
            $fields = [];
            foreach ($messageInfo as $info) {
                array_push($fields, [
                    'title' => "{$info['errorWord']} => {$info['replaceWord']}",
                    'value' => $info['errorNote'],
                    'short' => false,
                ]);
            }
            $airtel = '';
            $mtudy = '';
            $message = new \Maknz\Slack\Message(new \Maknz\Slack\Client($mtudy, [
                'link_names' => true,
            ]));
            $message->attach([
                'color' => 'danger',
                'fields' => $fields,
            ])->send("@{$request->getParsedBody()['user_name']} 엣헴, 엣헴! 이리오너라!!");
        }

        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            ''
        );
    }
});

$socket = new \React\Socket\Server('0.0.0.0:80', $loop);
$server->listen($socket);

$loop->run();