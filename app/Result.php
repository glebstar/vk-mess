<?php

namespace App;

use VK\Client\VKApiClient;

class Result
{
    public static function getResult($date) {
        // все диалоги
        $conversations = [];

        $vk = new VKApiClient();

        // получить все диалоги
        $offset = 0;
        while(true) {
            $response = $vk->messages()->getConversations(env('VK_ACCESS_TOKEN'), [
                'count' => 200,
                'offset' => $offset,
            ]);

            if ($response['count'] > 0) {
                $conversations = array_merge($conversations, $response['items']);
            }

            if ($response['count'] < 200) {
                break;
            }

            $offset += 200;
        }

        // получить всю историю для диалогов для нужной даты
        $history = [];
        foreach ($conversations as $conversation) {
            $offset = 0;
            while(true) {
                $response = $vk->messages()->getHistory(env('VK_ACCESS_TOKEN'), [
                    'count' => 200,
                    'offset' => $offset,
                    'peer_id' => $conversation['conversation']['peer']['id'],
                    'rev' => 1,
                ]);

                if ($response['count'] > 0) {
                    foreach ($response['items'] as $item) {
                        if (\Carbon\Carbon::createFromTimestamp($item['date'])->rawFormat('d.m.Y') == $date) {
                            if (!isset($history[$conversation['conversation']['peer']['id']])) {
                                $history[$conversation['conversation']['peer']['id']] = [];
                            }
                            $history[$conversation['conversation']['peer']['id']][] = $item;
                        }
                    }
                }

                if ($response['count'] < 200) {
                    break;
                }

                $offset += 200;
            }
        }

        $offsetTime = [];

        foreach ($history as $key=>$items) {
            $dateUser = false;
            $dateAdmin = false;
            foreach ($items as $item) {
                if (!isset($item['admin_author_id'])) {
                    // это сообщение пользователя
                    if (!$dateUser) {
                        $dateUser = $item['date'];
                    }
                } else {
                    // это сообщение администратора
                    if ($dateUser && !$dateAdmin) {
                        $dateAdmin = $item['date'];
                    }
                }

                if ($dateUser && $dateAdmin) {
                    $offsetTime[$key] = round(($dateAdmin - $dateUser) / 60, 2);
                    break;
                }
            }
        }

        $result = [
            // среднее время ответа
            'average_time' => 0,
            // наиболее часто встречающееся время ответа
            'most_often'   => 0,
            // диалоги, где время ответа более 15 минут
            'more15'       => [],
        ];

        $mostOften = [];
        foreach ($offsetTime as $key=>$item) {
            $result['average_time'] += $item;

            if (isset($mostOften[$item])) {
                $mostOften[$item]++;
            } else {
                $mostOften[$item] = 1;
            }

            if ($item > 15) {
                $result['more15'][] = $key;
            }
        }

        if (count($offsetTime) > 0) {
            $result['average_time'] = round($result['average_time'] / count($offsetTime), 2);
        } else {
            $result['average_time'] = 'Нет данных';
        }

        if (count($offsetTime) > 0) {
            $s = 0;
            foreach ($mostOften as $key => $item) {
                if ($item > $s) {
                    $result['most_often'] = $key;
                    $s = $item;
                }
            }
        } else {
            $result['most_often'] = 'Нет данных';
        }

        return $result;
    }
}
