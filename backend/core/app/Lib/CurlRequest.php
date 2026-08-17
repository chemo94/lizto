<?php

namespace App\Lib;

class CurlRequest
{

    /**
    * GET request using curl
    *
    * @return mixed
    */
	public static function curlContent($url,$header = null)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    if ($header) {
	    	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    $result = curl_exec($ch);
	    $error = curl_error($ch);
	    $errno = curl_errno($ch);
	    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);

	    if ($result === false || $errno !== 0) {
	        \Illuminate\Support\Facades\Log::error('[CurlRequest::curlContent] cURL error', [
	            'url'        => $url,
	            'errno'      => $errno,
	            'error'      => $error,
	        ]);
	        return false;
	    }
	    if ($statusCode >= 400) {
	        \Illuminate\Support\Facades\Log::error('[CurlRequest::curlContent] HTTP error', [
	            'url'        => $url,
	            'statusCode' => $statusCode,
	            'response'   => substr((string)$result, 0, 500),
	        ]);
	    }

	    return $result;
	}


    /**
    * POST request using curl
    *
    * @return mixed
    */
	public static function curlPostContent($url, $postData = null,$header = null)
	{
	    if (is_array($postData)) {
	        $params = http_build_query($postData);
	    } else {
	        $params = $postData;
	    }
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    if ($header) {
	    	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    $result = curl_exec($ch);
	    $error = curl_error($ch);
	    $errno = curl_errno($ch);
	    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);

	    if ($result === false || $errno !== 0) {
	        \Illuminate\Support\Facades\Log::error('[CurlRequest::curlPostContent] cURL error', [
	            'url'        => $url,
	            'errno'      => $errno,
	            'error'      => $error,
	        ]);
	        return false;
	    }
	    if ($statusCode >= 400) {
	        \Illuminate\Support\Facades\Log::error('[CurlRequest::curlPostContent] HTTP error', [
	            'url'        => $url,
	            'statusCode' => $statusCode,
	            'response'   => substr((string)$result, 0, 500),
	        ]);
	    }

	    return $result;
	}
}
