<?php
/**
 * H2O Template
 *
 * @author James Logsdon <dwarf@girsbrain.org>
 * @author Taylor Luk <taylor.luk@idealian.net>
 * @package h2o-php
 * @copyright Copyright (c) 2008 Taylor Luk
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Variable node
 *
 * @extends h2o_Node
 */
class h2o_Node_Variable extends h2o_Node {
    /**
     * Name of the variable we will be looking up
     *
     * @var string
     * @access private
     */
    private $_variable;

    /**
     * Filters that should be applied to the variables value on render
     *
     * @var array
     * @access private
     */
    private $_filters = array();

    /**
     * Takes a valid variable tag and parses out any filters
     *
     * @access public
     * @param string $contents
     * @return void
     * @todo Filter parsing
     */
    public function __construct($contents) {
        if (!strpos($contents, '|')) {
            $this->_variable = symbol(trim($contents));
            return;
        }

        list($key, $filters) = explode('|', $contents, 2);

        $this->_variable = symbol(trim($key));

        // Parse the filter string
        foreach (explode('|', $filters) as $filter) {
            @list($filter, $arguments) = preg_split('/\s+/', trim($filter), 2);

            if (!empty($arguments)) {
                $arguments = preg_split('/\s+/', $arguments);
            } else {
                $arguments = array();
            }

            array_push($this->_filters, compact('filter', 'arguments'));
        }
    }

    /**
     * Load the variable from the context and apply any filters.
     * 
     * @access public
     * @param h2o_Context $context
     * @return void
     * @todo Filter handling
     */
    public function render(h2o_Context $context) {
        $content = $context->resolve($this->_variable);
        $escaped = false; // We only want to escape output once

        foreach ($this->_filters as $filter) {
            if ($filter['filter'] == 'escape' || $filter['filter'] == 'safe') {
                $escaped = true;
            }

            $content = h2o_Filter::run($filter['filter'], $content, $filter['arguments']);
        }

        if (!$escaped && $context->shouldEscape()) {
            $content = h2o_Filter::run('escape', $content);
        }

        return $content;
    }
}
