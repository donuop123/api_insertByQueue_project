<?php

namespace App\Console\Commands;

date_default_timezone_set('Asia/Taipei');

use DB;
use App\Jobs\insertqueueJob;
use Illuminate\Console\Command;

class InsertQueue extends Command
{
    protected $date;

    protected $time;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:queue {num}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert by queue.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $num = $this->argument('num');
        $date = date("Y-m-d");
        $time = date("H:i", mktime(date('H'), date('i') - 1));

        // if (!$this->date) {
        //     $this->date = $this->ask('請輸入查詢日期');
        // }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://train.rd6/?start={$date}T{$time}:00&end={$date}T{$time}:59&from={$num}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $temp = curl_exec($ch);
        $data = json_decode($temp, true);

        foreach ($data['hits']['hits'] as $k => $v) {
            // $data['hits']['hits'][$k]['_source'] = json_encode($data['hits']['hits'][$k]['_source']);
            // $data['hits']['hits'][$k]['sort'] = json_encode($data['hits']['hits'][$k]['sort']);
            $source = "";
            $sort = "";
            foreach ($v['_source'] as $sourceKey => $sourceValue) {
                $source .= "{$sourceKey} : {$sourceValue},  ";
            }
            foreach ($v['sort'] as $sortKey => $sortValue) {
                $sort .= "{$sortKey} : {$sortValue},  ";
            }
            $data['hits']['hits'][$k]['_source'] = $source;
            $data['hits']['hits'][$k]['sort'] = $sort;
        };

        // dd(count($data['hits']['hits']));
        // DB::table('api10000s')->insert($data['hits']['hits']);
        // $this->info(count($data['hits']['hits']));

        $data_length = count($data['hits']['hits']);  //  一頁總筆數
        $count = 0;
        $this->info('第' . ($num + 1) . '筆');
        // dd($data_length);

        while ($data_length > 0) {          //  當有資料時
            $data_splice = array_splice($data['hits']['hits'], 0, 5000);    //  一次dispatch幾筆
            $count += count($data_splice);  //  顯示存入筆數
            $this->info($count);
            insertqueueJob::dispatch($data_splice); //  扣掉已被切除的資料量
            $data_length = $data_length - count($data_splice);

            if ($count == 10000) {  //  當存入10000筆就換頁
                $num += 10000;
                if ($num <= 90000) {
                    $this->call('command:queue', [
                        'num' => $num
                    ]);
                }
            }
        }

        curl_close($ch);
    }
}
