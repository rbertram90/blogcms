<?php
/**
	Formatting for tag list
**/
function printTags($psTagList, $pBlogID) {
	// Seperate CSV into Array
	$res = "";
	$lsTags = explode(",", $psTagList);
	foreach($lsTags as $lsTag):
		$lsTag = trim($lsTag);
        $lsTag = str_replace("+"," ",$lsTag);
		$res.= "<a href='".CLIENT_ROOT_BLOGCMS."/blogs/$pBlogID/tags/$lsTag' class='tag'>$lsTag</a> ";
	endforeach;
	return $res;
}

function showAjaxPagination($pages, $pagenum) {
    $res = '<div class="pagination">';
    
    // Don't show back link if current page is first page.
    if ($pagenum != 1) {
        $res .= '<a href="#" onclick="refreshData(\''.($pagenum-1).'\'); return false;">&lt;</a>';
    }
    // loop through each page and give link to it.
    for ($i = 1; $i <= $pages; $i++) {
        if ($pagenum == $i) $res .= '<a><b>'.$i.'</b></a>';
        else $res .= '<a href="#" onclick="refreshData(\''.$i.'\'); return false;">'.$i.'</a>';
    }
    // If last page don't give next link.
    if ( $pagenum < $pages ) {
        $res .= '<a href="#" onclick="refreshData(\''.($pagenum+1).'\'); return false;">&gt;</a>';
    }
    $res .= '</div>';

    return $res;
}

// require_once 'ajax_setup.inc.php';

/**************************************************************************
    Blog CMS - Manage Posts Ajax Post Fetcher
    Called by ajax to get posts in format required
    
***************************************************************************
    Inputs
**************************************************************************/
    
    // get input
    $b = isset($_GET['b']) ? sanitize_number($_GET['b']) : null;   // blog id
    if($b === null) die('Blog not found');
    $n = isset($_GET['n']) ? sanitize_number($_GET['n']) : 10;      // number to show
    $s = isset($_GET['s']) ? sanitize_number($_GET['s']) : 1;       // page number
    $o = isset($_GET['o']) ? sanitize_string($_GET['o']) : 'name ASC';    // sort
    $fd = isset($_GET['fd']) ? sanitize_string($_GET['fd']) : '1';    // include drafts
    $fd = ($fd == 'true') ? 1 : 0;
    $fs = isset($_GET['fs']) ? sanitize_string($_GET['fs']) : '1';    // include scheduled posts
    $fs = ($fs == 'true') ? 1 : 0;

/**************************************************************************
    Setup
**************************************************************************/

    // Count all posts
    $totalposts = $modelPosts->countPostsOnBlog($b, $fd, $fs);
    $numpages = ceil($totalposts / $n);
    $startrecord = $s * $n - ($n - 1);
    $endrecord = $s * $n;
    if($endrecord > $totalposts) $endrecord = $totalposts;
    
    
    $constRoot = CLIENT_ROOT_ABS;
    $constBlogCMS = CLIENT_ROOT_BLOGCMS;
    
    // Get Posts
    $arrayPosts = $modelPosts->getPostsByBlog($b, $s, $n, $fd, $fs, $o);

/**************************************************************************
    Output
**************************************************************************/

if(count($arrayPosts) > 0):

/*************************************
    Header
*************************************/

echo <<<EOD

    <p>Showing {$startrecord} - {$endrecord} of {$totalposts}</p>

	<table width="100%" cellspacing="2" cellpadding="6" cellspacing="1" class="summary_table">
	<tr><th>Title</th><th>Tag(s)</th><th>Author</th>
	
	<th>Visitors <a href="#" class="helptext" onclick="javascript:alert('This is the count of \'unique visitors\' for each post, not the number of times it has been viewed. So it will count 1 view even if someone refreshes the page multiple times');">[?]</a></th>
	
	<th>Views <a href="#" class="helptext" onclick="javascript:alert('This is the number of times each blog post has been loaded, if someone was to refresh the page 1000 times then it will show 1000 views, so this statistic may be unreliable');">[?]</a></th>
	
	<th>Comments</th><th>Type</th><th>Word Count</th></tr>
EOD;

/*************************************
    Posts
*************************************/
    
foreach($arrayPosts as $post):

    // Label Drafts
    $draft = $post['draft'] ? '<em>(Draft)</em>' : '';

    // Label Scheduled Posts
    if(new DateTime($post['timestamp']) > new DateTime(date('Y-m-d H:i:s'))) $scheduled = '<i>(scheduled)</i>';
    else $scheduled = '';

    // Format time
    $timestamp = date("g:ia, jS M Y", strtotime($post['timestamp']));

    // Format Tags
    $tags = strlen($post['tags']) > 0 ? printTags($post['tags'], $b) : "<i>None</i>";

    // Format Hits
    if(strlen($post['hits']) == 0) $post['hits'] = '0';

    $username = $modelUsers->get(array('id','username'), array('id' => $post['author_id']));

    if(count($username) > 0) {
        $username = '<a href="'.CLIENT_ROOT.'/users/'.$username[0]['id'].'" class="user-link"><span data-userid="'.$username[0]['id'].'">'.$username[0]['username'].'</span></a>';
    } else {
        $username = 'Not Set!';
    }

    $wordcount = str_word_count($post['content']);

echo <<<EOD

    <tr><td>
    <a href="{$constBlogCMS}/blogs/{$b}/posts/{$post['link']}">
    {$post['title']}</a> {$draft} {$scheduled}
    <br />
    <span class="date">{$timestamp}</span>

    </td><td>
    {$tags}
    
    </td><td>
    {$username}

    </td><td>
    {$post['uniqueviews']}

    </td><td>
    {$post['hits']}

    </td><td>
    {$post['numcomments']}
    
    </td><td>
    {$post['type']}
    
    </td><td>
    {$wordcount}

    </td><td width="100">
        <div class="option-dropdown" style="width:100px;">
            <div class="default-option">- Actions -</div>
            <div class="hidden-options">
                <a href="{$constBlogCMS}/posts/{$b}/edit/{$post['id']}">Edit</a>
                <a href="{$constBlogCMS}/posts/{$b}/delete/{$post['id']}" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
            </div>
        </div>   

    </td></tr>

EOD;

endforeach;

/*************************************
    Footer
*************************************/

echo "</table>";

echo <<<EOD
<script>
  $(".user-link").mouseenter(function() {showUserProfile($(this), "{$constRoot}", "{$constBlogCMS}")});
  $(".user-link").mouseleave(function() {hideUserProfile($(this))});
</script>
EOD;


echo showAjaxPagination($numpages, $s);

else:
    echo showInfo("No posts have been made on this blog! <a href='{$constBlogCMS}/posts/{$b}/new'>Create New Post</a>");

endif;
?>