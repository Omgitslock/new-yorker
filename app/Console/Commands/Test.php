<?php

namespace App\Console\Commands;


use App\Services\IssueDatesService;
use App\Services\IssueService;
use Illuminate\Console\Command;

class Test extends Command
{

    protected $signature = 'test';

    public function handle(IssueDatesService $datesService, IssueService $issueService)
    {
        $dates = $datesService->getIssueDatesForAllYears();

        //$result = $datesService->gitIssueDatesForAllYears();

        $result = [];

        foreach($dates as $date){
            $result[] = $issueService->saveIssue($date);
        }

        dd($result);
    }
}