<?php

// src/Service/HearthstoneApiService.php
namespace App\Service;

class HearthstoneApiService
{
    public function getCard($id)
    {
        //Init du cURL
        $curl = curl_init();
        //Options du cURL
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://omgvamp-hearthstone-v1.p.mashape.com/cards/'.$id.'?locale=frFR',
            CURLOPT_HTTPHEADER => array(
                "X-Mashape-Key: 7W7yBM8CIRmshFT0hIxj953mctOFp1A1mCXjsnI3N2Twy5dDnD",
                "Accept: application/json"
            )
        ));
        //Execution de la requête
        $response = curl_exec($curl);
        if(!curl_exec($curl)){
            die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
        }
        //var_dump(json_decode($response, true));
        //Fermeture du cUrl (save de la ressource)
        curl_close($curl);
        return json_decode($response, true);
    }
}

?>