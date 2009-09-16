<?php

require_once H2O_PATH.'/h2o/filter.php';

$strings = array('upper' => 'strtoupper', 'lower' => 'strtolower', 'titlecase' => 'ucwords');

h2o_Filter::add($strings);
