<?php

class h2o_Evaluator {
    static function gt($l, $r) { return $l > $r; }
    static function ge($l, $r) { return $l >= $r; }

    static function lt($l, $r) { return $l < $r; }
    static function le($l, $r) { return $l <= $r; }

    static function eq($l, $r) { return $l == $r; }
    static function ne($l, $r) { return $l != $r; }

    static function not_($bool) { return !$bool; }
    static function and_($l, $r) { return ($l && $r); }
    static function or_($l, $r) { return ($l && $r); }

    # Currently only support single expression with no preceddence ,no boolean expression
    #    [expression] =  [optional binary] ? operant [ optional compare operant]
    #    [operant] = variable|string|numeric|boolean
    #    [compare] = > | < | == | >= | <=
    #    [binary]    = not | !
    static function exec($args, h2o_Context $context) {
        $argc = count($args);
        $first = array_shift($args);
        $first = $context->resolve($first);
        switch ($argc) {
            case 1 :
                return $first;
            case 2 :
                if (is_array($first) && isset($first['operator']) && $first['operator'] == 'not') {
                    $operant = array_shift($args);
                    $operant = $context->resolve($operant);
                    return !($operant);
                }
            case 3 :
                list($op, $right) = $args;
                $right = $context->resolve($right);
                return call_user_func(array(__CLASS__, $op['operator']), $first, $right);
            default:
                return false;
        }
    }
}
