<?php
/**
 * @package  api-framework
 * @author   Martin Bean <martin@martinbean.co.uk>
 * @abstract
 */
abstract class AbstractController {

    /**
     * GET method
     * 
     * @param  Request $request
     * @return string
     */
    public function get($request) { return ""; }

    /**
     * POST action
     *
     * @param  $request
     * @return null
     */
    public function post($request) { return null; }

    protected function debug($msg) {
        print "Debug: " . $msg . "\n";
    }

    protected function info($msg) {
        print "Info: " . $msg . "\n";
    }

    protected function warning($msg) {
        print "Warning: " . $msg . "\n";
    }

    protected function error($msg) {
        print "Error: " . $msg . "\n";
    }

}