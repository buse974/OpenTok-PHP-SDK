<?php

namespace OpenTok\Util;

interface ClientInterface
{
	public function post($uri);
	public function get($uri);
	public function delete($uri);
	public function send();
	public function setUserAgent($user_agent, $includeDefault = null);
	public function setBody($body,$conTentType = null);
	public function setHeader($header, $value);
	public function xml();
	public function json();
	public function setParameterPost($fields);
	public function setParameterGet($fields);
	public function setBaseUrl($url);
	public function addAuth($apiKey, $apiSecret);
}