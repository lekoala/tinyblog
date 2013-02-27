tinyblog
========

tinyblog is my attempt to build a minimalist blog engine using markdown.

## usage ##

	$blog = new tinyblog();
	echo $blog->output();

## how it works ##

Quite simply! tinyblog is going to find a list of articles in the blog subfolder (configurable with the "dir" option). Each article should be name with the slug you want to use, for instance 2013-01-01-my-post.md

A list of articles is going to be built, with the title, the date (based on filemtime) and a summary (which takes the first few lines of your articles after your title).
The list is then cached for future use and optimise disk access.

## latest post ##

You can get the latest post by simple doing

	$post = require PATH_TO_BLOG . '/_list.php';
	$article = $post[0];

The last article is always the first one.

## comments ##

To keep things simple, tinyblog use third party comment systems. A sample livefyre code is included, but you need to replace it by passing the html snippet as the "comments_html" option to the constructor.