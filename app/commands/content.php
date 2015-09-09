<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class content extends Command {


    protected $geoCacheDir = '';

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'content';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->geoCacheDir = storage_path().'/geo_cache';
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $res = DB::select('SELECT * FROM jobs_content');
        $count = count($res);
        $c = 0;
        
        foreach ($res as $row) {
            $c++;
            
            $content = addslashes(trim($row->content));
            $job_id = intval($row->job_id);
            
            DB::update("UPDATE jobs_indeed SET description = '$content' WHERE job_id = $job_id;");
            
            $this->info('['.$c.'/'.$count.'] Done '.$job_id);
        }
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			
		);
	}

}
