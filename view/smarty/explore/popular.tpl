{viewCrumbtrail(array(), 'Explore')}
{viewPageHeader('Most Favourited Blogs', 'plane.png')}

<!--Explore Menu-->
<nav class="explore-menu">
	<a href="{$clientroot_blogcms}/explore/popular">Most Popular</a>
	<a href="{$clientroot_blogcms}/explore/blogsbyletter">Browse By Letter</a>
</nav>

<table cellpadding="5" cellspacing="0" border="0" width="100%">
    <tr><th>Blog Name</th><th># Favourites</th></tr>
	
	{foreach from=$topblogs item=blog}
        
        <tr>
            <td><a href="{$clientroot_blogcms}/blogs/'.{$blog.id}">{$blog.name}</a></td>
            <td>{$blog.fav_count}</td>
        </tr>
    
	{/foreach}
	
</table>