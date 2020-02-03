<?php
namespace tegic;
class Http {
	private $post = array();
	private $set = array();
	private $set_head = array();
	private $head = '';

	public function __construct(){
	}

	public function reset(){
		$this->post= array();
		$this->head = '';
		return $this;
	}

	public function post($key,$value = false){
		if ($key === true || is_string($key) ){
			$this->post = $key;
		}else if ( $key === null && $value === false  ){
			$this->post = array();
		}else if ( $value === false ){
			foreach($key as $k=>$v){
				$this->post[$k] = $v;
			}
		}else{
			$this->post[$key] = $value;
		}
		return $this;
	}
	public function header($key,$value){
		$this->set_head[$key] = $value;
	}

	public function set($key,$value){
		if ( $key == CURLOPT_COOKIEFILE || $key == CURLOPT_COOKIEJAR ){
			$this->set[CURLOPT_COOKIEFILE] = $value;
			$this->set[CURLOPT_COOKIEJAR] = $value;
		}else{
			$this->set[$key] = $value;
		}
		return $this;
	}

	public function httpHeaderCallback($ch, $head) {
		$this->head .= $head;
		return strlen($head);
	}

	public function connect($url,$charset = false){
		$header = array();
		$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Accept-Charset: utf-8;q=0.7,*;q=0.7";

		foreach($this->set_head as $k=>$v){
			$header[] = "{$k}: {$v}";
		}

		$ch = curl_init();
		foreach($this->set as $k=>$v){
			curl_setopt($ch, $k, $v);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this,'httpHeaderCallback'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.2) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30');
		if ( $this->post ){
			curl_setopt($ch,CURLOPT_POST, true);
			if ( is_array($this->post) ){
				curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($this->post));
			}else{
				if ( $this->post === true){
					$this->post='';
				}
				curl_setopt($ch,CURLOPT_POSTFIELDS, $this->post);
			}
		}
		$html = curl_exec($ch);

		$no = curl_errno($ch);

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// 解决安全模式下的问题
		if ( $http_code == 301 || $http_code == 302 ) {
			if ( $a = strpos($this->head,'Location:') ) {
				$url = substr($this->head,$a+9);
				$url = substr($url,0,strpos($url,"\r\n"));
				$url = trim($url);
				$this->post = array();
				$this->head = '';
				return $this->connect($url,$charset);
			}
		}


		if ( $no ){
			// var_dump(curl_error($ch));
			// echo curl_getinfo($ch,CURLINFO_HTTP_CODE); 
			throw new \RuntimeException(curl_error($ch));
			return false;
		}else{
			if ( $charset ) {
				$code = @mb_detect_encoding($html, array('UTF-8','GBK','gb2312'));
				if ( $code && strtolower($charset) != strtolower($code) ) {
					$html = str_ireplace(array('UTF-8','GBK','gb2312'),$charset,$html);
					$html = @mb_convert_encoding($html, $charset, $code);
				}
			}
			return $html;
		}

		curl_close($ch);

	}

	public function getHeader(){
		return $this->head;
	}

	/* 根据字符串开始和结尾的字符串截取字符串 */
	public function substr($html, $start, $end) {
		$a = strpos($html, $start);
		if ($a === false) {
			return '';
		}
		$html = substr($html, $a + strlen($start));
		$a = strpos($html, $end);
		if ($a === false) {
			return '';
		}
		return substr($html, 0, $a);
	}

	/* 将字符串转换为UTF-8编码 */
	public function toUTF8($string,&$code=''){
		$code = mb_detect_encoding($string, array('UTF-8','GBK'));
		return @mb_convert_encoding($string, 'UTF-8', $code);
	}

	/* 将字符串转换为GB2312编码 */
	public function toGBK($string,&$code=''){
		$code = mb_detect_encoding($string, array('UTF-8','GBK'));
		return @mb_convert_encoding($string, 'GBK', $code);
	}
}