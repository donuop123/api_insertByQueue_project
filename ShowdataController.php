<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShowdataController extends Controller
{
    public function index() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://train.rd6/?start=2019-11-15T03:03:00&end=2019-11-15T03:03:59&from=0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $temp = curl_exec($ch);
        $data = json_decode($temp, true);

        dd(count($data['hits']['hits']));
        // 18:05 id: WgRgaW4BwUNexJwBcHgn
        // 18:04 id: bupfaW4BRcBX7ibgd4_O
        curl_close($ch);
    }
}
