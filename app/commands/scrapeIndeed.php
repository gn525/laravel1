<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class scrapeIndeed extends Command {

	protected $name = 'scrapeIndeed';
	protected $description = 'Command description.';

    //protected $publisher = '9732299398004143';
    protected $publisher = '5573670061574273';
    protected $version = 2;
    protected $useragent = '';
    protected $userip = '';
    protected $keyword = 'IT';
    protected $country = 'UK';
    
    protected $scrapeOnce = 10;
    
    protected $results = [];

	public function __construct()
	{
		parent::__construct();
		
		$ug = array(
    		urlencode('Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0'),
    		urlencode('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.67 Safari/537.36'),
    		urlencode('Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.67 Safari/537.36'),
    		urlencode('Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14'),
    		urlencode('Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)'),
    		urlencode('Mozilla/5.0 (compatible; MSIE 10.0; Macintosh; Intel Mac OS X 10_7_3; Trident/6.0)'),
    		urldecode('Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))'),
    		urlencode('Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; Zune 4.0; InfoPath.3; MS-RTC LM 8; .NET4.0C; .NET4.0E)')
		);
		
		$this->useragent = $ug[rand(0, count($ug) - 1)];
		
		$this->userip = urlencode(rand(10, 200).'.'.rand(30, 150).'.'.rand(99, 250).'.'.rand(1, 254));
	}

	public function fire()
	{
    	//$this->error('Something went wrong!');
		$this->info('Starting the indeed.co.uk scraper');
		
		$url = 'http://api.indeed.com/ads/apisearch?publisher='.$this->publisher.'&q='.$this->keyword.'&co='.$this->country.'&radius=10&start=0&limit=10&fromage=1&format=json&filter=1&latlong=1&userip='.$this->userip.'&useragent='.$this->useragent.'&v='.$this->version;
		
		$this->info('URL: '.$url);
		
		$resp = json_decode(file_get_contents($url));
		
		$totalResults = intval($resp->totalResults);
		$pages = ceil($totalResults / $this->scrapeOnce);
		
		$this->info('Jobs to scrape: '.$totalResults);
		$this->info('Total pages: '.$pages);
		
		//foreach ($pages as $p) {
    	//	
		//}
		
		$this->parseResults($resp);
		
		for ($i = 2; $i <= $pages; $i++) {
    		$this->retrievePage($i);
    		
    		//$sleep = rand(2, 5);
    		//$this->info('Sleeping: '.$sleep.' sec.');
    		//sleep($sleep);
		}
		
	}
	
	protected function retrievePage($pageNo) {
    	
    	$this->info('Page: '.$pageNo);
    	
    	$start = ($pageNo * $this->scrapeOnce) - $this->scrapeOnce;
    	
    	$url = 'http://api.indeed.com/ads/apisearch?publisher='.$this->publisher.'&q='.$this->keyword.'&co='.$this->country.'&radius=10&start='.$start.'&limit=10&fromage=1&format=json&filter=1&latlong=1&userip='.$this->userip.'&useragent='.$this->useragent.'&v='.$this->version;
    	
    	$resp = json_decode(file_get_contents($url));
    	
    	$this->parseResults($resp);
	}
	
	protected function parseResults($resp) {
    	if (is_object($resp)) {
        	if (isset($resp->results) && count($resp->results) > 0) {
            	foreach ($resp->results as $r) {                	
                	try {
                		DB::table('jobs_indeed')->insert(
                            array(
                                'jobkey' => $r->jobkey,
                                'jobtitle' => trim($r->jobtitle),
                                'company' => trim($r->company),
                                'city' => trim($r->city),
                                'state' => trim($r->state),
                                'country' => trim($r->country),
                                'formatted_location' => trim($r->formattedLocation),
                                'source' => trim($r->source),
                                'date' => date('Y-m-d G:i:s', strtotime($r->date)),
                                'snippet' => trim($r->snippet),
                                'url' => trim($r->url),
                                'latitude' => isset($r->latitude) ? floatval($r->latitude) : 0.0,
                                'longitude' => isset($r->longitude) ? floatval($r->longitude) : 0.0
                            )
                        );
        
            		} catch (Exception $e) {
                		$this->error('Could not insert job: '.$r->jobkey);
                		$this->error($e->getMessage());
                		print_r($r);
            		}
            	}
        	}
    	}
	}

	protected function getArguments()
	{
		return [];
	}

	protected function getOptions()
	{
		return [];
	}

}
