<?php
namespace rbwebdesigns\blogcms;
use rbwebdesigns;
use Codeliner;

/**********************************************************************
class PostsController

This is the controller which acts as the intermediatory between the
model (database) and the view. Any requests to the model are sent from
here rather than the view.

Example requests that will be handled here:
    /posts/1298340239
    /posts/1298340239/new
    /posts/1298340239/new/submit
    /posts/1298340239/edit/67
    /posts/1298340239/edit/67/submit
    /posts/1298340239/delete/67
    
**********************************************************************/

class PostsController extends GenericBlogCMSController {

    // Class Variables
    private $modelBlogs;        // Blogs Model
    private $modelPosts;        // Posts Model
    private $modelComments;     // Comments Model
    private $modelUsers;        // Users Model
    private $modelSecurity;     // Security Functions
    
    protected $view;

    // Constructor
    public function __construct($cms_db, $view) {
        // Initialise Models
        $this->modelBlogs = new ClsBlog($cms_db);
        $this->modelContributors = new ClsContributors($cms_db);
        $this->modelPosts = new ClsPost($cms_db);
        $this->modelComments = new ClsComment($cms_db);
        $this->modelUsers = $GLOBALS['gClsUsers'];
        $this->modelSecurity = new rbwebdesigns\AppSecurity();
        $this->view = $view;
    }    
    
    /*
        function: route
        
        @param    params    Miscellaneous inputs from the URL such as blog id
                            accessed in an array.
    */
        
    public function route($params)
    {
        // Create an easy reader for the params array
        $paramsReader = new Codeliner\ArrayReader\ArrayReader($params);
        
        if(getType($params) == 'array' && array_key_exists(0, $params)):
        
            // Find and open blog
            $blogid = sanitize_number($params[0]);
            $arrayblog = $this->modelBlogs->getBlogById($blogid);
                
            if(getType($arrayblog) != 'array') return $this->throwNotFound();
            
            // Check that user can at least manage posts on this blog - this will be all contributors
            if(!$this->modelContributors->isBlogContributor($arrayblog['id'], $_SESSION['userid'])) return $this->throwAccessDenied();
        
            // Find and open post (if needed)
            //$postid = $paramsReader->integerValue(2, false);
            $postid = array_key_exists(2, $params) && is_numeric($params[2]) ? sanitize_number($params[2]) : false;
            
            if($postid !== false) {
                $arraypost = $this->modelPosts->getPostById($postid);
                                
                if(getType($arraypost) != 'array') $this->throwNotFound();
                
                // Convert gallery image list to array
                if($arraypost['type'] == 'gallery')
                {
                    $arraypost['gallery_imagelist'] = explode(',', $arraypost['gallery_imagelist']);
                }
            }
            
            $action = array_key_exists(1, $params) ? sanitize_string($params[1]) : ''; // Action
            $formsubmitted = (array_key_exists(2, $params) && $params[2] == 'submit'); // Submit
            if (!$formsubmitted) $formsubmitted = (array_key_exists(3, $params) && $params[3] == 'submit'); // Submit
                    
            switch($action):
                case 'new':
                    if($formsubmitted) $this->action_createPost();
                    $posttype = $paramsReader->stringValue(2, '');        
                    $this->createPost($arrayblog, $posttype);
                    break;
                    
                case 'edit':
                    if($formsubmitted) $this->action_editPost($arraypost);
                    $this->editPost($arrayblog, $arraypost);
                    break;
                
                case 'delete':
                    $this->action_deletePost($arraypost);
                    break;
        
                case 'preview':
                    $this->previewPost();
                    break;
                
                default:
                    $this->managePosts($arrayblog);
                    break;
                    
            endswitch;
                        
        else:
            // Must have at least one param -> blogid
            return $this->throwNotFound();
        
        endif;
    }
        
    /******************************************************************
        GET
    ******************************************************************/
    
    /**
        View post overview page
    **/
    public function managePosts($arrayblog)
    {
        $this->view->setVar('blog', $arrayblog);
        $this->view->setPageTitle('Manage Posts - '.$arrayblog['name']);
        $this->view->addScript(CLIENT_ROOT_BLOGCMS.'/js/showUserCard');
        $this->view->render('posts/manage.tpl');
    }
    
    
    /**
        View new post form
    **/
    public function createPost($arrayblog, $viewtype)
    {
        // Set blog in view
        $this->view->setVar('blog', $arrayblog);
        $this->view->setPageTitle('New Post');
        
        $this->view->addScript(CLIENT_ROOT.'/resources/js/rbwindow');
        $this->view->addScript(CLIENT_ROOT.'/resources/js/rbrtf');
        $this->view->addStylesheet(CLIENT_ROOT.'/resources/css/rbwindow');
        $this->view->addStylesheet(CLIENT_ROOT.'/resources/css/rbrtf');
        
        // View Form
        switch($viewtype)
        {
            case 'video':
            $this->view->render('posts/videopost.tpl');
            break;
            
            case 'gallery':
            $this->view->render('posts/gallerypost.tpl');
            break;
            
            case 'standard':
            $this->view->render('posts/standardpost.tpl');
            break;
            
            default:
            // Render the view
            $this->view->render('posts/newpostmenu.tpl');
            break;
        }
    }
    
    
    /**
        View edit post form
    **/
    public function editPost($arrayblog, $arraypost)
    {
        if($arraypost['initialautosave'] == 1)
        {
            // get the most recent content from the actual autosave record
            $arrayAutosave = $this->modelPosts->getAutosave($arraypost['id']);

            if(getType($arrayAutosave) == 'array')
            {
                // Merge the two
                $arraypost = array_merge($arraypost, $arrayAutosave);
            }

            // mark as autosave off
            $this->modelPosts->update(array('id' => $arraypost['id']), array('initialautosave' => 0));
            
        }
        elseif($this->modelPosts->autosaveExists($arraypost['id']))
        {
            // Has been edited without being saved
            // Show a notice
            $arraypost['autosave'] = $this->modelPosts->getAutosave($arraypost['id']);
        }
        
        // Check we have permission to perform action
        if(!($this->modelContributors->isBlogContributor($arraypost['blog_id'], $_SESSION['userid'], 'all') || $_SESSION['userid'] == $arraypost['author_id'])) return $this->throwAccessDenied();
                
        $this->view->setVar('post', $arraypost);
        $this->view->setVar('blog', $arrayblog);
        $this->view->setPageTitle('Edit Post - '.$arraypost['title']);
        
        $this->view->addScript(CLIENT_ROOT.'/resources/js/rbwindow');
        $this->view->addScript(CLIENT_ROOT.'/resources/js/rbrtf');
        $this->view->addStylesheet(CLIENT_ROOT.'/resources/css/rbwindow');
        $this->view->addStylesheet(CLIENT_ROOT.'/resources/css/rbrtf');

        switch($arraypost['type'])
        {
            case 'video':
            $this->view->render('posts/videopost.tpl');
            break;
            
            case 'gallery':
            $this->view->render('posts/gallerypost.tpl');
            break;
            
            default:
            case 'standard':
            $this->view->render('posts/standardpost.tpl');
            break;
        }
    }
    
    public function previewPost() {
        
        echo "Preview Post - functionality to be completed!";
        
        exit; // go no further!
    }
    

    /******************************************************************
        POST Requests (Form Submitted)
    ******************************************************************/
    
    /**
        Create a new blog post
    **/
    public function action_createPost() {
        
        $postdate = $_POST['fld_postdate_y'].'-'.$_POST['fld_postdate_m'].'-'.$_POST['fld_postdate_d'].' '.$_POST['fld_postdate_h'].':'.$_POST['fld_postdate_i'].':00';
        
        $newPost = array(
            'title' => $_POST['fld_posttitle'],
            'content' => $_POST['fld_postcontent'],
            'tags' => $_POST['fld_tags'],
            'blog_id' => $_POST['fld_blogid'],
            'draft' => $_POST['fld_draft'],
            'allowcomments' => $_POST['fld_allowcomment'],
            'type' => $_POST['fld_posttype'],
            'initialautosave' => 0,
            'timestamp' => $postdate
        );
        
        // Add additional fields for video post
        if($newPost['type'] == 'video')
        {
            $newPost['videoid'] = $_POST['fld_postvideoID'];
            $newPost['videosource'] = $_POST['fld_postvideosource'];
        }
        
        if($newPost['type'] == 'gallery')
        {
            $newPost['gallery_imagelist'] = $_POST['fld_gallery_imagelist'];
        }
        
        // Add to database
        if(array_key_exists('fld_postid', $_POST)) {
            
            // This should be the case as it should have been created when the autosave run
            $postid = sanitize_number($_POST['fld_postid']);
            $this->modelPosts->updatePost($postid, $newPost);
            
            // Remove any autosaves
            $this->modelPosts->removeAutosave($postid);
            
        } else {
            $this->modelPosts->createPost($newPost);
        }
        
        // Set success messages
        setSystemMessage(ITEM_CREATED, 'Success');
        redirect(CLIENT_ROOT_BLOGCMS.'/posts/'.$_POST['fld_blogid']);
    }
    
    
    /**
        Edit an existing blog post
    **/
    public function action_editPost($arraypost)
    {
        // Re-check security with heightened permissions
        if(!($this->modelContributors->isBlogContributor($_POST['fld_blogid'], $_SESSION['userid'], 'all') || $_SESSION['userid'] == $arraypost['author_id'])) return $this->throwAccessDenied();
        
        $postdate = $_POST['fld_postdate_y'].'-'.$_POST['fld_postdate_m'].'-'.$_POST['fld_postdate_d'].' '.$_POST['fld_postdate_h'].':'.$_POST['fld_postdate_i'].':00';
        
        $arrayPostUpdates = array(
            'title' => $_POST['fld_posttitle'],
            'content' => $_POST['fld_postcontent'],
            'tags' => $_POST['fld_tags'],
            'draft' => $_POST['fld_draft'],
            'allowcomments' => $_POST['fld_allowcomment'],
            'initialautosave' => 0,
            'timestamp' => $postdate
        );
        
        // Add additional fields for video post
        if($arraypost['type'] == 'video')
        {
            $arrayPostUpdates['videoid'] = $_POST['fld_postvideoID'];
            $arrayPostUpdates['videosource'] = $_POST['fld_postvideosource'];
        }
        
        if($arraypost['type'] == 'gallery')
        {
            $arrayPostUpdates['gallery_imagelist'] = $_POST['fld_gallery_imagelist'];
        }
        
        // Update DB with post information
        $this->modelPosts->updatePost($_POST['fld_postid'], $arrayPostUpdates);
        
        // Remove Autosave
        $this->modelPosts->removeAutosave($_POST['fld_postid']);
        
        // Set Success Message
        setSystemMessage(ITEM_UPDATED, 'Success');
        redirect(CLIENT_ROOT_BLOGCMS.'/posts/'.$_POST['fld_blogid'].'/edit/'.$_POST['fld_postid']);
    }
    
    
    /**
        Delete a blog post
    **/
    public function action_deletePost($arraypost) {

        // Check we have permission to perform action - if the user created the post or is blog admin
        if(!($this->modelContributors->isBlogContributor($arraypost['blog_id'], $_SESSION['userid'], 'all') || $_SESSION['userid'] == $arraypost['author_id'])) return $this->throwAccessDenied();
        
        // Perform Database query (using generic delete)
        $delete = $this->modelPosts->delete(array('id' => $arraypost['id']));
        
        // Check outcome
        if($delete) setSystemMessage(ITEM_DELETED, 'Success');
        else setSystemMessage('Sorry, There has been an error deleting your post.', 'Error');
        
        // Redirect
        redirect(CLIENT_ROOT_BLOGCMS.'/posts/'.$arraypost['blog_id']);
    }
    
}
?>