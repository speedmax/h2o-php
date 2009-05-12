<?php
/**
 * @name SimpleSpec
 *      PHP SimpleTest extension for Behavior driven development(BDD)
 *      
 *      why not PHPSpec? well its a good project but SimpleTest has better coverage for testing 
 *      and i want better grammer
 *      
 *  Features:
 *      - Reuse SimpleTest framework
 *      - simplly one include from away
 *      - Underscore for readibility - all examples uses underscore to seperate descriptions.
 *      - Natural language grammer
 *      - before_all, after_all is not supported, i don't want to modify SimpleTest
 *      - conventional before/after named prepare/cleanup respectively
 * 
 * @example
 *      class Describe_his_mum extends SimpleSpec {
 *          function prepare() {
 *              $this->mum = new Mum(array('mood'=>'angry')); 
 *          }
 * 
 *          function should_be_very_angry_when_i_break_my_lunch_box() {
 *              expects($this->mum->mood)->should_be('angry');
 *          }
 * 
 *         function should_be_very_happy_when_i_punch_her_in_the_face() {
 *              punch($this->mum);
 *              expects($this->mum->mood)->should_be('happy');
 *          }
 * 
 *          function cleanup() {
 *              unset($this->mum); // kill da mum
 *          }
 *      }
 * @author - Taylor Luk aka 'speedmax'
 * @license Free for all  
 */

class SimpleSpec extends UnitTestCase {
    public $target;
    private $negate;
    private $matcher;

    function __construct($label = false) {
        if (! $label) {
            $label = str_replace(array('Describe_', '_'), array('', ' '), get_class($this));
        }
        $this->matcher = new SpecMatcher($this);        
        parent::__construct($label);
    }

    function _isTest($method) {
        if (strtolower(substr($method, 0, 2)) == 'it' || strtolower(substr($method, 0, 6)) == 'should') {
            return ! SimpleTestCompatibility::isA($this, strtolower($method));
        }
        return false;
    }

    function prepare() {
    }
    
    
    function cleanup() {
    }
    
    function setUp() {
        $this->prepare();
    }
    
    function tearDown() {
        $this->cleanup();
    }
    
    function __call($name, $args) {
        $matcher = null;
        $this->matcher->negate(false);
        array_unshift($args, $this->target);
        
        if (preg_match('/should_not_(.*)/', $name, $match)) {
            $matcher = $match[1];
            $this->matcher->negate(true);
        } 
        elseif (preg_match('/should_(.*)/', $name, $match)) {
            $matcher = $match[1];
        } 

        if (!method_exists($this->matcher, $matcher)) {
            throw new Exception("matcher doesn't exist");
        } else {
            call_user_func_array(array($this->matcher, $matcher), $args);
        }
    }
    
    function offsetGet($object) {
        return $this->expect($object);
    }
    
    function offsetSet($key, $value) {}
    function offsetExists($key) {}
    function offsetUnset($key) {}
    
    function expect($object) {
        $this->target = $object;
        return $this;
    }
    
    function value_of($object) {
        return $this->expect($object);
    }
    
    function assert(&$expectation, $compare, $message = '%s') {
        $result = $expectation->test($compare);
        if ($this->matcher->negate) {
            $result = !$result;
        }
        if ($result) {
            return $this->pass(sprintf($message,$expectation->overlayMessage($compare, $this->_reporter->getDumper())));
        } else {
            return $this->fail(sprintf($message,$expectation->overlayMessage($compare, $this->_reporter->getDumper())));
        }
    }
    
    /**
     *    Uses a stack trace to find the line of an assertion.
     *    @return string           Line number of first assert*
     *                             method embedded in format string.
     *    @access public
     */
    function getAssertionLine() {
        $trace = new SimpleStackTrace(array('should', 'it_should', 'assert', 'expect', 'pass', 'fail', 'skip'));
        return $trace->traceMethod();
    }
}

function expects($subject) {
    $trace = debug_backtrace();
    $object = $trace[1]['object'];
    return $object->expect($subject);
}

class Have_Matcher {
    function __construct($subject, $count, $runtime) {
        $this->subject = $subject;
        $this->count = $count;
        $this->runtime = $this->runtime;
    }
    
    function __get($key) {
        $object = $runtime->target;
        
        if (is_array($object) && isset($object[$this->subject]))
            $subject = $object[$this->subject];
        elseif (is_object($object) && isset($object->{$this->subject}))
            $subject = $object->{$this->subject};
        return $this->runtime->be(count($subject), $this->count);
    }
}

class SpecMatcher {
    private $tester;
    public $negate;
    
    function __construct($runtime) {
        $this->runtime = $runtime;
    }
    
    function negate($bool = false) {
        $this->negate = $bool;
    }
    
    function be($first, $second, $message = '%s') {
        return $this->runtime->assert(new EqualExpectation($first), $second, $message);
    }
    
    function be_equal($first, $second, $message = '%s') {
        return $this->be($first, $second, $message);
    }
    
    function be_empty($subject, $message = '%s') {
        $dumper = new SimpleDumper();
        
        return $this->be_true(empty($subject) == true, "[{$dumper->describeValue($subject)}] should be empty");
    }
    
    function be_true($result, $message = '%s') {
        return $this->runtime->assert(new TrueExpectation(), $result, $message);
    }

    function be_false($result, $message = '%s') {
        return $this->runtime->assert(new FalseExpectation(),  $result, $message);
    }
    
    function be_null($value, $message = '%s') {
        $dumper = new SimpleDumper();
        $message = sprintf($message, '[' . $dumper->describeValue($value) . '] should be null');
        return $this->runtime->assert(new TrueExpectation(), ! isset($value), $message);
    }
    
    function be_a($object, $type, $message = '%s') {
        if (strtolower($type) == 'object') 
            $type = 'stdClass';
        return $this->runtime->assert(new IsAExpectation($type),$object, $message);
    }
    
    function be_an($object, $type, $message = '%s') {
        return $this->be_a($object, $type, $message);
    }
    
    function be_within_margin($first, $second, $margin, $message = '%s') {
        return $this->runtime->assert(
                new WithinMarginExpectation($first, $margin),
                $second,
                $message);
    }
    
    function be_outside_margin($first, $second, $margin, $message = '%s') {
        return $this->runtime->assert(
                new assertOutsideMargin($first, $margin),
                $second,
                $message);
    }
    
    function be_identical($first, $second, $message = '%s') {
        return $this->runtime->assert(
                new IdenticalExpectation($first),
                $second,
                $message);
    }
    
    function be_reference_of(&$first, &$second, $message = '%s') {
        $dumper = new SimpleDumper();
        $message = sprintf(
                $message,
                '[' . $dumper->describeValue($first) .
                        '] and [' . $dumper->describeValue($second) .
                        '] should reference the same object');
        return $this->runtime->assert(new TrueExpectation(), SimpleTestCompatibility::isReference($first, $second), $message);
    }
    
    function be_clone_of(&$first, &$second, $message = '%s') {
        $dumper = new SimpleDumper();
        $message = sprintf(
                $message,
                '[' . $dumper->describeValue($first) .
                        '] and [' . $dumper->describeValue($second) .
                        '] should not be the same object');
        $identical = new IdenticalExpectation($first);
        return $this->runtime->assert(new TrueExpectation(), 
                $identical->test($second) && ! SimpleTestCompatibility::isReference($first, $second),
                $message);
    }
    
    function be_copy_of(&$first, &$second, $message = '%s') {
        $dumper = new SimpleDumper();
        $message = sprintf(
                $message,
                "[" . $dumper->describeValue($first) .
                        "] and [" . $dumper->describeValue($second) .
                        "] should not be the same object");
        return $this->runtime->assert(new FaseExpectation(), 
                SimpleTestCompatibility::isReference($first, $second),
                $message);
    }
    
    function contain($subject, $target, $message = '%s') {
        $dumper = new SimpleDumper();
        $message = "[ {$dumper->describeValue($subject)}] should contain [{$dumper->describeValue($target)}]";
        
        if (is_array($subject) && is_array($target)) {
            return $this->be_true(array_intersect($target, $subject) == $target, $message);
        } elseif (is_array($subject)) {
            return $this->be_true(in_array($target, $subject), $message);
        } elseif (is_string($subject)) {
            return $this->be_true(strpos($target, $subject) !== false, $message);
        }
    }
    
    function have($target, $count, $key, $messages = '%s') {
        $dumper = new SimpleDumper();
        $subject = null;
        
        if (is_array($target) && isset($target[$key]))
            $subject = $target[$key];
        elseif (is_object($target) && isset($target->$key)) 
            $subject = $target->$key;
        
        $result = count($subject);
        $messages = "Expecting count for [$key] should be [$count], got [$result]";

        return $this->be($result, $count, $messages);
    } 
    
    function match($subject, $pattern, $message = '%s') {
        $regex = "/^[\/{#](.*)[\/}#][imsxeADSUXJu]*/sm";

        if (preg_match($regex, $subject)) {
            list($subject, $pattern) = array($pattern, $subject);
        }

        return $this->runtime->assert(
                new PatternExpectation($pattern),
                $subject,
                $message);
    }

    function expect_error($message = '%s') {
        $context = &SimpleTest::getContext();
        $queue = &$context->get('SimpleErrorQueue');
        $queue->expectError($this->runtime->_coerceExpectation($this->negate), $message);
    }
    
    function expect_exception($message = '%s') {
        $context = &SimpleTest::getContext();
        $queue = &$context->get('SimpleExceptionTrap');
        // :HACK: Directly substituting in seems to cause a segfault with
        // Zend Optimizer on some systems
        $line = $this->runtime->getAssertionLine();
        $queue->expectException($this->negate, $message . $line);
    }
}


?>