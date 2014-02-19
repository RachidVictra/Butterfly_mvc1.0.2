<?php

namespace Application\Errors;

/**
 * appError File : Class Error .
 *
 * @author Rachid
 */
class appError extends \Exception {

    private $msg = '';
    private $title = '';

    function __construct($title, $message) {
        $this->msg = $message;
        $this->title = $title;
    }

    public function getMsg() {
        return "<div style='margin:15px;text-align:left;-moz-border-radius: 4px;border-radius: 4px;background:#EFD2D2;border:1px solid #Fe2e2e;padding:15px;color:#Fe2e2e;font: normal 13px Calibri, Tahoma, sans-serif;'><b>$this->title</b> : <i>$this->msg</i></div>";
    }

}

?>
