<?php
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * @author	Nachteule`
 * @license	GNU Lesser General Public License
 * @package nachteule.wcf.router
 */
class RouteRewriteListener implements EventListener {
	public $buffer;
	public $base = '/';
	public $routes = array();
	
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!MODULE_ROUTER) return;
		
		// get base
		$segments = explode('/', FileUtil::addTrailingSlash(PAGE_URL), 4);
		if (!empty($segments[3])) $this->base .= $segments[3];
		
		// load routes
		WCF::getCache()->addResource(
			'routes-'.PACKAGE_ID,
			WCF_DIR.'cache/cache.routes-'.PACKAGE_ID.'.php',
			WCF_DIR.'lib/system/cache/CacheBuilderRoutes.class.php'
		);
		$this->routes = WCF::getCache()->get('routes-'.PACKAGE_ID);
		
		ob_start(array($this, 'rewrite'));
	}
	
	public function rewrite($output, $status) {
		if ($status & PHP_OUTPUT_HANDLER_START) {
			$this->buffer = preg_replace_callback('~(?<=href=")index\.php(|\?([^"]+)?)(?=")~', array($this, 'rewriteCallback'), $output);
			$this->buffer = preg_replace_callback('~(?<=action=")index\.php(|\?([^"]+)?)(?=")~', array($this, 'rewriteCallback'), $this->buffer);
		}
		
		if ($status & PHP_OUTPUT_HANDLER_END)
			return $this->buffer;
	}
	
	public function rewriteCallback($match) {
		$args = array();
		if (isset($match[2]))
			parse_str(StringUtil::decodeHTML($match[2]), $args);
		
		if ($newUrl = $this->rewriteUrl($args))
			return StringUtil::encodeHTML($newUrl);
		
		return $match[0];
	}
	
	public function rewriteUrl($args) {
		foreach ($this->routes as $data) {
			$route = $data[0];
			
			// check if required variables exists
			foreach ($data[1] as $isset)
				if (!isset($args[$isset])) continue 2;
		
			// check if required values are correct
			foreach ($data[2] as $key => $value)
				if ($args[$key] != $value) continue 2;
		
			// build new url
			foreach ($data[1] as $key) {
				$route = str_replace('{$'.$key.'}', $args[$key], $route);
				$route = str_replace('{#$'.$key.'}', $args[$key], $route);
				unset($args[$key]);
			}
			
			// append remaining arguments
			$append = '';
			foreach ($args as $key => $value)
				$append .= (empty($append) ? '?' : '&').$key.'='.$value;
			
			return $this->base.$route.$append;
		}
		
		return false;
	}
}
