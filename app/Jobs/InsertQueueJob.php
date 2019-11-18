<?php

namespace App\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InsertQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $api_data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($apiData)
    {
        $this->api_data = $apiData;
        // dd($this->api_data);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $curl = new Curl();
        $curl->get($this->api_data);

        if ($curl->error) {
            echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        } else {
            echo 'Response:' . "\n";
            // var_dump($curl->response);   //  陣列 下面是 物件
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
            DB::table('api10000s')->insert($value);
            // $count += count($value);
            // insertqueueJob::dispatch($value);
            // echo $count . "\n";
        }

        $curl->close();
        // DB::table('api10000s')->insert($this->api_data);
    }
}
