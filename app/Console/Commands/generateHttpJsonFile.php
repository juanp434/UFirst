<?php

namespace App\Console\Commands;

use Error;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use stdClass;

class generateHttpJsonFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-http-json-file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command is used to generete a Json file formated from the epa-http.txt file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = 'epa-http.txt';
        //check file exists
        if (!Storage::disk('local')->exists($file)) {
            throw new Error('File does not exist', 404);
        }
        
        $contents = Storage::disk('local')->get($file);
        $arrContent = explode("\n",$contents);
        $json = [];
        try {
            foreach($arrContent as $item){
                $arrItem = explode(" ",$item);
                if(count($arrItem) == 7){
                    $obj = $this->parseData($item, true);
                    $json[] = $obj;
                }elseif(count($arrItem) > 1){
                    $obj = $this->parseData($item, false);
                    $json[] = $obj;
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        
        Storage::disk('local')->put('http.json', json_encode($json) );
    }


    function parseData($item, $normal){
        try {

            if($normal){
                $arrItem = explode(" ",$item);
                $datetime = str_replace(array( '[', ']' ), '',$arrItem[1]);
                $datetime = explode(":",$datetime);
                
                $host = $arrItem[0]; //HOST
    
                $method = substr($arrItem[2], 1);
                $url = $arrItem[3];
                
                $protocol = explode("/",$arrItem[4])[0];
                $protocol_version = substr( explode("/",$arrItem[4])[1], 0, -1);
                
                $response_code = $arrItem[5]; // RESPONSE CODE
                $document_size = $arrItem[6]; // DOCUMENT SIZE

            }else{
                $arrItem = explode('"',$item);
                
                $arrItem[0] = substr($arrItem[0], 0, -1);
                $arrItem[2] = substr($arrItem[2], 1);
        
                $host = explode(' ',$arrItem[0])[0]; // HOST
                $datetime = str_replace(array( '[', ']' ), '',explode(' ',$arrItem[0])[1]);
                $datetime = explode(":",$datetime); //DATETIME
                
                $response_code = explode(' ',$arrItem[2])[0]; //RESPONSE CODE
                
                $document_size = explode(' ',$arrItem[2])[1]; //DOCUMENT_SIZE
        
                //REQUEST
                switch ($response_code) {
                    case 400:
                        $method = '';
                        $url = trim($arrItem[1]);
                        $protocol = '-';
                        $protocol_version = '-';
                        break;
                    default:
                        $method = explode(' ', $arrItem[1])[0];
                        $url = trim( explode(' ', $arrItem[1])[1] );
                        $protocol = 'HTTP';
                        $protocol_version = '1.0';
                        break;
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }

        $obj = new stdClass();
        $obj->host = $host; //HOST

        $obj->datetime = new stdClass(); //DATETIME
        $obj->datetime->day = $datetime[0];
        $obj->datetime->hour = $datetime[1];
        $obj->datetime->minute = $datetime[2];
        $obj->datetime->second = $datetime[3];

        $obj->request = new stdClass(); // REQUEST
        $obj->request->method = $method;
        $obj->request->url = $url;
        $obj->request->protocol = $protocol;
        $obj->request->protocol_version = $protocol_version;

        $obj->response_code = $response_code; // RESPONSE CODE
        $obj->document_size = $document_size; // DOCUMENT SIZE

        return $obj;
    }
}
