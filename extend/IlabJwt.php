<?php

class IlabJwt
{
	const TYPE_RESERVED = 0;
	const TYPE_JSON = 1;
	const TYPE_SYS = 2;
	

	public static $appName = '实验空间';
	public static $issuerId = 100400;
	public static $secretKey = '16jmp2';
	public static $aesKey = 'SbYymvfZ8UjEmShxRAB0b1Dtaa0uGjDOOJa/f0Mbuo4=';
	
	// 生成 JWT
	public static function getJwt($body)
	{
		$body = json_encode($body, JSON_UNESCAPED_UNICODE);	//JSON_UNESCAPED_UNICODE 必须
		
		$header = self::packHeader();
		$body = self::encrypt($body);
		
		$base64Header = base64_encode($header);
		$base64Payload = base64_encode($body);
		$base64Signature = base64_encode(self::sign($base64Header, $base64Payload));
		
		return "{$base64Header}.{$base64Payload}.{$base64Signature}";
	}

	public static function abc(){
		echo 'abc';
	}
	
	// 生成 header
	protected static function packHeader()
	{
		$header = '';
		
		$expiry = round((microtime(true) + 900) * 1000);	//900秒过期时间
		$header .= pack('J', $expiry);
		
		$type = pack('n', self::TYPE_SYS);
		$header .= $type[1];
		
		$header .= pack('J', self::$issuerId);
		
		return $header;
	}
	
	// 加密
	protected static function encrypt($body)
	{
		$payload = '';
		
		//前接8字节随机整数
		$randLong = pack('J', rand(0, PHP_INT_MAX));
		$payload .= $randLong;
		
		$payload .= $body;
		
		//补齐为64字节的整数倍
		$tempLen = strlen($payload) + 1;
		$paddingLen = 16 - $tempLen % 16;
		$padding = str_pad('', $paddingLen + 1, pack('c', $paddingLen));
		$payload .= $padding;
		
		//aes加密
		$aesKey = base64_decode(self::$aesKey);
		$iv = substr($aesKey, 0, 16);
		
		$payload = openssl_encrypt($payload, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
		
		return $payload;
	}
	
	// 签名
	public static function sign($base64Header, $base64Payload)
	{
		$signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::$secretKey, true);
		return $signature;
	}

	// 验证签名
	public static function validateSignature($signature, $base64Header, $base64Payload)
	{
		$caculatedSignature = self::sign($base64Header, $base64Payload);

		return $caculatedSignature === $signature;
	}

	// 解码 header
	public static function getHeader($header)
	{
		$expiry = substr($header, 0, 8);
		$type = substr($header, 8, 1);
		$issuerId = substr($header, 9);

		$expiry = unpack('J', $expiry);
		$type = unpack('n', "\0" . $type);
		$issuerId = unpack('J', $issuerId);

		return ['expiry'=>$expiry[1], 'type'=>$type[1], 'issuerId'=>$issuerId[1]];
	}
	
	// 解码 payload
	public static function decrypt($payload)
	{
		$aesKey = base64_decode(self::$aesKey);
		$iv = substr($aesKey, 0, 16);
		
		$data = openssl_decrypt($payload, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);

		// 计算 padding 补位长度
		$dataLen = strlen($data);
		$paddingLen = unpack('n', "\0" . $data[$dataLen - 1]);
		$paddingLen = $paddingLen[1];
		
		// 舍弃 前8位 和 后面的补位长度
		$body = substr($data, 8, - $paddingLen - 1);
		
		return $body;
	}
	

	
}


