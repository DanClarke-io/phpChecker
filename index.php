<?php
	function colourise($text, $status) { //because, English.
		$out = "";
		switch($status) {
			case "success":
				$out = "[32m"; //Green
				break;
			case "fail":
				$out = "[31m"; //Red
				break;
			case "warning":
				$out = "[33m"; //Yellow
				break;
			case "note":
				$out = "[36m"; //Blue
				break;
			default:
				throw new Exception("Invalid status: " . $status);
		}
		return chr(27) . "$out" . "$text" . chr(27) . "[0m";
	}
	function get_headers_curl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,            $url);
		curl_setopt($ch, CURLOPT_HEADER,         true);
		curl_setopt($ch, CURLOPT_NOBODY,         true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT,        15);
		$r = curl_exec($ch);
		$r = explode("\n", $r);
		$outs = array();
		foreach($r as $out) { $outs[] = trim($out); }
		return $outs;
	}

	function checkURLs($base,$url,$ref) {
		global $checked;
		global $x;
		global $out;
		$response = get_headers_curl($url);
		$checked[] = $url;
		if($response[0]=='HTTP/1.1 200 OK' || $response[0]=='HTTP/1.0 200 OK') {
			$out['passed'][] = $url;
			$file = file_get_contents($url);
			if(substr(parse_url($url,PHP_URL_PATH),-2)!='js') { // jquery.js has links to /a by using the below syntax, this stops it from happening.
				$hrefs1 = explode('href="',$file);  array_shift($hrefs1);
				$hrefs2 = explode("href='",$file); array_shift($hrefs2);
				$hrefs3 = explode('src="',$file); array_shift($hrefs3);
				$hrefs4 = explode("src='",$file); array_shift($hrefs4);
				$hrefs = array_merge($hrefs1,$hrefs2,$hrefs3,$hrefs4);
				foreach($hrefs as $href) {
					if(strpos($href, '"')) { $end = strpos($href, '"'); }
					if(strpos($href, "'")) { $end = (strpos($href, "'")<$end?strpos($href, "'"):$end); }
					$newURL = substr($href,0,$end);
					if(empty($newURL)) { $newURL = $end1.' '.$end2.' '.substr($href,0,10); }
					if(substr($newURL,0,1)=='/' && substr($newURL,0,2)!='//') { $newURL = $base.$newURL; }
					if(substr($newURL,0,1)!='#' && substr($newURL,0,2)!='//') { $urls[] = $newURL; }
				}
			}
			if(isset($urls)) {
				foreach($urls as $ourURL) {
					if(substr($ourURL,0,strlen($base))==$base && !in_array($ourURL, $checked)) {
						$x++;
						if ($x % 20 == 0) { echo $x."\n"; }
						checkURLs($base,$ourURL,$url);
					}
				}
			}
		}
		elseif($response[0]=='HTTP/1.1 301 Moved Permanently' || $response[0]=='HTTP/1.0 301 Moved Permanently') {
			$out['redirected'][] = $url;
		}
		else {
			echo colourise('Didn\'t get 200: '.$response[0].' for '.$url.' from '.$ref."\n",'fail');
			$out['failed'][] = colourise('Didn\'t get 200: '.$response[0].' for '.$url.' from '.$ref."\n",'fail');
		}
	}
	$checked = array();
	$out = array();
	$x = 0;
	checkURLs('baseURL','startURL','none');
	if(isset($out['passed'])) { echo 'Found '.count($out['passed']).' correct URLs.'."\n"; }
	if(isset($out['redirected'])) { echo 'Found '.count($out['redirected']).' redirected URLs.'."\n"; }
	if(isset($out['failed'])) { echo 'Found '.count($out['failed']).' failed URLs:'."\n"; }
	if(isset($out['failed'])) { print_r($out['failed']); }
?>
