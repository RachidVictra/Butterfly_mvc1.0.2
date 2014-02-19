<?php

namespace App\Config\Controllers;

use App\Config\Application as App;

/**
 * AppController File : principal Controller
 *
 * @author Rachid
 */
class AppController extends App\Application {

    public $request;
    public $controller = '';
    protected $url;
    protected $form;

    public function __construct() {
        $this->html = new App\Html();
        $this->models[] = 'Model';
        $this->loadModel($this->models);
        $this->request = parent::$requestTab['cap']; //cap=>ControlActionParams
        $this->controller = $this->request['controller'];
        $this->url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * The corresponding view to action | Display the last view setting $view
     * @param string $view
     */
    public function viewPage($view = NULL) {
        if (!isset($view))
            $view = $this->request['action'];
        $page = 'Views/' . $this->controller . '/' . $view . '.vph';
        $this->view = $page;
        if (file_exists($page)) {
            ob_start();
            require ROOT . $page;
            $content = ob_get_contents();
            ob_end_clean();
            $this->getLayout("\n\t$content", $this->title_for_layout);
        } else {
            $this->setError($page, "This view $view not exist !");
        }
    }

    /**
     * Verify the existence of parameter.
     * @param type $param
     * @return boolean
     */
    public function isParamExist($param) {
        if (key_exists('params', $this->request)) {
            if (key_exists($param, $this->request['params']))
                return TRUE;
        }
        else
            return FALSE;
    }

    /**
     * Retrieve parameters from the url.
     * @param type $param
     * @return type
     */
    public function getParam($param) {
        if ($this->isParamExist($param))
            return $this->request['params'][$param];
        else
            $this->setError('Parameter', "This param $param=value not posted in URL !");
    }

    /**
     * Verification form is posted.
     * @return boolean
     */
    public function isFormPosted() {
        if ($this->form->isPost())
            return TRUE;
        else
            return FALSE;
    }

     /**
      * Verifies that the data are posted by Ajax
      * @return boolean
      */
    public function isAjaxPosted() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET')
            return TRUE;
        return FALSE;
    }

     /**
      * Verifies that the parameter exists in the data posted
      * @return boolean
      */
    public function isAjaxParamExist($param) {
        if (key_exists($param, $_POST) || key_exists($param, $_GET))
            return TRUE;
        return FALSE;
    }

     /**
      * Returns the value
      * @return type
      */
    public function getAjaxParam($param) {
        if (key_exists($param, $_POST))
            return $_POST[$param];
        else if (key_exists($param, $_GET))
            return $_GET[$param];
        return NULL;
    }

    /**
     * Toggles content in a variable used in the view.
     * @param type $key
     * @param type $value
     */
    public function set($key, $value = NULL) {
        $this->$key = $value;
    }

    /**
     * 
     * @param type $model
     */
    public function loadModel($models = array()) {
        if (!empty($models)) {
            foreach ($models as $value) {
                if ($value === 'Model') {
                    $this->$value = new \App\Config\Models\AppModel();
                    continue;
                }
                $file = 'Models/' . $value . '.Model.php';
                $filename = ROOT . 'Models/' . $value . '.Model.php';
                $class = $value . 'Model';
                if (!file_exists($filename)) {
                    $table = $this->getTable($value);
                    if ($this->getConnection())
                        if (!$this->pdo->query("SHOW TABLES LIKE '$table'")) {
                            $this->setError('Table', "This table $table not exists in Database");
                            exit();
                        }
                    $fileOpen = fopen($filename, 'w') or $this->setError("Model", "This Model '$value' not exist ! ");
                    $modelSkeleton = str_replace('className', $class, file_get_contents('Config/modelSkeleton'));
                    fwrite($fileOpen, $modelSkeleton);
                    fclose($fileOpen);
                }
                require_once $filename;
                if (class_exists($class))
                    $this->$value = new $class;
                else
                    $this->setError("Class", "This Class '$class' not exist In $file! ");
            }
        }
    }

    /**
     * Write Session
     * @param type $args
     * @param type $value
     */
    public function writeSession($args, $value) {
        if (isset($args) && !empty($args) && isset($value) && !empty($value))
            $_SESSION[$args] = $value;
        else
            $this->setError('Write in Session', "the parameters of writeSession is not correctly defined");
    }

    /**
     * Read Session
     * @param type $args
     * @return boolean
     */
    public function readSession($args) {
        if (isset($args) && !empty($args)) {
            if (key_exists($args, $_SESSION))
                return $_SESSION["$args"];
        }
        else
            $this->setError('Read Session', "the parameter of readSession is not correctly defined");
        return false;
    }

    /**
     * Session destroy
     * @param type $session
     */
    public function destructSession($session) {
        unset($session);
    }

    /**
     * redirectUrl($link) 
     * @param type $url
     */
    public function redirectUrl($url) {
        if (!preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $url))
            $url = $this->parserUrl($url);
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        } else {
            $this->setError('Redirection', "Cannot redirect, for now please click this <a " . "href=\"$url\">link</a> instead\n");
            exit;
        }
    }

    /**
     * redirect(controller, action, id)
     * @param type $controller
     * @param type $action
     * @param type $id
     */
    public function redirect($controller, $action = null, $id = array()) {
        $url = '';
        $url .= "$controller";
        if (isset($action)) {
            $url .= "/$action";
            if (isset($id)) {
                $first = true;
                foreach ($id as $key => $value) {
                    if ($first) {
                        $url .= "/$key=$value";
                        $first = false;
                    }
                    else
                        $url .= ",$key=$value";
                }
            }
        }
        $this->redirectUrl($url);
    }

    /**
     * Add Elements in Controllers
     * @param string $elementName
     */
    public function setElement($elementName) {
        $fileName = 'Views/Elements/' . $elementName . '.element';
        if (file_exists($fileName)) {
            ob_start();
            include_once $fileName;
            $this->elementUnit .= ob_get_contents();
            ob_end_clean();
        }
        else
            $this->setError("Element not Exist", "This element '$fileName' not Exist");
    }

    /**
     * Display a Element as View(Specified in a Controller Or a View)
     * @param type $elementName
     */
    public function displayOnlyElement($elementName) {
        $fileName = 'Views/Elements/' . $elementName . '.element';
        if (file_exists($fileName)) {
            ob_start();
            include_once $fileName;
            $this->elementUnit = ob_get_contents();
            ob_end_clean();
        }
        else
            $this->setError("Element not Exist", "This element '$fileName' not Exist");
        die($this->elementUnit);
    }

}


