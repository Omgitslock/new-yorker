<?php


namespace App\Services;


use Carbon\Carbon;
use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

class IssueDatesService
{

    /**
     * Урл, по которому можно узнать данные,
     * за какие числа имеются обложки
     *
     * @var string
     */
    private $url = 'https://data.cdn.realviewdigital.com/global/content/getarchivepanel.aspx';

    /**
     * @var Client
     */
    private $client;

    /**
     * Первый год с которого начинаются выпуски
     */
    const YEAR_START = 1925;


    //todo clientInterface

    /**
     * IssueDatesService constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Забираем список дат для всех обложек
     * для всех лет
     *
     * @return array
     */
    public function getIssueDatesForAllYears()
    {
        $year = self::YEAR_START;

        $now = Carbon::now()->year;

        $result = [];

        while($year <= $now){
            $result = array_merge($result, $this->getIssueDatesForYear($year));

            $year++;
        }

        return $result;
    }


    /**
     * Забираем список дат, когда были выпущены обложки
     * в данном году
     *
     * @param $year
     *
     * @return array/Carbon[]
     */
    public function getIssueDatesForYear(int $year)
    {
        $response = $this->client->get($this->url, [
            'query' => [
                'pid'  => '1012',
                'type' => 'IssuesForYear',
                'Year' => $year
            ]
        ]);

        $body = $response->getBody();

        $result = $this->transformResponseToDates($body, $year);

        return $result;
    }

    /**
     * Очищаем наш ответ, оставляя только даты(Carbon)
     *
     * @param StreamInterface $body
     *
     * @return array
     */
    public function transformResponseToDates(StreamInterface $body, int $year)
    {
        $responseString = $body->getContents();
        //убираем лишние слова из респонса, чтобы можно было распарсить json
        $responseString = str_replace("var objResponse = new Object();objResponse.responseText = '", '', $responseString);
        $responseString = str_replace("'; Set_ArchivePanel_IssuesYears(objResponse,$year);", '', $responseString);

        $response = json_decode($responseString, true);

        if(!$response){
            return [];
        }

        //в этом поле хранятся даты
        $dates = array_column($response, 'SystemName');

        $dates = $this->castToCarbon($dates);

        return $dates;
    }

    /**
     * Даты приходят в формате yyyy_mm_dd,
     * поэтому конвертим их в карбон
     *
     * @param array $dates
     *
     * @return array
     */
    private function castToCarbon(array $dates)
    {
        $result = [];

        foreach($dates as $date){
            $result[] = Carbon::parse(str_replace('_', '-', $date));
        }

        return $result;
    }
}