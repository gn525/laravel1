<div class="row">
    <div class="col-md-2 sidebar">
        <div class="form-group">
            Salary
            <div>
                <a href="">£20,000+</a>
            </div>
            <div>
                <a href="">£40,000+</a>
            </div>
            <div>
                <a href="">£60,000+</a>
            </div>
            <div>
                <a href="">£80,000+</a>
            </div>
            <div>
                <a href="">£100,000+</a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <form role="form" method="post" action="<?=url('jobs')?>">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for a job title, keyword" name="term" value="<?=$searchTerm?>">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-default" value="do-search" name="search">
                            <i class="fa fa-search"></i>
                            Search
                        </button>
                    </span>
                </div>
            </form>
        </div>
        <div>
            <div class="well well-sm" id="job-list-sort">
                Sort list by
                <select name="sort_by" onchange="submitSort(this)">
                    <option value="nosort">--</option>
                    <option value="date"<?=($sortMode == 'date' ? ' selected="selected"' : '')?>>Date</option>
                    <option value="rating"<?=($sortMode == 'rating' ? ' selected="selected"' : '')?>>Rating</option>
                    <option value="comments"<?=($sortMode == 'comments' ? ' selected="selected"' : '')?>>Comments</option>
                </select>
<? if (strlen($searchTerm) > 0): ?>
                <div class="pull-right">
                    <a href="<?=url('jobs/clear-search')?>" class="btn btn-danger btn-xs">
                        <i class="fa fa-times"></i>
                        Clear search
                    </a>
                </div>
<? endif; ?>
            </div>
        </div>
<? foreach ($jobsList as $job): ?>
        <div style="border-bottom: 1px dotted #eee;padding-bottom:10px;margin-bottom: 10px;">
            <div class="pull-left" style="background:#eee;">
                <div style="width:30px;text-align: center;">
                    <a href="javascript:voteJobUp(<?=$job->job_id?>)"><i class="fa fa-chevron-up"></i>
                </div>
                <div style="width:30px;text-align: center;font-size:11px;">
                    <span id="job_rating_<?=$job->job_id?>">
                        <?=($job->rating !== null ? $job->rating : 0)?>
                    </span>
                </div>
                <div style="width:30px;text-align: center;">
                    <a href="javascript:voteJobDown(<?=$job->job_id?>)"><i class="fa fa-chevron-down"></i>
                </div>
            </div>
            <div style="margin-left:10px;float:left;width:80%;">
                <a href="<?=url('jobs/'.urlencode(str_replace(array(' ', '(', ')', '/', ','), '', $job->title)).'-'.$job->job_id)?>">
                    <?=$job->title?>
                </a>
            </div>
            <div style="margin-left:10px;float:left;width:80%;font-size:12px;">
                <div>
                    <?=$job->employer?>
                </div>
<? if ($job->min_salary > 0 && $job->max_salary > 0): ?>
                <div>
                    Salary: £<?=$job->min_salary?> - £<?=$job->max_salary?> <?=str_replace('_', ' ', $job->salary_type)?>
                </div>
<? endif; ?>
                <div>
                    Location: <?=$job->location?>
                </div>
                <div>
                    Posted 
<? if (date('Y-m-d') == date('Y-m-d', strtotime($job->date_posted))): ?>
                    today
<? elseif (date('Y-m-d', time() - 86400) == date('Y-m-d', strtotime($job->date_posted))): ?>
                    yesterday
<? else: ?>
                    on <?=date('l, jS F Y', strtotime($job->date_posted))?>
<? endif; ?>
                </div>
                <div>
<? if (intval($job->num_comments) > 0): ?>
                    <a href="<?=url('jobs/'.urlencode(str_replace(array(' ', '(', ')', '/', ','), '', $job->title)).'-'.$job->job_id)?>#comments"><?=intval($job->num_comments)?> <?=(intval($job->num_comments) == 1 ? 'comment' : 'comments')?></a>
<? else: ?>
                    No comments
<? endif; ?>
                </div>
            </div>
            <div style="font-size: 12px;padding-top: 5px;padding-left:10px;float:left;">
            </div>
            
            
            <div style="clear:both;"></div>
        </div>
<? endforeach; ?>
        <div>
            <nav>
                <ul class="pager">
                    <li class="previous<?=($page == 1 ? ' disabled' : '')?>"><a href="<?=($page > 1 ? url('jobs/'.($page - 1)) : '#')?>"><span aria-hidden="true">&larr;</span> Newer</a></li>
                    <li><div id="page-of"><span>Page</span> <?=$page?> <span>of</span> <?=$pages?></div></li>
                    <li class="next<?=($page == $pages ? ' disabled' : '')?>"><a href="<?=($page < $pages ? url('jobs/'.($page + 1)) : '#')?>">Older <span aria-hidden="true">&rarr;</span></a></li>
                </ul>
            </nav>
        </div>
    </div>
    <div class="col-md-2 sidebar">
        right
    </div>
</div>
<script>
    function submitSort(el) {
        var mode = el.options[el.selectedIndex].value;
        
        document.location = '<?=url('jobs/sort-by')?>/' + mode;
    }

    function voteJobUp(jobId) {
        $.ajax({
            type: 'POST',
            url: '<?=url('/jobs/rate-job')?>',
            data: { job_id: jobId, value: 'up' }
        }).done(function(resp) {
            if (resp != 'error') {
                respVal = parseInt(resp, 10);
                $('#job_rating_' + jobId).html(respVal);
            }
        });
    }
    
    function voteJobDown(jobId) {
        $.ajax({
            type: 'POST',
            url: '<?=url('/jobs/rate-job')?>',
            data: { job_id: jobId, value: 'down' }
        }).done(function(resp) {
            if (resp != 'error') {
                respVal = parseInt(resp, 10);
                $('#job_rating_' + jobId).html(respVal);
            }
        });
    }
</script>