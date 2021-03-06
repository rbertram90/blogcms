<?php
/****************************************************************
  Widget-profile
  Shows a brief biography of the author (owner) of the blog
****************************************************************/

class Profile extends BlogCMSWidget {

    // Class Variables
    private $modelUsers;
    private $blog;
    
    public function __construct($settings, $blog, $modelBlog, $modelPosts, $modelComments, $modelContributors, $modelUsers)
	{
        // Constructor
        $this->modelUsers = $modelUsers;
        $this->blog = $blog;
        
        $this->defaults = array(
            'name' => '',
            'showpic' => true
        );
        
        $this->config = $settings;
		$this->setupConfig();
    }
    
    public function generate() {

        $owner = $this->modelUsers->getUserById($this->blog['user_id']);
        $cr = CLIENT_ROOT;
        $sr = SERVER_ROOT;
        $cra = CLIENT_ROOT_ABS;
        $ar = CLIENT_ROOT_AVATARS_ABS;
        
        $output = "<h3>{$this->config['name']}</h3>";
        
        if($this->config['showpic']) {
            $output.= "<img src='{$ar}/thumbs/{$owner['profile_picture']}' class='profile-picture' />";
        }
        
return $output.<<<EOD
    <p class="profile-name">{$owner['name']} {$owner['surname']} ({$owner['username']})</p>
    <p class="profile-description">{$owner['description']}</p>
    <div style="text-align:right; clear:both;">
        <a href="{$cra}/users/{$owner['id']}" class="profile-link">View Full Profile</a>
    </div>
EOD;
        
    }
}
?>