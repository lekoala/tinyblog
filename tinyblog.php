<?php

/**
 * A really minimalistic markdown based blog engine
 * 
 * @author LeKoala
 * @link www.lekoala.be
 */
class tinyblog {

	protected $config;

	public function __construct($config = array()) {
		//default options
		$this->config = array(
			'dir' => __DIR__ . '/blog',
			'base' => isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/',
			'date_format' => 'd/m/Y \a\t H:i:s',
			'querystring' => 'slug',
			'num_words' => 30,
			'ellipsis' => '...',
			'cache' => 1,
			'cache_time' => 60 * 60 * 24,
			'comments' => 1,
			'comments_html' => '<!-- START: Livefyre Embed -->
<div id="livefyre-comments"></div>
<script type="text/javascript" src="http://zor.livefyre.com/wjs/v3.0/javascripts/livefyre.js"></script>
<script type="text/javascript">
(function () {
    var articleId = fyre.conv.load.makeArticleId(null);
    fyre.conv.load({}, [{
        el: "livefyre-comments",
        network: "livefyre.com",
        siteId: "__REPLACE_WITH_YOUR_ID__",
        articleId: articleId,
        signed: false,
        collectionMeta: {
            articleId: articleId,
            url: fyre.conv.load.makeCollectionUrl(),
        }
    }], function() {});
}());
</script>
<!-- END: Livefyre Embed -->',
			'prev' => '<',
			'next' => '>',
			'parser' => function($md) {
				if (function_exists('Markdown')) {
					return Markdown($md);
				}
				return $md;
			}
		);
		$this->config = array_merge($this->config, $config);
	}

	public function getPostList() {
		$cache = $this->config['dir'] . '/_list.php';
		$list = array();
		if (is_file($cache)) {
			$list = require $cache;
			if ($this->config['cache']) {
				if (filemtime($cache) > (time() - $this->config['cache_time'])) {
					return $list;
				}
			}
		}
		$iterator = new DirectoryIterator($this->config['dir']);
		
		$existing = array_map(function($i) {
			return $i['slug'];
		}, $list);
		foreach ($iterator as $file) {
			if($file->getExtension() != 'md') {
				continue;
			}
			$slug = rtrim($this->config['base'],'/') . '/' . $file->getBasename('.md');
			if(in_array($slug, $existing)) {
				continue;
			}
			$title = strip_tags(trim($this->config['parser'](($this->getLine($file->getPathname())))));
			$last_updated = filemtime($file->getPathname());
			$summary = strip_tags(trim($this->config['parser'](($this->getLine($file->getPathname(),3,10)))));
			$list[] = compact('slug', 'title', 'last_updated', 'summary');
		}
		usort($list, function($a, $b) {
			  return $a['last_updated'] < $b['last_updated'];
		  });
		if ($this->config['cache']) {
			file_put_contents($cache, '<?php return ' . var_export($list, true) . ';');
		}
		return $list;
	}

	public function getSlug() {
		$slug = $this->config['base'];
		if (isset($_GET[$this->config['querystring']])) {
			$slug = $_GET[$this->config['querystring']];
		}
		$slug = preg_replace('/([^a-z0-9_-]*)/i', '', $slug);
		return $slug;
	}

	public function output() {
		$slug = $this->getSlug();
		if (!empty($slug)) {
			$output = $this->outputPost($slug);
		} else {
			$output = $this->outputList();
		}
		return $output;
	}

	public function outputPost($slug) {
		$file = $this->config['dir'] . '/' . $slug . '.md';
		if (!is_file($file)) {
			return $this->outputList();
		}
		$content = $this->config['parser'](file_get_contents($file));
		$html = "<article>$content</article>";
		//comments
		if($this->config['comments']) {
			$html .= '<div id="comments">';
			$html .= $this->config['comments_html'];
			$html .= '</div>';
		}
		return $html;
	}

	public function outputList($limit = 2) {
		$list = $this->getPostList();
		$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
		$enable_pagination = $offset = $page * $limit;
		$i = 0;
		$html = '<section>';
		foreach ($list as $post) {
			if ($offset-- > 0) {
				continue;
			}
			if (++$i > $limit) {
				$enable_pagination = true;
				break;
			}
			$title = $post['title'];
			$date = date($this->config['date_format'], $post['last_updated']);
			$url = $post['slug'];
			preg_match('/^([^.!?\s]*[\.!?\s]+){0,' . $this->config['num_words'] . '}/', strip_tags(trim($post['summary'])), $matches);
			$summary = trim($matches[0]);
			if (str_word_count($summary) > $this->config['num_words']) {
				$summary .= $this->config['ellipsis'];
			}

			$post_html = "<article>
				<h2>$title</h2>
				<p class='date'>$date</p>
				<p class='summary'><a href='$url'>$summary</a></p>
			</article>";
			$html .= $post_html;
		}
		$html .= '</section>';
		if ($enable_pagination) {
			$html .= "<div class='pagination'><ul>";
			if ($page > 0) {
				$prev_url = $this->config['base'] . '?page=' . ($page - 1);
				$html .= '<li><a href="' . $prev_url . '">' . $this->config['prev'] . '</a></li>';
			}
			if (($page + 1) * $limit < count($list)) {
				$next_url = $this->config['base'] . '?page=' . ($page + 1);
				$html .= '<li><a href="' . $next_url . '">' . $this->config['next'] . '</a></li>';
			}
			$html .= "</ul></div>";
		}
		return $html;
	}

	protected function getLine($file, $ln = 1, $n = 1) {
		$f = fopen($file, 'r');
		$c = '';
		if ($f) {
			while ($line = fgets($f)) {
				--$ln;
				if ($ln <= 0) {
					$c .= $line;
					--$n;
					if ($n <= 0) {
						fclose($f);
						return $c;
					}
				}
			}
		}
		return $c;
	}

}