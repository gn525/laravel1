<div class="row">
    <div class="col-md-12">
        <div class="well well-sm">
            <a href="<?=url('/jobs/back-to-list')?>" class="btn btn-default btn-sm">
                <i class="fa fa-chevron-left"></i>
                Back to list
            </a>
            <div class="pull-right">
                <button type="button" class="btn btn-warning btn-sm">
                    <i class="fa fa-floppy-o"></i>
                    Save job
                </button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <h1>
            <?=$job->title?>
        </h1>
        <p>
            In <?=$job->location?>
        </p>
    </div>
</div>
<div class="row">
    <div class="col-md-9">
        <p>
            <?=$job->description?>
        </p>
    </div>
    <div class="col-md-3">
        <div class="well well-sm">
            <p>
                <a class="btn btn-primary btn-block" href="<?=$job->reed_url?>" target="_blank">
                    View job
                </a>
            </p>
            <p>
                <small>
                    Published by
                </small>
            </p>
            <p>
                <?=$employers[$job->employer_id]?>
            </p>
            <p>
                <small>
                    On
                </small>
            </p>
            <p>
                <?=date('l, jS F Y', strtotime($job->date_posted))?>
            </p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <hr />
    </div>
<? if ($captcha_error == 1): ?>
    <div class="col-md-12">
        <div class="alert alert-danger" role="alert">
            Missing or invalid captcha code.
        </div>
    </div>
<? endif; ?>
    <div class="col-md-12">
        <form role="form" method="post" action="<?=$url?>">
            <div class="form-group">
                <textarea name="comment" placeholder="Enter your comment on this job" class="form-control" rows="5"></textarea>
            </div>
<? if (!Auth::check()): ?>
            <div class="form-group">
                <img src="<?=Captcha::img()?>" alt="captcha">
                <div class="captcha_text">
                    Enter the code from image:<br />
                    <input type="text" name="captcha" length="6">
                </div>
            </div>
<? else: ?>
            <div class="form-group">
                <input type="checkbox" value="1" name="anonymous" id="post_anonymous">
                <label for="post_anonymous">Anonymous comment</label>
            </div>
<? endif; ?>
            <div class="form-group">
                <button type="submit" name="post-comment" class="btn btn-primary" value="post-comment">
                    Post comment
                </button>
            </div>
        </form>
    </div>
<? if (count($comments) > 0): ?>
    <a name="comments"></a>
    <div class="col-md-12">
        <?=print_comments($comments)?>
    </div>
<? endif; ?>
</div>
<script>
    function showReplyForm(comment_id) {
        var holder = $('#reply_' + comment_id);
        
        var html = '';
        html += '<form method="post" action="<?=$url?>" class="comment_form">';
            html += '<div><textarea name="comment" rows="5"></textarea></div>';
<? if (Auth::check()): ?>
            html += '<div>';
                html += '<input type="checkbox" value="1" name="anonymous" id="anonymous">';
                html += '<label for="anonymous">Anonymous comment</label>';
            html += '</div>';
<? endif; ?>
<? if (!Auth::check()): ?>
            html += '<div class="comment_captcha">';
                html += '<img src="<?=Captcha::img()?>" alt="captcha">';
                html += '<div class="comment_captcha_text">';
                    html += 'Enter the code from image:<br />';
                    html += '<input type="text" name="captcha" length="6">';
                html += '</div>';
            html += '</div>';
<? endif; ?>
            html += '<div>';
                html += '<input type="submit" value="Post comment" name="post-comment">';
                html += '<input type="button" value="Cancel" onclick="cancelReplyForm(' + comment_id + ')">';
            html += '</div>';
            html += '<input type="hidden" name="parent_id" value="' + comment_id + '">';
        html += '</form>';
        
        holder.empty().append(html).slideDown('slow');
    }
    
    function cancelReplyForm(comment_id) {
        var holder = $('#reply_' + comment_id);
        
        holder.slideUp('fast', function() {
            $(this).empty();
        });
    }
    
    function reportComment(comment_id) {
        $.ajax({
            type: 'POST',
            url: '<?=url('/jobs/report-comment')?>',
            data: { comment_id: comment_id }
        }).done(function(resp) {
            //console.log('done', resp);
        });
    }
    
    function voteCommentUp(comment_id) {
        $.ajax({
            type: 'POST',
            url: '<?=url('/jobs/rate-comment')?>',
            data: { comment_id: comment_id, value: 'up' }
        }).done(function(resp) {
            if (resp != 'error') {
            }
        });
    }
    
    function voteCommentDown(comment_id) {
        $.ajax({
            type: 'POST',
            url: '<?=url('/jobs/rate-comment')?>',
            data: { comment_id: comment_id, value: 'down' }
        }).done(function(resp) {
            if (resp != 'error') {
            }
        });
    }

</script>
<?php

    function print_comments($comments) {
        $html = '';

        if (is_array($comments)) {
            foreach ($comments as $c) {
                if (intval($c->parent_id) == 0) {
                    $extraClass = 'no_border';
                } else {
                    $extraClass = '';
                }

                if (strlen($c->user_name) > 0) {
                    $user_name = trim($c->user_name);
                } else {
                    $user_name = 'anonymous';
                }

                $html .= '<div class="comment'.(strlen($extraClass) > 0 ? ' '.$extraClass : '').'">';
                $html .= '<a name="comment_'.$c->comment_id.'"></a>';
                
                // rating
                $html .= '<div class="pull-left">';
                    $html .= '<div style="width:30px;text-align: center;">';
                    $html .= '<a href="javascript:voteCommentUp('.$c->comment_id.')"><i class="fa fa-chevron-up"></i></a>';
                    $html .= '</div>';
                    
                    $html .= '<div style="width:30px;text-align: center;">';
                    $html .= '<a href="javascript:voteCommentDown('.$c->comment_id.')"><i class="fa fa-chevron-down"></i></a>';
                    $html .= '</div>';
                $html .= '</div>';
                // end rating
                
                
                $html .= '<div class="comment_date">';
                    $html .= '<strong>'.$user_name.'</strong> on '.date('l, jS F Y H:i', strtotime($c->added_on));
                $html .= '</div>';
                $html .= '<br>';
                                
                $html .= '<p class="comment_text">'.$c->comment;
                $html .= '<br />';
                $html .= '<span class="comment_controls">';
                    $html .= '<a href="javascript:showReplyForm('.$c->comment_id.')">reply</a>';
                    $html .= '<a href="javascript:reportComment('.$c->comment_id.')">report</a>';
                $html .= '</span>';
                
                $html .= '</p>';
                
                $html .= '<div class="comment_reply" id="reply_'.$c->comment_id.'"></div>';
                
                if (count($c->replies) > 0) {
                    $html .= print_comments($c->replies);
                }
                
                $html .= '<div style="clear:both;"></div>';
                $html .= '</div>';
            }
        }
        
        return $html;
    }

?>