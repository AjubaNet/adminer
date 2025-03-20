<?php

/** Edit all fields containing "_html" by HTML editor TinyMCE and display the HTML in select
* @link https://www.adminer.org/plugins/#use
* @uses TinyMCE, http://tinymce.moxiecode.com/
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerTinymce {
	protected $path;

	/**
	* @param string
	*/
	function __construct($path = "tiny_mce/tiny_mce.js") {
		$this->path = $path;
	}

	function head($dark = null) {
		$lang = Adminer\get_lang();
		$lang = ($lang == "zh" ? "zh-cn" : ($lang == "zh-tw" ? "zh" : $lang));
		if (!file_exists(dirname($this->path) . "/langs/$lang.js")) {
			$lang = "en";
		}
		echo Adminer\script_src($this->path);
		?>
<script<?php echo Adminer\nonce(); ?>>
tinyMCE.init({
	entity_encoding: 'raw',
	language: '<?php echo $lang; ?>'
}); // learn how to customize here: https://www.tinymce.com/docs/configure/
</script>
<?php
	}

	function selectVal(&$val, $link, $field, $original) {
		if (preg_match("~_html~", $field["field"]) && $val != '') {
			$ellipsis = "<i>…</i>";
			$length = strlen($ellipsis);
			$shortened = (substr($val, -$length) == $ellipsis);
			if ($shortened) {
				$val = substr($val, 0, -$length);
			}
			//! shorten with regard to HTML tags - http://php.vrana.cz/zkraceni-textu-s-xhtml-znackami.php
			$val = preg_replace('~<[^>]*$~', '', html_entity_decode($val, ENT_QUOTES)); // remove ending incomplete tag (text can be shortened)
			if ($shortened) {
				$val .= $ellipsis;
			}
			if (class_exists('DOMDocument')) { // close all opened tags
				$dom = new DOMDocument;
				if (@$dom->loadHTML("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head>$val")) { // @ - $val can contain errors
					$val = preg_replace('~.*<body[^>]*>(.*)</body>.*~is', '\1', $dom->saveHTML());
				}
			}
		}
	}

	function editInput($table, $field, $attrs, $value) {
		if (preg_match("~text~", $field["type"]) && preg_match("~_html~", $field["field"])) {
			return "<textarea$attrs id='fields-" . Adminer\h($field["field"]) . "' rows='12' cols='50'>" . Adminer\h($value) . "</textarea>" . Adminer\script("
tinyMCE.remove(tinyMCE.get('fields-" . Adminer\js_escape($field["field"]) . "') || { });
tinyMCE.EditorManager.execCommand('mceAddControl', true, 'fields-" . Adminer\js_escape($field["field"]) . "');
qs('#form').onsubmit = () => {
	tinyMCE.each(tinyMCE.editors, ed => {
		ed.remove();
	});
};
");
		}
	}
}
