<?php


namespace App\Services;


use App\Issue;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class IssueService
{

    /**
     * Урл, по которому получаем все обложки
     * %date% - маска для даты
     * Дата должна быть передана в формате YYYY_MM_DD
     *
     * Соответственно примерный урл для 21 декабря 2018 будет выглядеть как
     * https://archives.newyorker.com/rvimageserver/Conde%20Nast/New%20Yorker/2018_12_21/page0000001.jpg
     *
     * @var string
     */
    private $url = 'https://archives.newyorker.com/rvimageserver/Conde%20Nast/New%20Yorker/%date%/page0000001.jpg';

    /**
     * @var Client
     */
    private $client;

    /**
     * IssueService constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }


    /**
     * Скачиваем и сохраняем в бд
     *
     * @param Carbon $date
     *
     * @return mixed
     */
    public function saveIssue(Carbon $date)
    {
        $path = $this->downloadIssue($date);

        $issue = Issue::updateOrCreate(
            ['date' => $date],
            [
                'name'   => 'issue' . $date,
                'source' => $this->buildUrl($date),
                'path'   => $path,
            ]
        );

        return $issue;
    }

    /**
     * Скачиваем обложку за переданное число
     *
     * @param Carbon $date
     *
     * @return string
     */
    public function downloadIssue(Carbon $date)
    {
        $url = $this->buildUrl($date);

        $response = $this->client->get($url, [
            'query' => [
                'quality' => '100'
            ]
        ]);

        $path = 'issue' . $date . '.jpg';

        Storage::put($path, $response->getBody());

        return $path;
    }

    /**
     * Создаём урл для переданой даты
     *
     * @param Carbon $date
     *
     * @return mixed
     */
    private function buildUrl(Carbon $date)
    {
        //форматируем дату в необходимый для url формат
        $date = str_replace('-', '_', $date->format('Y-m-d'));

        //заменяем дату в урл на полученную
        $url = str_replace('%date%', $date, $this->url);

        return $url;
    }
}