<?php

namespace App\Console\Commands;

date_default_timezone_set('Asia/Taipei');

use \Curl\Curl;
use DB;
use App\Jobs\insertqueueJob;
use Illuminate\Console\Command;

class InsertQueueAsk extends Command
{
    protected $num = 0;

    protected $date;

    protected $time;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:queueAsk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // $this->date = date("Y-m-d");
        // $this->time = date("H:i", mktime(date('H'), date('i') - 1));

        if (!$this->date && !$this->time) {
            $this->date = $this->ask('請輸入查詢日期');
            if(!preg_match("/^20[0-9]{2}\-[0-9]{2}-[0-9]{2}/", $this->date)) {
                echo "日期格式錯誤,請重新輸入 \n";
                $this->date = $this->ask('請輸入查詢日期');
            }

            $this->time = $this->ask('請輸入查詢時間');
            if(!preg_match("/^[0-9]{2}\:[0-9]{2}/", $this->time)) {
                echo "時間格式錯誤,請重新輸入 \n";
                $this->time = $this->ask('請輸入查詢時間');
            }
        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->get("http://train.rd6/?start={$this->date}T{$this->time}:00&end={$this->date}T{$this->time}:59&from={$this->num}");

        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        } else {
            echo 'Response:' . "\n";
            echo "第" . ($this->num + 1) . "筆\n";
            // var_dump($curl->response);
        }

        $data = json_decode($curl->response, true);

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

        $dataChunks = array_chunk($data['hits']['hits'], 1000, true);
        $count = 0;
        foreach ($dataChunks as $value) {
            $count += count($value);
            insertqueueJob::dispatch($value);
            echo $count . "\n";
        }
        $this->num += 10000;
        if ($this->num <= 90000) {
            $this->call('command:queueAsk');
        }


        // if($count == 10000) {
        //     $count = 0;
        //     $this->num += 10000;
        // }

        // dd(count($data['hits']['hits']));
        // DB::table('api10000s')->insert($data['hits']['hits']);
        // $this->info(count($data['hits']['hits']));

        // $data_length = count($data['hits']['hits']);  //  一頁總筆數
        // $count = 0;
        // $this->info('第' . ($num + 1) . '筆');
        // // dd($data_length);

        // while ($data_length > 0) {          //  當有資料時
        //     $data_splice = array_splice($data['hits']['hits'], 0, 5000);    //  一次dispatch幾筆
        //     $count += count($data_splice);  //  顯示存入筆數
        //     $this->info($count);
        //     insertqueueJob::dispatch($data_splice); //  扣掉已被切除的資料量
        //     $data_length = $data_length - count($data_splice);

        //     if ($count == 10000) {  //  當存入10000筆就換頁
        //         $num += 10000;
        //         if ($num <= 90000) {
        //             $this->call('command:queue', [
        //                 'num' => $num
        //             ]);
        //         }
        //     }
        // }

        // curl_close($ch);
        $curl->close();
    }
}
