<?php
namespace Core\Library;
class Http
{
    /**
     * Http::download() 下载数据
     * @param string $url
     * @return string file save path
     */
    static public function download(string $url){
        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        $str = curl_exec($curl);
        $str = str_replace('百度','Google',$str);
        curl_close($curl);
        return $str;
    }
}