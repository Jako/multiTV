<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @author		ExpressionEngine Dev Team. Adopted to MODX by Thomas Jakobi
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * Pagination Class
 *
 * @category	Pagination
 * @author		ExpressionEngine Dev Team. Adopted to MODX by Thomas Jakobi
 * @link		http://www.codeigniter.com/user_guide/libraries/pagination.html
 */
class Pagination {

	var $total_rows = 0; // Total number of items (database results)
	var $per_page = 10; // Max number of items you want shown per page
	var $num_links = 2; // Number of "digit" links to show before/after the currently viewed page
	var $cur_page = 0; // The current page being viewed
	var $use_page_numbers = FALSE; // Use page number for segment instead of offset
	var $first_link = '&lsaquo; First';
	var $next_link = '&gt;';
	var $prev_link = '&lt;';
	var $last_link = 'Last &rsaquo;';
	var $full_tag_open = '';
	var $full_tag_close = '';
	var $first_tag_open = '';
	var $first_tag_close = '&nbsp;';
	var $last_tag_open = '&nbsp;';
	var $last_tag_close = '';
	var $first_url = ''; // Alternative URL for the First Page.
	var $cur_tag_open = '&nbsp;<span id="currentPage">';
	var $cur_tag_close = '</span>';
	var $next_tag_open = '&nbsp;';
	var $next_tag_close = '&nbsp;';
	var $prev_tag_open = '&nbsp;';
	var $prev_tag_close = '';
	var $num_tag_open = '&nbsp;';
	var $num_tag_close = '';
	var $page_query_string = 'page';
	var $original_query_string = '';
	var $display_pages = TRUE;
	var $anchor_class = '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	public function __construct($params = array()) {
		if (count($params) > 0) {
			$this->initialize($params);
		}

		if ($this->anchor_class != '') {
			$this->anchor_class = 'class="' . $this->anchor_class . '" ';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */
	function initialize($params = array()) {
		if (count($params) > 0) {
			foreach ($params as $key => $val) {
				if (isset($this->$key)) {
					$this->$key = $val;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @access	public
	 * @return	string
	 */
	function create_links() {
		global $modx;
		// If our item count or per-page total is zero there is no need to continue.
		if ($this->total_rows == 0 OR $this->per_page == 0) {
			return '';
		}

		// Calculate the total number of pages
		$num_pages = ceil($this->total_rows / $this->per_page);

		// Is there only one page? Hm... nothing more to do here then.
		if ($num_pages == 1) {
			return '';
		}

		// Set the base page index for starting page number
		if ($this->use_page_numbers) {
			$base_page = 1;
		} else {
			$base_page = 0;
		}

		// Get current MODX values
		$cur_id = $modx->documentObject['id'];

		// Get original query string
		$c = substr($this->original_query_string, 0, 1);
		if ($c == '&' || $c == '?') {
			$this->original_query_string = substr($this->original_query_string, 1);
		}
			$this->original_query_string = ($this->original_query_string != '') ? '&' . $this->original_query_string : '';

		// Set current page to 1 if using page numbers instead of offset
		if ($this->use_page_numbers AND $this->cur_page == 0) {
			$this->cur_page = $base_page;
		}

		$this->num_links = (int) $this->num_links;

		if ($this->num_links < 1) {
			show_error('Your number of links must be a positive number.');
		}

		if (!is_numeric($this->cur_page)) {
			$this->cur_page = $base_page;
		}

		// Is the page number beyond the result range?
		// If so we show the last page
		if ($this->use_page_numbers) {
			if ($this->cur_page > $num_pages) {
				$this->cur_page = $num_pages;
			}
		} else {
			if ($this->cur_page > $this->total_rows) {
				$this->cur_page = ($num_pages - 1) * $this->per_page;
			}
		}

		$uri_page_number = $this->cur_page;

		if (!$this->use_page_numbers) {
			$this->cur_page = floor(($this->cur_page / $this->per_page) + 1);
		}

		// Calculate the start and end numbers. These determine
		// which number to start and end the digit links with
		$start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

		// And here we go...
		$output = '';

		// Render the "First" link
		if ($this->first_link !== FALSE AND $this->cur_page > ($this->num_links + 1)) {
			$first_url = ($this->first_url == '') ? $modx->makeUrl($cur_id, '', $this->original_query_string) : $this->first_url;
			$output .= $this->first_tag_open . '<a ' . $this->anchor_class . 'href="' . $first_url . '">' . $this->first_link . '</a>' . $this->first_tag_close;
		}

		// Render the "previous" link
		if ($this->prev_link !== FALSE AND $this->cur_page != 1) {
			if ($this->use_page_numbers) {
				$i = $uri_page_number - 1;
			} else {
				$i = $uri_page_number - $this->per_page;
			}
			if ($i == 1 && $this->first_url != '') {
				$output .= $this->prev_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->first_url . '">' . $this->prev_link . '</a>' . $this->prev_tag_close;
			} else {
				$i = ($i == 1) ? $this->original_query_string : $this->page_query_string . '=' . $i . $this->original_query_string;
				$output .= $this->prev_tag_open . '<a ' . $this->anchor_class . 'href="' . $modx->makeUrl($cur_id, '', $i) . '">' . $this->prev_link . '</a>' . $this->prev_tag_close;
			}
		}

		// Render the pages
		if ($this->display_pages !== FALSE) {
			// Write the digit links
			for ($loop = $start - 1; $loop <= $end; $loop++) {
				if ($this->use_page_numbers) {
					$i = $loop;
				} else {
					$i = ($loop * $this->per_page) - $this->per_page;
				}

				if ($i >= $base_page) {
					if ($this->cur_page == $loop) {
						$output .= $this->cur_tag_open . $loop . $this->cur_tag_close; // Current page
					} else {
						$n = ($i == $base_page) ? '' : $i;

						if ($n == '' && $this->first_url != '') {
							$output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->first_url . '">' . $loop . '</a>' . $this->num_tag_close;
						} else {
							$n = ($n == '') ? '' : $this->page_query_string . '=' . $n;

							$output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $modx->makeUrl($cur_id, '', $n . $this->original_query_string) . '">' . $loop . '</a>' . $this->num_tag_close;
						}
					}
				}
			}
		}

		// Render the "next" link
		if ($this->next_link !== FALSE AND $this->cur_page < $num_pages) {
			if ($this->use_page_numbers) {
				$i = $this->cur_page + 1;
			} else {
				$i = ($this->cur_page * $this->per_page);
			}
			$output .= $this->next_tag_open . '<a ' . $this->anchor_class . 'href="' . $modx->makeUrl($cur_id, '', $this->page_query_string . '=' . $i . $this->original_query_string) . '">' . $this->next_link . '</a>' . $this->next_tag_close;
		}

		// Render the "Last" link
		if ($this->last_link !== FALSE AND ($this->cur_page + $this->num_links) < $num_pages) {
			if ($this->use_page_numbers) {
				$i = $num_pages;
			} else {
				$i = (($num_pages * $this->per_page) - $this->per_page);
			}
			$output .= $this->last_tag_open . '<a ' . $this->anchor_class . 'href="' . $modx->makeUrl($cur_id, '', $this->page_query_string . '=' . $i . $this->original_query_string) . '">' . $this->last_link . '</a>' . $this->last_tag_close;
		}

		// Kill double slashes.  Note: Sometimes we can end up with a double slash
		// in the penultimate link so we'll kill all double slashes.
		$output = preg_replace("#([^:])//+#", "\\1/", $output);

		// Add the wrapper HTML if exists
		$output = $this->full_tag_open . $output . $this->full_tag_close;

		return $output;
	}

}

// END Pagination Class
?>
