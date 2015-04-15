<?php
/**
 * Comments controller
 *
 * @package CommentsController
 * @author  Marco Solari <marcosolari@gmail.com>
 *
 */

# TODO: use Utilities getUrlContents WITH charset !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

require_once "lib/simple_html_dom.php";
require_once "classes/services/Utilities.php";

class CommentsController {

  /**
   * Constructor
   */
  function __construct($router) {
    require_once "setup/comments.php"; // comments setup
  }

}