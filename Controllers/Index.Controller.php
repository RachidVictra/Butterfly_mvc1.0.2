<?php

use App\Config\Controllers as Controller;

class IndexController extends Controller\AppController {

    public function index() {
        //This index Action ...
		$this->title_for_layout = 'Butterfly 1.0.2';
    }

    //end controller
}

