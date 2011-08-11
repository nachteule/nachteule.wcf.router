<?php
require_once(WCF_DIR.'lib/system/cache/CacheBuilder.class.php');
require_once(WCF_DIR.'lib/system/event/EventHandler.class.php');

/**
 * @author	Nachteule`
 * @license	GNU Lesser General Public License
 * @package nachteule.wcf.router
 */
class CacheBuilderRoutes implements CacheBuilder {
	public $packageID;
	public $routes = array();
	public $cache = array();
	
	/**
	 * @see CacheBuilder::getData()
	 */
	public function getData($cacheResource) {
		list($cache, $this->packageID) = explode('-', $cacheResource['cache']);
		
		$this->loadRoutes();
		EventHandler::fireAction($this, 'loadRoutes');
		
		$this->buildCache();
		EventHandler::fireAction($this, 'buildCache');
		
		$this->sortCache();
		EventHandler::fireAction($this, 'sortCache');
		
		return $this->cache;
	}
	
	protected function loadRoutes() {
		$sql = "SELECT *
				FROM wcf".WCF_N."_route
				WHERE packageID = ".$this->packageID;
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result))
			$this->routes[$row['route']] = $row['url'];
	}
	
	protected function buildCache() {
		foreach ($this->routes as $route => $url) {
			$requiredArguments = array();
			$requiredValues = array();
			$regexp = preg_quote($route, '#');
			
			preg_match_all('/\\\{(|\\\#)\\\$([^\}]+)\\\}/', $route, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				if (!in_array($match[2], $requiredArguments))
					$requiredArguments[] = $match[2];
				
				if ($match[1] == '')
					$regexp = str_replace($match[0], '(?P<'.$match[2].'>[^/]+)', $regexp);
				else if ($match[1] == '#')
					$regexp = str_replace($match[0], '(?P<'.$match[2].'>\d+)', $regexp);
			}
			
			$regexp = '#^'.$regexp.'#';
			
			parse_str($url, $args);
			foreach ($args as $key => $value) {
				if (!in_array($key, $requiredArguments))
					$requiredArguments[] = $key;
				
				$requiredValues[$key] = $value;
			}
			
			$this->cache[] = array(
				$route,
				$requiredArguments,
				$requiredValues,
				$regexp
			);
		}
	}
	
	protected function sortCache() {
		/*
			ORDER BY segments DESC, vars ASC
																				values count
																					variables count
																						segments count
			/users/{#$userID}/blog/1			->	page=UserBlog&entryID=1		2	1	4
			/users/foo/blog/{#$entryID}			->	page=UserBlog&userID=1		2	1	4
			/users/{#$userID}/blog/{#$entryID}	->	page=UserBlog				1	2	4
			/users/{#$userID}/blog				->	page=UserBlog				1	1	3
			/users/online						->	page=UsersOnline			1	0	2
			/users/team							->	page=Team					1	0	2
			/users/special						->	page=User&userID=1			2	0	2
			/users/{#$userID}					->	page=User					1	1	2
			/users/{$username}					->	page=User					1	1	2
			/users								->	page=MembersList			1	0	1
			/									->								0	0	0
		*/
		
		// this contains the presorted cache
		// structure: $preSorted[$segments][$variables][]
		$preSorted = array();
		foreach ($this->cache as $route => $data) {
			// counts all / in the route
			$segments = count(explode('/', $route)) - 1;
			// count all variables in the route
			$variables = count($data[1]) - count($data[2]);
			
			if (!isset($preSorted[$segments]))
				$preSorted[$segments] = array();
			
			if (!isset($preSorted[$segments][$variables]))
				$preSorted[$segments][$variables] = array();
			
			$preSorted[$segments][$variables][] = $data; 
		}
		
		// sort $preSorted[$segments] ASC
		for ($i = 0; $i < count($preSorted); $i++) ksort($preSorted[$i]);
		// sort $preSorted DESC
		krsort($preSorted);
		
		// walk over the array and make it flat/one-dimensional
		$sortedCache = array();
		for ($i = 0; $i < count($preSorted); $i++) {
			for ($j = 0; $j < count($preSorted); $j++) {
				foreach ($preSorted[$i][$j] as $$data)
					$sortedCache[] = $data;
			}
		}
		
		$this->cache = $sortedCache;
	}
}
