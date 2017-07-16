<?php
/**
 * Inline Games - Telegram Bot (@inlinegamesbot)
 *
 * (c) 2017 Jack'lul <jacklulcat@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Request;

/**
 * Class InlinequeryCommand
 *
 * @package Longman\TelegramBot\Commands\SystemCommands
 */
class InlinequeryCommand extends SystemCommand
{
    public function execute()
    {
        $articles = [];

        foreach ($this->getGamesList() as $game) {
            if (class_exists($game_class = $game['class'])) {
                $articles[] = [
                    'id' => $game_class::getCode(),
                    'title' => $game_class::getTitle() . (method_exists($game_class, 'getTitleExtra') ? ' ' . $game_class::getTitleExtra() : ''),
                    'description' => $game_class::getDescription(),
                    'input_message_content' => new InputTextMessageContent(
                        [
                            'message_text' => '<b>' . $game_class::getTitle() . '</b>' . PHP_EOL . PHP_EOL . '<i>' . __('This game session is empty.') . '</i>',
                            'parse_mode' => 'HTML',
                            'disable_web_page_preview' => true,
                        ]
                    ),
                    'reply_markup' => $this->createInlineKeyboard($game_class::getCode()),
                    'thumb_url' => $game_class::getImage(),
                ];
            }
        }

        $array_article = [];
        foreach ($articles as $article) {
            $array_article[] = new InlineQueryResultArticle($article);
        }

        return Request::answerInlineQuery(
            [
            'inline_query_id' => $this->getUpdate()->getInlineQuery()->getId(),
            'cache_time' => 300,
            'results' => '[' . implode(',', $array_article) . ']',
            ]
        );
    }

    /**
     * Get list of Games (classes)
     *
     * @return array
     */
    private function getGamesList()
    {
        $games = [];
        if (is_dir(SRC_PATH . '/Entity/Game')) {
            foreach (new \DirectoryIterator(SRC_PATH . '/Entity/Game') as $file) {
                if (!$file->isDir() && !$file->isDot()) {
                    $game_class = '\Bot\Entity\Game\\' . basename($file->getFilename(), '.php');

                    $games[] = [
                        'class' => $game_class,
                        'order' => $game_class::getOrder()
                      ];
                }
            }
        }

        usort(
            $games,
            function ($item1, $item2) {
                return $item1['order'] <=> $item2['order'];
            }
        );

        return $games;
    }

    /**
     * Create inline keyboard with prefixed game code
     *
     * @param $game_code
     *
     * @return InlineKeyboard
     */
    private function createInlineKeyboard($game_code)
    {
        $inline_keyboard = [
            [
                new InlineKeyboardButton(
                    [
                    'text' => __('Create'),
                    'callback_data' => $game_code . ';new'
                    ]
                )
            ]
        ];

        $inline_keyboard_markup = new InlineKeyboard(...$inline_keyboard);

        return $inline_keyboard_markup;
    }
}