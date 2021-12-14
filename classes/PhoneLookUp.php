<?php

require "vendor/autoload.php";

use PHPHtmlParser\Dom;


class PhoneLookUp
{
    public $regional = false;
    const spam_msg = "Це повідомлення спам";
    const disabled_comment_1 = "comment-item   comment-disabled";
    const disabled_comment_2 = "comment-item   comment-item-ad  comment-disabled";
    const censored_tag = '/<span class="profanity" style="width:44px"><\/span>/';


    public function __construct($phone)
    {
        $this->dom = new Dom;
        $this->phone = $phone;
        $this->curl($phone, 1);
        $this->dom->loadStr($this->source);
    }

    public function curl(string $phone, int $page): ?bool
    {
        try {
            $agent = 'Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/15.0 Chrome/90.0.4430.210 Mobile Safari/537.36';
            $ch = curl_init('https://www.telefonnyjdovidnyk.com.ua/nomer/' . $phone . "/p/" . $page);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            $html = curl_exec($ch);
            curl_close($ch);
            $this->source = $html;
            return true;
        } catch (Exception $e) {
            return null;
        }
    }

    public function checkBarExist(): ?bool
    {
        try {
            $divs = $this->dom->find('div');
            for ($i = 0; $i < count($divs); $i++) {
                if ($divs[$i]->hasAttribute('class')) {
                    $value = $divs[$i]->getAttribute('class');
                    if (str_starts_with($value, 'progress-bar-rank')) $this->progess_bar_class_name = $value;
                }
            }
            return false;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getLocalAddress(): ?string
    {
        try {
            $spans = $this->dom->find('span');
            for ($i = 0; $i < count($spans); $i++) {
                if ($spans[$i]->hasAttribute('itemprop')) {
                    if ($spans[$i]->getAttribute('itemprop') == "addressLocality") {
                        $this->regional = true;
                        return $spans[$i]->innerHtml;
                    }
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }


    public function getDangerousRate(): ?int
    {
        try {
            $this->checkBarExist();
            return intval($this->dom->find('.' . $this->progess_bar_class_name)[0]->innerHtml);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getCountRate(): ?int
    {
        try {
            return $this->dom->find('#count-comments')->innerHtml;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getLastRateDate(): ?string
    {
        try {
            if ($this->regional)
                $text = $this->dom->find('.td78')[8]->innerHtml;
            else $text = $this->dom->find('.td78')[2]->innerHtml;
            $result = explode(' ', $text);
            return $result[0];
        } catch (Exception $e) {
            return null;
        }
    }

    public function getViewsCount(): ?int
    {
        try {
            if ($this->regional)
                $text = $this->dom->find('.td78')[9]->innerHtml;
            else $text = $this->dom->find('.td78')[3]->innerHtml;
            $result = preg_replace('/\D/', '', $text);
            return intval($result);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getLastDateView(): ?string
    {
        try {
            if ($this->regional)
                $text = $this->dom->find('.td78')[10]->innerHtml;
            else $text = $this->dom->find('.td78')[4]->innerHtml;
            return $text;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getCountPages(): ?int
    {
        try {
            $list = $this->dom->find('a');
            $counter = 0;
            for ($i = 0; $i < count($list); $i++) {
                if ($list[$i]->hasAttribute('data-page')) $counter++;
            }
            if ($counter == 0) $counter = 1;
            return intval($counter);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getComments(): ?array
    {
        try {
            $comments_counter = 0;
            for ($c = 0; $c < $this->getCountPages(); $c++) {
                $this->curl($this->phone, $c);
                $this->dom->loadStr($this->source);
                $divs = $this->dom->find('div');
                for ($i = 0; $i < count($divs); $i++) {
                    $id = $divs[$i]->getAttribute('id');
                    if (str_starts_with($id, 'id-comment')) {
                        $class = $this->dom->find('#' . $id)->getAttribute('class');
                        if ($class != self::disabled_comment_1 && $class != self::disabled_comment_2)
                            $comment_container = $this->dom->find('#' . $id)->innerHtml;
                        $local_dom = new Dom;
                        $local_dom->loadStr($comment_container);
                        $comments = $local_dom->find('.comment-text');
                        $rank = $local_dom->find('.rank')->innerHtml;
                        for ($j = 0; $j < count($comments); $j++) {
                            if (trim($comments[$j]->innerHtml) != strtolower(self::spam_msg)) {
                                $this->comments[$comments_counter]['rank'] = $rank;
                                $this->comments[$comments_counter]['text'] = $comments[$j]->innerHtml;
                                $this->comments[$comments_counter]['text'] = preg_replace(self::censored_tag, '[censored]', $this->comments[$comments_counter]['text']);
                            }
                        }
                        $comments_counter++;
                    }
                }
            }
            return $this->comments;
        } catch (Exception $e) {
            return null;
        }
    }

    public function lookup(): ?array
    {
        try {
            $this->result['phone'] = $this->phone;
            $this->result['local_address'] = $this->getLocalAddress();
            $this->result['dangerous_rate'] = $this->getDangerousRate();
            $this->result['comments_count'] = $this->getCountRate();
            $this->result['last_date_rate'] = $this->getLastRateDate();
            $this->result['views_count'] = $this->getViewsCount();
            $this->result['last_date_view'] = $this->getLastDateView();
            $this->result['comments'] =  $this->getComments();
            return $this->result;
        } catch (Exception $e) {
            return null;
        }
    }
}
