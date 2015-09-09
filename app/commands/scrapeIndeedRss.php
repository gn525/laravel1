<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Monashee\PhpSimpleHtmlDomParser\PhpSimpleHtmlDomParser;

class scrapeIndeedRss extends Command {

	protected $name = 'scrapeIndeedRss';
	protected $description = 'Retrieve jobs using Indeed.co.uk RSS feed.';
	protected $keywords = array('it', 'technician', 'engineer', 'developer', 'programmer', 'service', 'network', 'support');
	
	protected $getOnce = 20;
	
	protected $stopOnDate = 0;
	
	protected $jobs = [];
	
	protected $tempFile = '';
	
	protected $jobsCacheDir = '';
	
	protected $geoCacheDir = '';
	
	protected $logSpace = '            ';

	public function __construct() {
		parent::__construct();
		
		// timestamp for today 00:00 am
		$this->stopOnDate = strtotime(date('Y-m-d 00:00:00'));
		$this->tempFile = storage_path().'/jobs/indeed.json';
		$this->jobsCacheDir = storage_path().'/jobs/content';
		$this->geoCacheDir = storage_path().'/geo_cache';
		
	}

	public function fire()
	{
		$this->info('Retrieving jobs using Indeed.co.uk\'s RSS feed');
		$this->info(date('H:i:s').' -> started...');
		
		if (is_file($this->tempFile) && (time() - filemtime($this->tempFile) < 3600)) {
    		$this->info($this->logSpace.'Source: cache file (updated '.date('Y-m-d G:i:s', filemtime($this->tempFile)).')');
    		$this->info('-');
    		$this->jobs = (array) json_decode(file_get_contents($this->tempFile));
		} else {
    		$this->info($this->logSpace.'Source: RSS feed');
    		$this->info('-');
    		$this->retrieveJobsFromFeed();
		}
		
		$this->info(date('H:i:s').' -> Jobs: '.count($this->jobs));
		$this->info($this->logSpace.'Starting the pages parser...');
		//print_r($this->jobs);
		
		$parser = new PhpSimpleHtmlDomParser();
		
		$noJobs = count($this->jobs);
		$cntJob = 1;
		
		foreach ($this->jobs as $jobId => $job) {

    		$this->info(date('H:i:s').' -> ['.$cntJob.'/'.$noJobs.'] -> Job ID '.$jobId);
    		
    		$jobRow = DB::table('jobs_indeed')->where('job_key', $jobId)->first();
    		
    		$cntJob++;
    		
    		if ($jobRow !== null) {
        		$this->info($this->logSpace.'Already in database, aborting...');
        		continue;
    		}
    		
    		$curl = new Curl\Curl();
            //$curl->setUserAgent('Propel.me.uk');
            $curl->get($job->link);

            if ($curl->error) {
                $this->error($curl->error_code);
            } else {
                $response = trim($curl->response);
                $html = $parser->str_get_html($response);
                
                $jobHeader = $html->find('div[id=job_header]');

                try {
                    $title = html_entity_decode(trim(strip_tags($jobHeader[0]->childNodes(0)->plaintext)));
                    $company = html_entity_decode(trim(strip_tags($jobHeader[0]->childNodes(2)->plaintext)));
                    $location = html_entity_decode(trim(strip_tags($jobHeader[0]->childNodes(3)->plaintext)));
                } catch (Exception $e) {
                    $this->error('Error: '.$e->getMessage());
                    continue;
                }

                if (strlen($company) > 0) {
                    $compRow = DB::table('employers')->where('name', $company)->first();
                    
                    if ($compRow !== null) {
                        $employerId = intval($compRow->employer_id);
                    } else {
                        $employerId = DB::table('employers')->insertGetId(
                            array('name' => $company)
                        );
                    }
                }

                $this->info($this->logSpace.'title="'.$title.'" company="'.$company.'" location="'.$location.'"');

                $geoFile = $this->geoCacheDir.'/'.md5(urlencode($location));

                if (is_file($geoFile)) {
                    $gmaps = json_decode(file_get_contents($geoFile));
                } else {
                    $geoUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($location).'&components=country:UK';
                    $req = file_get_contents($geoUrl);
                    
                    file_put_contents($geoFile, $req);
                    $gmaps = json_decode($req);
                }

                if ($gmaps->status == 'OK' && isset($gmaps->results[0])) {
                    $formattedAddress = $gmaps->results[0]->formatted_address;
                    $latitude = floatval($gmaps->results[0]->geometry->location->lat);
                    $longitude = floatval($gmaps->results[0]->geometry->location->lng);
                    
                    $this->info($this->logSpace.'lat="'.$latitude.'" lng="'.$longitude.'" addr="'.$formattedAddress.'"');
                    
                    $city = '';
                    $state = '';
                    $country = '';
                    
                    foreach ($gmaps->results[0]->address_components as $addrComp) {
                        if (in_array('locality', $addrComp->types)) {
                            $city = trim($addrComp->long_name);
                        } elseif (in_array('country', $addrComp->types)) {
                            $country = trim($addrComp->long_name);
                        } elseif (in_array('administrative_area_level_1', $addrComp->types)) {
                            $state = trim($addrComp->long_name);
                        }
                    }
                }
                
                $this->info($this->logSpace.'country="'.$country.'" state="'.$state.'" city="'.$city.'"');
                
                try {
                    $contents = $html->find('span[id=job_summary]');
                    $text = $contents[0]->outertext;
                    $text = trim($text);
                } catch (Exception $e) {
                    $this->error('Error: '.$e->getMessage());
                    continue;
                }
                
                try {
                    $row = array(
                        'job_key' => $jobId,
                        'employer_id' => $employerId,
                        'job_title' => $title,
                        'formatted_location' => $location,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'description' => $text,
                        'published' => $job->date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'job_url' => $job->link
                    );
                    
                    $jobDbId = DB::table('jobs_indeed')->insertGetId($row);
                    
                    $jobFile = $this->jobsCacheDir.'/'.$jobDbId;
                    
                    if (is_file($jobFile)) {
                        unlink($jobFile);
                    }

                    //file_put_contents($jobFile, $text);
                    
                    $keywordId = Jobs::getKeywordId($job->keyword);
                    
                    //Jobs::assignJobKeyword($jobDbId, $keywordId);

                } catch (Exception $e) {
                    $this->error('Error: '.$e->getMessage());
                    continue;
                }
            }
            
            $this->info($this->logSpace.'Done!');
            $this->info('-');
            sleep(rand(1, 3));

            //die();
		}
	}

    protected function retrieveJobsFromFeed() {
        foreach ($this->keywords as $keyword) {
    		$urlKeyword = urlencode($keyword);
    		//$urlLocation = urlencode($location);
    		
    		$url = 'http://www.indeed.co.uk/rss?sort=date&q='.$urlKeyword;
    		
    		$start = 0;
    		$continue = true;
    		$reqWithoutNewResults = 0;
    		
    		while ($continue === true) {
        		$urlFeed = $url.'&start='.$start;
        		
        		$this->info(date('H:i:s').' -> GET keyword="'.$keyword.'" start="'.$start.'"');
        		$this->info($this->logSpace.'URL: '.$urlFeed);
        		
        		$curl = new Curl\Curl();
                //$curl->setUserAgent('Propel.me.uk');
                $curl->get($urlFeed);
                
                if ($curl->error) {
                    $this->error($curl->error_code);
                } else {
                    
                    //$this->info($curl->response);
                    $feed = $curl->response;
                    $xml = new SimpleXmlElement($feed);
                    
                    $itemsCount = 0;
                    $itemsCollide = 0;
                    
                    foreach ($xml->channel->item as $item) {

                        $datetime = strtotime($item->pubDate);
                        
                        if ($datetime < $this->stopOnDate || $reqWithoutNewResults >= 4) {
                            
                            $this->info($this->logSpace.'Stopped at '.$itemsCount.' / '.count($xml->channel->item));
                            $continue = false;
                            break;

                        } else {

                            $link = (string) $item->link;
                            
                            $expLink1 = explode('=', $link);
                            $expLink2 = explode('-', $link);
                            
                            $countExpLink1 = count($expLink1);
                            $countExpLink2 = count($expLink2);
                            
                            if ($countExpLink1 > $countExpLink2) {
                                $jobId = trim($expLink1[$countExpLink1 - 1]);
                            } else {
                                $jobId = trim($expLink2[$countExpLink2 - 1]);
                            }

                            if (isset($this->jobs[$jobId])) {
                                
                                $this->error($this->logSpace.'Job collision ID="'.$jobId.'" title="'.$item->title.'"');
                                $itemsCollide++;
                                
                            } else {
                                $this->jobs[$jobId] = (object) array(
                                    'id' => $jobId,
                                    'title' => trim($item->title),
                                    'link' => $link,
                                    'date' => date('Y-m-d G:i:s', $datetime),
                                    'keyword' => $keyword
                                );
                            }

                        }
                        
                        $itemsCount++;
                    }
                    
                    $this->info($this->logSpace.'Ok. Retrieved '.$itemsCount.' jobs ('.$itemsCollide.' already existing). Last datetime="'.date('Y-m-d G:i:s', $datetime).'"');
                    $this->info('-');
                    
                    if ($itemsCount == $itemsCollide) {
                        $reqWithoutNewResults++;
                        $this->error($this->logSpace.'reqWithoutNewResults='.$reqWithoutNewResults);
                    }
                }

                $start += $this->getOnce;
                
                sleep(rand(1, 4));
    		}
		}
		
		file_put_contents($this->tempFile, json_encode($this->jobs));
    }

	protected function getArguments()
	{
		return array();
	}

    protected function getOptions()
	{
		return array();
	}

}
