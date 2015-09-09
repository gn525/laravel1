<?php
    
class Jobs {
    
    public static function getJobs($keyword = '', $offset = 0, $limit = 50) {
        $sortMode = Session::get('jobs_sort', '');

        if ($sortMode == 'rating') {
            $orderBy = 'ORDER BY rating DESC ';
        } elseif ($sortMode == 'date') {
            $orderBy = 'ORDER BY date_posted DESC ';
        } elseif ($sortMode == 'comments') {
            $orderBy = 'ORDER BY num_comments DESC ';
        } else {
            $orderBy = ' ';
        }

        $searchTerm = Session::get('jobs_search', '');

        $where = self::generateWhereForJobsListsQueries();

        $sql = "SELECT j.job_id,
                       j.reed_id AS job_key,
                       j.title,
                       j.location,
                       j.date_posted,
                       j.reed_url AS job_url,
                       j.min_salary,
                       j.max_salary,
                       j.salary_type,
                       e.name AS employer,
                       e.employer_id,
                       IFNULL((SELECT SUM(value) FROM job_ratings WHERE job_id = j.job_id), 0) AS rating,
                       (SELECT COUNT(job_id) FROM job_comments WHERE job_id = j.job_id) AS num_comments
                    FROM jobs_reed as j
                    LEFT JOIN employers AS e ON j.employer_id = e.employer_id
                    ".$where."
                    ".$orderBy."
                    LIMIT $limit
                    OFFSET $offset;";

        $results = DB::select($sql);
        
        return $results;
    }
    
    private static function generateWhereForJobsListsQueries($term = '') {
        if (strlen($term) > 0) {
            $searchTerm = trim(strtolower($term));
        } else {
            $searchTerm = Session::get('jobs_search', '');
        }

        $where = '';
        
        if (strlen($searchTerm) > 0) {
            $keywordIds = array();
            $jobIds = array();

            $sql = "SELECT keyword_id FROM keywords WHERE keyword LIKE '$searchTerm';";
            $kres = DB::select($sql);
            
            foreach ($kres as $row) {
                $keywordIds[] = intval($row->keyword_id);
            }
        
            $where  = "WHERE (j.title LIKE '%$searchTerm%') OR ";
            $where .= "(j.location LIKE '%$searchTerm%')";

            if (count($keywordIds) > 0) {
                if (count($keywordIds) == 1) {
                    $sql = "SELECT job_id FROM job_keywords WHERE keyword_id = ".$keywordIds[0].";";
                } elseif (count($keywordIds) > 1) {
                    $sql = "SELECT job_id FROM job_keyword WHERE keyword_id IN (".implode(',', $keywordIds).");";
                }
                
                $jres = DB::select($sql);
                
                foreach ($jres as $row) {
                    $jobIds[] = intval($row->job_id);
                }
                
                if (count($jobIds) > 0) {
                    $where .= " OR (".(count($jobIds) == 1 ? "j.job_id = ".$jobIds[0] : 'j.job_id IN ('.implode(',', $jobIds).')').")";
                }
            }
        }
        
        return $where;
    }
    
    public static function countJobs($keyword = '') {
        $where = self::generateWhereForJobsListsQueries();
        $sql = "SELECT COUNT(job_id) AS job_count FROM jobs_reed AS j ".$where.";";
        
        $result = DB::select($sql);

        return intval($result[0]->job_count);
    }
    
    public static function getJob($jobId) {
        return DB::table('jobs_reed')->where('job_id', intval($jobId))->first();
    }
    
    public static function getEmployersList() {
        $rows = DB::table('employers')->get();
        $list = array();
        
        foreach ($rows as $r) {
            $list[$r->employer_id] = $r->name;
        }
        
        return $list;
    }
    
    public static function getCommentsForJob($jobId) {
        $sql = "SELECT c.comment_id,
                       c.comment,
                       c.added_on
                    FROM job_comments AS c
                    WHERE c.job_id = $jobId
                    ORDER BY added_on DESC;";
                    
        $results = DB::select($sql);
        
        return $results;
    }
    
    public static function getCommentsTree($jobId) {
        $comments = array();

        $sql = "SELECT c.comment_id,
                       c.comment,
                       c.added_on,
                       c.parent_id,
                       CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                       IFNULL((SELECT SUM(value) FROM comment_ratings WHERE comment_id = c.comment_id), 0) AS rating
                    FROM job_comments AS c
                    LEFT JOIN users AS u ON u.id = c.user_id
                    WHERE c.job_id = $jobId AND c.parent_id = 0
                    ORDER BY rating DESC, c.added_on DESC;";

        $results = DB::select($sql);
        
        //print_r($results);

        foreach ($results as $c) {
            $c->replies = self::getCommentsForParent($c->comment_id);

            $comments[] = $c;
        }
        
        /*echo '<pre>';
        print_r($comments);
        echo '</pre>';
        die();*/
        
        return $comments;
    }
    
    public static function getCommentsForParent($commentId) {
        $sql = "SELECT c.comment_id,
                       c.comment,
                       c.added_on,
                       c.parent_id,
                       CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                       IFNULL((SELECT SUM(value) FROM comment_ratings WHERE comment_id = c.comment_id), 0) AS rating
                    FROM job_comments AS c
                    LEFT JOIN users AS u ON u.id = c.user_id
                    WHERE c.parent_id = $commentId
                    ORDER BY rating DESC, c.added_on DESC;";
        
        $results = DB::select($sql);

        if (count($results) > 0) {
            foreach ($results as $c) {
                $c->replies = self::getCommentsForParent($c->comment_id);
    
                $comments[] = $c;
            }
            
            return $comments;

        } else {
            return array();
        }
    }

    public static function getKeywordId($keyword) {
        $keyword = trim(strtolower($keyword));
        
        $keywordRow = $user = DB::table('keywords')->where('keyword', $keyword)->first();
        
        if ($keywordRow) {
            return $keywordRow->keyword_id;
        } else {
            $keywordId = DB::table('keywords')->insertGetId(array(
                'keyword' => $keyword,
                'added_on' => date('Y-m-d G:i:s')
            ));
            
            return $keywordId;
        }
    }
    
    public static function assignJobKeyword($jobId, $keywordId) {
        try {
            DB::table('job_keywords')->insert(array(
                'job_id' => intval($jobId),
                'keyword_id' => intval($keywordId)
            ));
        } catch (Exception $e) {
            
        }
    }
    
}