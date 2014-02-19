<?php

require_once ROOT . 'Config/Application.php';
require_once ROOT . 'Config/Router.php';
require_once ROOT . 'Config/appController.php';
require_once ROOT . 'Config/appModel.php';
require_once ROOT . 'Config/Formhelper.php';

use App\Config\Application as App;

/**
 * Dispatcher File : 
 *
 * @author Rachid
 */
class Dispatcher {

    private $application;

    public function __construct() {
        $this->application = new App\Application;
        header('Content-Type: text/html; charset=' . $this->application->getConfigs('Encoding'));
        $this->routage();
        $app = get_class($this->application);
        $request = new Request();
        $app::$requestTab['url'] = $request->url;
        $app::$requestTab['cap'] = Router::parseUrl($request); //cap=>ControlActionParams
        $this->application->getConfigs('Startup_errors') ? error_reporting(-1) : error_reporting(1);
        $this->application->getConfigs('Session') ? session_start() : '';
        $this->loadController($app::$requestTab['cap']);
    }

    /**
     * Load Controller.
     * @param type $request
     */
    public function loadController($request) {
        $controllerName = $request['controller'];
        $actionName = $request['action'];
        $fileName = 'Controllers/' . $controllerName . '.Controller.php';
        try {
            if (file_exists($fileName)) {
                require_once 'Controllers/' . $controllerName . '.Controller.php';
                $classController = $controllerName . 'Controller';
                if (class_exists($classController)) {
                    $controllerInstance = new $classController();
                    if (method_exists($classController, $actionName)) {
                        $controllerInstance->$actionName();
                        if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                            $controllerInstance->viewPage($actionName);
                        } else if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
                            continue;
                    }
                    else
                        $this->application->setError($actionName, "Action not exist !");
                }
                else
                    $this->application->setError($classController, "This Class Controller $classController not exist in File Controllers/$controllerName.Controller.php !");
            }
            else
                $this->application->setError($controllerName, "This Controller $controllerName.Controller.php not exist in Directory Controllers/!");
        } catch (Exception $exc) {
             $this->application->setError("Error", $exc->getMessage());
        }
    }

    /**
     * # Routes Défintion
     */
    public function routage() {
        $nameRoute = '';
        $tabRoutes = array();
        $arrayRoute = file('Config/routes.yml', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($arrayRoute as $value) {
            if (substr($value, 0, 1) === '#' || substr($value, 0, 1) === '-' || count($value) === 0)
                continue;
            if (substr($value, 0, 1) !== ' ')
                $nameRoute = trim(str_replace(':', '', $value));
            if (substr($value, 0, 1) === ' ') {
                $route = explode(':', $value);
                if (trim(strtolower($route[0])) === 'pattern')
                    $tabRoutes[$nameRoute]['pattern'] = trim($route[1]);
                if (trim(strtolower($route[0])) === 'url') {
                    $url = explode('/', $route[1]);
                    $tabUrl = array();
                    for ($i = 0; $i < count($url); $i++) {
                        if ($i === 0)
                            $tabUrl['controller'] = trim($url[0]);
                        if ($i === 1)
                            $tabUrl['action'] = trim($url[1]);
                        if ($i === 2)
                            $tabUrl['id'] = trim($url[2]);
                    }
                    $tabRoutes[$nameRoute]['url'] = $tabUrl;
                }
                if (trim(strtolower($route[0])) === 'requis') {
                    $url = $tabRoutes[$nameRoute]['pattern'];
                    $requis = explode(';', $route[1]);
                    preg_match_all('/\{(.*?)\}/', $url, $m);
                    $slug = current($m[1]);
                    $syntaxe = $url;
                    for ($i = 0; $i < count($requis); $i++) {
                        $property = str_replace(' ', '', $requis[$i]);
                        $param = explode('=', $property);
                        $key = $param[0];
                        $val = $param[1];
                        $regex = '(?P<' . $key . '>' . $val . ')';
                        $slug = str_replace($key, $regex, $slug);
                        $syntaxe = str_replace($key, "[$val]+", $syntaxe);
                    }
                    $syntaxe = preg_replace("([{ }])", '', $syntaxe);
                    $tabRoutes[$nameRoute]['regex'] = "#$syntaxe#";
                    $tabRoutes[$nameRoute]['requis'] = "/$slug/";
                }
            }
        }

        foreach ($tabRoutes as $route) {
            $pattern = '';
            $url = '';
            $requis = '';
            $regex = '';
            if (key_exists('pattern', $route))
                $pattern = $route['pattern'];
            else
                $this->application->setError('Route - pattern', 'Pattern not set correctly in Route.yml!');
            if (key_exists('url', $route))
                $url = $route['url'];
            else
                $this->application->setError('Route - url', 'Url not set correctly in Route.yml!');
            if (key_exists('requis', $route))
                $requis = $route['requis'];
            if (key_exists('regex', $route))
                $regex = $route['regex'];
            if ($requis == '')
                Router::routes($pattern, $url);
            else
                Router::routes($pattern, $url, $requis, $regex);
        }
    }

}

class Request {

    public $url;

    public function __construct() {
        if (isset($_GET['url']))
            $this->url = $_GET['url'];
        else
            $this->url = '/';
    }

}

