<?php
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * @author	Nachteule`
 * @license	GNU Lesser General Public License
 * @package nachteule.wcf.router
 */
class RouterListener implements EventListener {
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
		
		// get request
		$request = $_SERVER['REQUEST_URI'];
		$_ = explode($this->base, $request, 2);
		$request = $_[count($_)-1];
		
		if (StringUtil::indexOf($request, 'index.php') === 0)
			return false;
		
		// get route which suits best for the request
		if (!$route = $this->getRouteByRequest($request))
			throw new SystemException('No route');
		
		// set variables
		foreach ($route[0][1] as $isset) {
			if (!isset($route[0][2][$isset]) && isset($route[1][$isset])) {
				$_GET[$isset] = $route[1][$isset];
				$_REQUEST[$isset] = $route[1][$isset];
			}
		}
		
		// set predefined variables
		foreach ($route[0][2] as $key => $value) {
			$_GET[$key] = $value;
			$_REQUEST[$key] = $value;
		}
	}
	
	public function getRouteByRequest($request) {
		foreach ($this->routes as $data) {
			if (preg_match($data[3], $request, $match))
				return array($data, $match);
		}
		
		return false;
	}
}
