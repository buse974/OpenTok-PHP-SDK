<?php

namespace OpenTok\Util;

use \Guzzle\Http\Exception\ClientErrorResponseException;
use \Guzzle\Http\Exception\ServerErrorResponseException;
use \Guzzle\Http\Client as ClientGuzzle;

use OpenTok\Exception\Exception;
use OpenTok\Exception\DomainException;
use OpenTok\Exception\UnexpectedValueException;
use OpenTok\Exception\AuthenticationException;

use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\EntityEnclosingRequest;

/**
 * 
 * Default Client Guzzle
 *
 */
class Client implements ClientInterface
{
	/**
	 * 
	 * @var EntityEnclosingRequest
	 */
    protected $request;
    
    /**
     * 
     * @var Response
     */
    protected $reponse;

    /**
     * 
     * @var \Guzzle\Http\Client
     */
    protected $http_client;
    
    public function __construct()
    {
    	$this->http_client = new ClientGuzzle();
    }
    
    public function post($uri)
    {
    	$this->request = $this->http_client->post($uri);
    	
    	return $this;
    }
    
    public function get($uri)
    {
    	$this->request = $this->http_client->get($uri);
    	
    	return $this;
    }
    
    public function addAuth($apiKey, $apiSecret)
    {
    	$partnerAuthPlugin = new Plugin\PartnerAuth($apiKey, $apiSecret);
    	$this->http_client->addSubscriber($partnerAuthPlugin);
    	
    	return $this;
    }
    
    public function delete($uri)
    {
    	$this->request = $this->http_client->delete($uri);
    	
    	return $this;
    }
    
    public function send()
    {
    	try {
    		$this->reponse = $this->request->send();
    	} catch (\Exception $e) {
    		$this->handleException($e);
    	}
    	
    	return $this;
    }
    
    public function setUserAgent($user_agent, $includeDefault = null)
    {
    	$this->http_client->setUserAgent($user_agent, $includeDefault);
    	
    	return $this;
    }
    
    public function setBody($body,$conTentType = null)
    {
    	$this->request->setBody($body,$conTentType);
    	
    	return $this;
    }
    
    public function setHeader($header, $value)
    {
    	$this->request->setHeader($header, $value);
    	
    	return $this;
    }
    
    public function xml()
    {
    	return $this->reponse->xml();
    }
    
    public function json()
    {
    	return $this->reponse->json();
    }
    
    public function setParameterPost($fields)
    {
    	$this->request->addPostFields($fields);
    	 
    	return $this;
    }
    
    public function setParameterGet($fields)
    {
    	foreach ($fields as $key => $value) {
    		$this->request->getQuery()->set($key, $value);
    	}
    	
    	return $this;
    }
    
    public function setBaseUrl($url)
    {
    	$this->http_client->setBaseUrl($url);
    	
    	return $this;
    }
    
    public function addSubscriber($partnerAuthPlugin)
    {
    	$this->http_client->addSubscriber($partnerAuthPlugin);
    }
    
    //echo 'Uh oh! ' . $e->getMessage();
    //echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . "\n";
    //echo 'HTTP request: ' . $e->getRequest() . "\n";
    //echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . "\n";
    //echo 'HTTP response: ' . $e->getResponse() . "\n";
    
    private function handleException($e)
    {
    	// TODO: test coverage
    	if ($e instanceof ClientErrorResponseException) {
    		// will catch all 4xx errors
    		if ($e->getResponse()->getStatusCode() == 403) {
    			throw new AuthenticationException(
    					$this->apiKey,
    					$this->apiSecret,
    					null,
    					$e
    			);
    		} else {
    			throw new DomainException(
    					'The OpenTok API request failed: '. json_decode($e->getResponse()->getBody(true))->message,
    					null,
    					$e
    			);
    		}
    	} else if ($e instanceof ServerErrorResponseException) {
    		// will catch all 5xx errors
    		throw new UnexpectedValueException(
    				'The OpenTok API server responded with an error: ' . json_decode($e->getResponse()-getBody(true))->message,
    				null,
    				$e
    		);
    	} else {
    		// TODO: check if this works because Exception is an interface not a class
    		throw new Exception('An unexpected error occurred');
    	}
    }
}
