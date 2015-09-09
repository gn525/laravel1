<?php

class JobsController extends SiteController {

    public $jobsOnPage = 50;

    public function __construct() {
        parent::__construct();
    }
    
    public function jobsList($page = 1) {

        if (Input::has('search')) {
            $term = trim(strtolower(Input::get('term')));

            Session::put('jobs_search', $term);
            
            return Redirect::to('jobs');
        }

        $page = intval($page);
        
        parent::setPageTitle('Jobs list'.($page > 1 ? ' ('.$page.')' : ''));

        Session::put('jobs_page', $page);

        $offset = $page * $this->jobsOnPage - $this->jobsOnPage;

        $jobsList = Jobs::getJobs('', $offset, $this->jobsOnPage);
        $jobsCount = Jobs::countJobs();
        
        $pages = ceil($jobsCount / $this->jobsOnPage);
        
        $data = array(
            'jobsList' => $jobsList,
            'jobsCount' => $jobsCount,
            'pages' => $pages,
            'page' => $page,
            'sortMode' => Session::get('jobs_sort', ''),
            'searchTerm' => Session::get('jobs_search', '')
        );

        return parent::getTemplate('jobs-list', $data);
    }
    
    public function clearSearch() {
        Session::forget('jobs_search');

        return Redirect::to('jobs');
    }
    
    public function backToJobsList() {
        $page = intval(Session::get('jobs_page'));
        
        return Redirect::to('jobs/'.$page);
    }
    
    public function sortJobs($mode) {
        $mode = trim(strtolower($mode));

        if ($mode == 'nosort') {
            Session::forget('jobs_sort');
        } else {
            Session::put('jobs_sort', $mode);
        }
        
        return Redirect::to('jobs');
    }
    
    public function rateJob() {
        $job_id = intval(Input::get('job_id'));
        //$user_id = intval(Input::get('user_id'));
        $user_id = 0;
        $value = strtolower(trim(Input::get('value')));
        
        if ($value == 'up') {
            $num_value = 1;
        } elseif ($value == 'down') {
            $num_value = -1;
        }
        
        $row = array(
            'job_id' => $job_id,
            'user_id' => $user_id,
            'user_ip' => Request::getClientIp(),
            'value' => $num_value
        );
        
        try {
            DB::table('job_ratings')->insert($row);
            
            $sql = "SELECT SUM(value) AS rating FROM job_ratings WHERE job_id = $job_id;";
            
            $row = DB::select($sql)[0];
            return $row->rating;
        } catch (Exception $e) {
            return 'error';
        }
        
    }
    
    public function rateComment() {
        $comment_id = intval(Input::get('comment_id'));
        
        if (Auth::check()) {
            $user_id = intval(Auth::user()->id);
        } else {
            $user_id = 0;
        }
        
        $value = strtolower(trim(Input::get('value')));
        
        if ($value == 'up') {
            $num_value = 1;
        } elseif ($value == 'down') {
            $num_value = -1;
        }
        
        $row = array(
            'comment_id' => $comment_id,
            'user_id' => $user_id,
            'user_ip' => Request::getClientIp(),
            'value' => $num_value
        );
        
        try {

            DB::table('comment_ratings')->insert($row);
            return 'ok';

        } catch (Exception $e) {

            return 'error';

        }
        
    }
    
    public function viewJob($job) {
        $jobPieces = explode('-', $job);
        
        $jobId = intval($jobPieces[count($jobPieces) - 1]);
        
        $job = Jobs::getJob($jobId);
        $employers = Jobs::getEmployersList();
        
        if (!$job) {
            return Redirect::to('jobs');
        }

        parent::setPageTitle($job->title);
        
        $data = array(
            'job' => $job,
            'employers' => $employers,
            'url' => Request::url(),
            'comments' => Jobs::getCommentsTree($jobId),
            'captcha_error' => intval(Session::get('comment_captcha_error'))
        );

        return parent::getTemplate('job', $data);
    }
    
    public function postOnJob($job) {
        $jobPieces = explode('-', $job);
        
        $jobId = intval($jobPieces[count($jobPieces) - 1]);
        
        if (Input::has('post-comment')) {

            $parentId = intval(Input::get('parent_id'));

            if (!Auth::check()) {
                $rules =  array('captcha' => array('required', 'captcha'));
                $validator = Validator::make(Input::all(), $rules);

                if ($validator->fails()) {
                    Session::flash('comment_captcha_error', '1');
                    
                    /*if ($parentId > 0) {
                        return Redirect::to('jobs/'.$job.'#comment_'.$parentId);
                    } else {
                        return Redirect::to('jobs/'.$job.'#comments');
                    }*/

                    return Redirect::to('jobs/'.$job.'#comments');
                }
            }
            
            $comment = trim(Input::get('comment'));
            $comment = strip_tags($comment);
            $comment = nl2br($comment);
            
            $anonymous = intval(Input::get('anonymous'));
            
                        
            if ($anonymous == 1) {
                $user_id = 0;
            } else {
                $user_id = Auth::check() ? intval(Auth::user()->id) : 0;
            }
            
            if (strlen($comment) > 0) {
                try {
                    $id = DB::table('job_comments')->insertGetId(array(
                        'job_id' => $jobId,
                        'parent_id' => $parentId,
                        'user_id' => $user_id,
                        'user_ip' => Request::getClientIp(),
                        'added_on' => date('Y-m-d G:i:s'),
                        'comment' => $comment
                    ));
                    
                    return Redirect::to('jobs/'.$job.'#comment_'.$id);
                } catch (Exception $e) {
                    die($e->getMessage());
                }
                
            }
        }
        
        return Redirect::to('jobs/'.$job.'#comments');
    }
    
    public function reportComment() {
        $commentId = intval(Input::get('comment_id'));
        
        if ($commentId > 0) {
            try {
                DB::table('comment_reports')->insert(array(
                    'comment_id' => $commentId,
                    'user_id' => intval(Auth::user()->id),
                    'user_ip' => Request::getClientIp(),
                    'added_on' => date('Y-m-d G:i:s')
                ));
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
}