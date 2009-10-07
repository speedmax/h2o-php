<?php

require_once H2O_PATH.'/h2o/filter.php';

// Safe PHP functions
h2o_Filter::add(array(
    'md5', 'sha1', 'join', 'wordwrap', 'trim',
    'upper' => 'strtoupper', 'lower' => 'strtolower',
    'escape' => 'htmlspecialchars'
));

class CoreFilters extends FilterCollection {
}
class HtmlFilters extends FilterCollection {
}
class NumberFilters extends FilterCollection {
}
class StringFilters extends FilterCollection {
    static public function capitalize($str) {
        return ucwords($str);
    }
}
class DatetimeFilters extends FilterCollection {
}

h2o_Filter::add(array('CoreFilters', 'HtmlFilters', 'NumberFilters', 'StringFilters', 'DatetimeFilters'));
