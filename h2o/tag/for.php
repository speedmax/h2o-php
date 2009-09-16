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
 * For tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_For extends h2o_Tag {
    private $_body;
    private $_data = array();
    private $_else;

    private $_pattern = '{
        (?P<item>[a-zA-Z][a-zA-Z0-9-_]*)(?:,\s?(?P<key>[a-zA-Z][a-zA-Z0-9-_]*))?
        \s+in\s+
        (?P<from>[a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*)\s*
        (?:limit\s*:\s*(?P<limit>\d+))?\s*
        (?P<reverse>reversed)?
    }x';

    public function __construct($arguments, h2o_Parser $parser) {
        if (!preg_match($this->_pattern, $arguments, $match)) {
            throw new RuntimeException('Invalid for syntax');
        }

        $this->_body = $parser->parse('endfor', 'else');

        if ($parser->Token['content'] == 'else') {
            $this->_else = $parser->parse('endfor');
        }

        $this->_data['item'] = $match['item']; // Mandatory
        $this->_data['from'] = $match['from']; // Mandatory

        if (!empty($match['key'])) {
            $this->_data['key']  = $match['item'];
            $this->_data['item'] = $match['key'];
        }

        if (!empty($match['limit'])) {
            $this->_data['limit'] = $match['limit'];
        } else {
            $this->_data['limit'] = false;
        }

        $this->_data['reversed'] = !empty($match['reverse']);
    }

    public function render(h2o_Context $context) {
        $from = $context->lookup($this->_data['from']);
        $keys = $this->_data;

        if (is_null($from) || count($from) == 0) {
            if (!is_null($this->_else)) {
                return $this->_else->render($context);
            }

            return null;
        }

        if ($keys['reversed'] && is_array($from)) {
            $from = array_reverse($from);
        }

        if ($keys['limit'] && is_array($from)) {
            $from = array_slice($from, 0, $keys['limit']);
        }

        $output = '';

        $parent = $context['loop'];
        $context->push();
        $rev_count = $is_even = $idx = 0;
        $length = count($from);

        foreach ($from as $key => $data) {
            if (is_object($from) && $keys['limit'] && $_index++ > $keys['limit']) {
                break;
            }

            if (!empty($keys['key'])) {
                $context[$keys['key']] = $key;
            }
            $context[$keys['item']] = $data;

            $rev_count = $length - $idx;
            $is_even   = $idx % 2; 

            $context['loop'] = array(
                'parent'      => $parent,
                'first'       => $idx === 0,
                'last'        => $rev_count === 1,
                'odd'         => !$is_even,
                'even'        => $is_even,
                'length'      => $length,
                'counter'     => $idx + 1,
                'counter0'    => $idx,
                'revcounter'  => $rev_count,
                'revcounter0' => $rev_count - 1
            ); 

            $output .= $this->_body->render($context);
        }

        return $output;
    }
}
