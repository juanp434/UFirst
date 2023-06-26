<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Error;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(): View{

        $file = 'http.json';
        // Get data from json file
        if (!Storage::disk('local')->exists($file)) {
            Artisan::call('app:generate-http-json-file');
        }
        if (!Storage::disk('local')->exists($file)) {
            throw new Error('there was a problem creating the http.json file, file not found', 404);
        }
        
        $data = Storage::disk('local')->get($file);
        $data = json_decode($data);
        
        //Requests per minute over the entire time span
        $totalRequests = count($data);
        $start_time = $data[0]->datetime;
        $end_time = $data[$totalRequests-1]->datetime;
        $st = Carbon::now();
        $st->day = $start_time->day;
        $st->hour = $start_time->hour;
        $st->minute = $start_time->minute;
        $st->second = $start_time->second;

        $et = Carbon::now();
        $et->day = $end_time->day;
        $et->hour = $end_time->hour;
        $et->minute = $end_time->minute;
        $et->second = $end_time->second;

        $minutes = $st->diffInMinutes($et);
        $requestsPerMinute = $totalRequests/$minutes;

        //Distribution of HTTP methods (GET, POST, HEAD,...)
        $methods = [];
        //Distribution of HTTP answer codes (200, 404, 302,...)
        $answers = [];
        //Distribution of the size of the answer of all requests with code 200 and size <1000B
        $size = 0;
        foreach($data as $item){

            if (!isset($methods[$item->request->method])) { 
                $methods[$item->request->method] = 1;
            }else{
                $methods[$item->request->method] += 1;
            }

            if (!isset($answers[$item->response_code])) { 
                $answers[$item->response_code] = 1;
            }else{
                $answers[$item->response_code] += 1;
            }

            if($item->response_code == 200 && $item->document_size < 1000){
                $size+=1;
            }
        }
        $statistics['requestsPerMinute'] = $requestsPerMinute;
        $statistics['methods'] = $methods;
        $statistics['answers'] = $answers;
        $statistics['size'] = $size;
        $statistics['totalRequests'] = $totalRequests;

        return view('welcome', ['statistics' => $statistics]);
    }
}
