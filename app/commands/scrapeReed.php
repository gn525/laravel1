<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class scrapeReed extends Command {

    protected $name = 'scrapeReed';
    protected $description = 'Command description.';

    protected $apiKey = '45c0f4c3-ea8d-459b-a507-6481f50464ff';

    protected $keywords = array('it', 'technician', 'engineer', 'developer', 'programmer', 'service', 'network', 'support');
    
    protected $getOnce = 100;

    protected $jobs = array();
    
    protected $logSpace = '            ';
    
    protected $geoCacheDir = '';

    protected $addedIds = array();

    public function __construct() {
        parent::__construct();
        
        $this->geoCacheDir = storage_path().'/geo_cache';
        
    }

    public function fire() {
        foreach ($this->keywords as $keyword) {
            $url = 'http://www.reed.co.uk/api/1.0/search?keywords='.$keyword;
            $skipStart = 0;
            
            $this->info('=======================================================================================================================');
            $this->info('Starting keyword: '.$keyword);
            $this->info('=======================================================================================================================');
            
            $goOn = true;
            
            $tempJobs = array();
            
            while ($goOn === true) {
                $urlApi = 'http://www.reed.co.uk/api/1.0/search?keywords='.urlencode($keyword).'&resultsToTake='.$this->getOnce.'&resultsToSkip='.$skipStart;
                
                $this->info(date('H:i:s').' -> GET keyword="'.$keyword.'" start="'.$skipStart.'"');
                $this->info($this->logSpace.'URL: '.$urlApi);
                
                $curl = new Curl\Curl();
                $curl->setBasicAuthentication($this->apiKey, '');
                $curl->get($urlApi);
                
                $response = json_decode($curl->response);
                
                if (!isset($response->results)) {
                    $this->error(date('H:i:s').' -> '.$curl->response);
                    sleep(3);
                    continue;
                }
                
                $jobs = $response->results;
                
                foreach ($jobs as $job) {
                    $job->keyword = $keyword;
                    $tempJobs[] = $job;
                }
                
                $this->info($this->logSpace.'JOBS: '.count($jobs));
                
                $skipStart += $this->getOnce;
                
                if ($skipStart >= 400) {
                    $goOn = false;
                }
                
                sleep(rand(2, 5));
            }
            
            $this->info(date('H:i:s').' -> Retrieved '.count($tempJobs).' jobs');
            
            foreach ($tempJobs as $job) {
                $this->jobs[] = $job;
            }
            
        }
        
        //$this->info('');
        $this->info('=======================================================================================================================');
        $this->info('Starting jobs parser');
        $this->info('=======================================================================================================================');

        $cntJob = 1;
        $noJobs = count($this->jobs);

        foreach ($this->jobs as $job) {
            $jobId = $job->jobId;
            $jobUrl = $job->jobUrl;
            $keyword = $job->keyword;

            //print_r($job);

            $scrapeUrl = 'http://www.reed.co.uk/api/1.0/jobs/'.$jobId;

            $this->info(date('H:i:s').' -> ['.$cntJob.'/'.$noJobs.'] -> Job ID '.$jobId);

            $jobRow = DB::table('jobs_reed')->where('reed_id', $jobId)->first();

            $cntJob++;

            if ($jobRow !== null) {

                $this->info($this->logSpace.'Already in database, aborting...');
                continue;
            }

            $this->info($this->logSpace.'Job URL: '.$jobUrl);
            
            $curl = new Curl\Curl();
            $curl->setBasicAuthentication($this->apiKey, '');
            $curl->get($scrapeUrl);

            $jobData = json_decode($curl->response);
            
            //print_r($jobData);
            try {
                $title = trim($jobData->jobTitle);
                $location = property_exists($jobData, 'locationName') ? $jobData->locationName : 'UK';
                $minSalary = intval($jobData->minimumSalary);
                $maxSalary = intval($jobData->maximumSalary) > 0 ? intval($jobData->maximumSalary) : $minSalary;
                
                $employerName = trim($jobData->employerName);
                $employerId = intval($jobData->employerId);
                
                $description = trim($jobData->jobDescription);
                $currency = trim(strtoupper($jobData->currency));
                
                $datePosted = date('Y-m-d', strtotime(str_replace('/', '-', $jobData->datePosted)));
                $dateExpire = date('Y-m-d', strtotime($jobData->expirationDate));
                $dateScrapped = date('Y-m-d');
                
                $jobType = intval($jobData->fullTime) == 1 ? 'full_time' : 'part_time';
                $salaryType = $this->prepareForDb($jobData->salaryType);
                $contractType = $this->prepareForDb($jobData->contractType);
                
                $reedUrl = $jobData->jobUrl;
                $externalUrl = $jobData->externalUrl;
            } catch (Exception $e) {
                $this->error($e->getMessage());
                print_r($job);
                print_r($jobData);
                sleep(5);
                continue;
            }

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
                
                if (strlen($formattedAddress) > 0) {
                    $location = $formattedAddress;
                }
                
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
            
            $compRow = DB::table('employers')->where('name', $employerName)->orWhere('reference_reed', $employerId)->first();
            
            if ($compRow !== null) {
                $employerDbId = intval($compRow->employer_id);
            } else {
                $employerDbId = DB::table('employers')->insertGetId(array(
                    'name' => $employerName,
                    'reference_reed' => $employerId
                ));
            }
            
            $this->info($this->logSpace.'title="'.$title.'" job_type="'.$jobType.'" date_posted="'.$datePosted.'" date_expiration="'.$dateExpire.'"');
            
            $id = DB::table('jobs_reed')->insertGetId(array(
                'reed_id' => $jobId,
                'employer_id' => $employerDbId,
                'job_type' => $jobType,
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => $city,
                'country' => $country,
                'state' => $state,
                'min_salary' => $minSalary,
                'max_salary' => $maxSalary,
                'salary_currency' => $currency,
                'salary_type' => $salaryType,
                'contract_type' => $contractType,
                'reed_url' => $reedUrl,
                'external_url' => $externalUrl,
                'date_posted' => $datePosted,
                'date_expiration' => $dateExpire,
                'date_scrapped' => $dateScrapped
            ));
            
            $this->addedIds[] = $id;
            
            //$keywordId = Jobs::getKeywordId($keyword);
            //Jobs::assignJobKeyword($id, $keywordId);
            
            sleep(rand(1, 2));
            
        }
        
        $this->info(date('H:i:s').' -> Scrape finished. Added '.count($this->addedIds).' jobs this run.');
        
    }

    protected function prepareForDb($text) {
        $text = strtolower($text);
        $text = str_replace(' ', '_', $text);
        
        return $text;
    }

    protected function getArguments() {
        return array();
    }

    protected function getOptions() {
        return array();
    }

}
