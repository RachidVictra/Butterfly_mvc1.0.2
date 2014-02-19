<?php

namespace App\Config\Application;

use \PDO;
use \PDOException;

/**
 * Application File : 
 *
 * @author Rachid
 */
class Application {

    public $metaData = array();
    public $title_for_layout = '';
    public $content_for_layout = '';
    public $script_for_layout = '';
    public $layout;
    public $models = array();
    protected $pdo;
    protected $html = '';
    protected $nbOfSimultaneousConnections;
    protected $mode = '';
    protected $view = '';
    protected $elementUnit = '';
    static $requestTab = array();
    static $sql_dump = array();
    private $errorTrace = array();

    /**
     * Layout method get
     * @param type $content_layout
     * @param type $title_layout
     */
    public function getLayout($content_for_layout = NULL, $title_for_layout = NULL) {
        $template = $this->getTemplate();
        if (!$template)
            die("This Layout $this->layout is not definied !");
        else {
            $this->title_for_layout = $title_for_layout;
            $this->content_for_layout = $content_for_layout;
            $this->html = new Html();
            $script = Html::$script;
            $this->script_for_layout = "<script type='text/javascript'>$script</script>";

            $this->element();

            require_once $template;
        }
    }

    private function getTemplate() {
        if (empty($this->layout))
            $this->layout = $this->getConfigs('DefaultLayout');
        $template = 'Layouts/' . $this->layout . '.vph';
        if (file_exists($template))
            return $template;
        else
            return 0;
    }

    /**
     * Set Message Flash
     * @param type $msg
     * @param type $attributs
     */
    public function setFlash($msg, $atributs = array()) {
        $atribut = '';
        foreach ($atributs as $key => $value)
            $atribut .= "$key = '$value' ";
        $this->getConfigs('Session') ? '' : session_start();
        $_SESSION['flashMsg'] = "<div $atribut> $msg </div>";
        session_write_close();
    }

    /**
     * Get Message Flash
     * @return type
     */
    public function flash() {
        $msg = '';
        if (isset($_SESSION) && isset($_SESSION['flashMsg'])) {
            $msg = $_SESSION['flashMsg'];
            unset($_SESSION['flashMsg']);
        }
        return $msg;
    }

    /**
     * Return the Meta Data
     * @return type
     */
    public function getMetaData() {
        $meta = '';
        $listMetaData = array('description', 'keywords', 'author', 'reply-to', 'copyright', 'identifier-url', 'revisit-after', 'language', 'robots');
        $i = 1;
        foreach ($this->metaData as $key => $value) {
            if (in_array(strtolower($key), $listMetaData)) {
                if (count($this->metaData) == $i)
                    $meta .= "<META NAME='$key' CONTENT='$value'>\n";
                else
                    $meta .= "<META NAME='$key' CONTENT='$value'>\n\t";
                $i++;
            }
            else
                $this->setError('MetaData', "This name of key $key not defined !");
        }
        return $meta;
    }

    /**
     * Specifie the error when it found
     * @param type $title
     * @param type $message
     */
    public function setError($title, $message) {
        $error = new appError($title, $message);
        if ($this->getMode() == 0) {
            $this->errorTrace['file'] = $error->getFile();
            $this->errorTrace['line'] = $error->getLine();
            $this->errorTrace['message'] = $message;
            $this->errorTrace['trace'] = $error->getTraceAsString();
            if ($this->getTemplate())
                $this->getLayout($error->getMsg(), $title);
        } else {
            $this->getPage404();
        }
    }

    /**
     * Get Connection with Server database
     * @throws \Exception
     */
    public function getConnection() {
        if (!isset($this->pdo)) {
            $host = $this->getConfigs('Hote');
            $user = $this->getConfigs('User');
            $password = $this->getConfigs('Password');
            $db_name = $this->getConfigs('Database');
            $driver = $this->getConfigs('driver');
            $dnsString = '';

            if ($driver === 'sqlite') {
                $database = "Data/$db_name.db";
                if (!file_exists(dirname($database)))
                    mkdir(dirname($database), 0777, true);
                new \SQLite3($database);
                $dnsString = "sqlite:/$db_name";
            }else {
                $dnsString = "$driver:host=$host;dbname=$db_name";
            }

            try {
                $this->pdo = new PDO($dnsString, $user, $password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if (!isset($this->nbOfSimultaneousConnections) && $driver != 'sqlite') {
                    $result = $this->pdo->query("SHOW STATUS");

                    foreach ($result->fetchAll() as $row)
                        if ($row['Variable_name'] === 'Max_used_connections') {
                            $this->nbOfSimultaneousConnections = $row['Value'];
                            break;
                        }
                }
                return 1;
            } catch (PDOException $e) {
                $this->setError("Error Db - ", $e->getMessage());
            }
        }
        else
            return 1;
    }

    protected function refreshConnection() {
        unset($this->pdo);
        $this->getConnection();
    }

    /**
     * Ecover name of table
     */
    protected function getTable($model) {
        return (strtolower(substr($model, -1)) === 'y') ?
                str_replace('y', 'ies', strtolower($model)) : strtolower($model) . 's';
    }

    public function getConfigs($index) {
        foreach (\parse_ini_file("config.ini") as $key => $value) {
            if (trim(strtolower($key)) === trim(strtolower($index)))
                return $value;
        }
    }

    /**
     * Get Page not Found
     */
    public function getPage404() {
        $this->html = new Html();
        $viewPage = 'Views/error/404.vph';
        if (file_exists($viewPage)) {
            require_once $viewPage;
            die();
        } else {
            header("HTTP/1.1 404 Not found");
            //header("Status: 404 Not found");
        }
    }

    public function getMode() {
        $this->mode = ($this->getConfigs('mode') === 'developement') ? 0 : 1;
        return $this->mode;
    }

    protected function element() {
        return $this->elementUnit;
    }

    /**
     * Debuger
     */
    public function debug() {
        if ($this->getMode() === 0 && $this->getConfigs('debuger') == TRUE) {
            $debug = new Debug(self::$sql_dump, self::$requestTab, $this->errorTrace, $this->models, $this->layout, $this->view);
            $debug->display();
        }
    }

    public function parserUrl($url) {
        if (!preg_match('#^(https?|ftp)://#', $url)) {
            $host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
            $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") ? 'https' : 'http';
            $port = (isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80);
            $uri = $proto . '://' . $host;
            if ((('http' == $proto) && (80 != $port)) || (('https' == $proto) && (443 != $port))) {
                // do not append if HTTP_HOST already contains port
                if (strrchr($host, ':') === false) {
                    $uri .= ':' . $port;
                }
            }

            if (strtolower($_SERVER['SERVER_NAME']) === 'localhost') {
                $rqtUri = explode('/', $_SERVER['REQUEST_URI']);
                $url = $uri . '/' . $rqtUri[1] . '/' . ltrim($url, '/');
            }
            else
                $url = $uri . '/' . ltrim($url, '/');
        }
        return $url;
    }

    public function loadLib($lib_name = NULL) {
        if (is_dir('Libs/' . $lib_name)) {
            $dirname = 'Libs/' . $lib_name;
            $return = $this->findLib($dirname, $lib_name);
            ($return !== 0) ? include_once $return : $this->setError('Load Lib', "This Library $lib_name not exist in Libs");
        } else {
            $return = $this->findLib('Libs/', $lib_name);
            ($return !== 0) ? require_once $return : $this->setError('Load Lib', "This Library $lib_name not exist in Libs");
        }
    }

    private function findLib($dirname, $lib_name) {
        $der = opendir($dirname);
        while ($inDer = readdir($der)) {
            if ($inDer != '.' && $inDer != '..' && $inDer != 'index.php') {
                $filename = "$dirname/$inDer";
                if (is_file($filename)) {
                    if (preg_match("/$lib_name.(php|class.php)$/i", $inDer)) {
                        return $filename;
                    }
                } else if (is_dir($filename)) {
                    $this->findLib($filename, $lib_name);
                }
            }
        }
        closedir($der);
        return 0;
    }

}

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

/**
 * appError File : Class hTML .
 *
 * @author Rachid
 */
class Html extends Application {

    static $script;

    /**
     * Html : Script Javascript
     * @param type $path
     * @return string
     */
    public function js($path = NULL) {
        $js = "<script type='text/javascript' src='" . WEBROOT . $path . "'></script>\n";
        return $js;
    }

    /**
     * Html : Css link
     * @param type $path
     * @param type $atributs
     * @return string
     */
    public function css($path = NULL, $atributs = array()) {
        $atribut = '';
        foreach ($atributs as $key => $value)
            $atribut .= "$key = '$value' ";
        $css = "<link $atribut href='" . WEBROOT . $path . "' />\n";
        return $css;
    }

    /**
     * Html : link
     * @param type link
     * @param type $args
     * @param type $atributs
     * @return type
     */
    public function link($link = NULL, $args = array(), $atributs = array()) {
        $path = '';
        $atribut = '';
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                if (trim(strtolower($key)) === 'controller')
                    $path.="$value";
                if (trim(strtolower($key)) === 'action')
                    $path.="/$value";
                if (trim(strtolower($key)) === 'params') {
                    $first = true;
                    $params = $value;
                    if (is_array($value)) {
                        $params = '/';
                        foreach ($value as $key => $val) {
                            if ($first) {
                                $params.="$key=$val";
                                $first = false;
                            }
                            else
                                $params.=",$key=$val";
                        }
                    }
                    else
                        $params = "/$params";

                    $path .= $params;
                }
            }
        }else {
            $path = $args;
        }

        if (count($args) == 0)
            $url = 'javascript:';
        else
            $url = $this->parserUrl($path);

        foreach ($atributs as $key => $value) {
            $atribut .= "$key = '$value' ";
        }

        return "<a href='$url' $atribut>$link</a>\n";
    }

    /**
     * Html : img
     * @param type $title
     * @param type $arg
     * @return type
     */
    public function img($title = NULL, $arg = array()) {
        $atributs = '';
        foreach ($arg as $key => $value) {
            if (strtolower($key) === 'src')
                $atributs .= $key . "=" . WEBROOT . $value . " ";
            else
                $atributs .= "$key = '$value' ";
        }
        return "<img title='$title' $atributs></img>\n";
    }

    /**
     * Html : Block the script 'Start'
     */
    public function startScript() {
        ob_start();
    }

    /**
     * Html : Block the script 'End'
     */
    public function endScript() {
        self::$script = ob_get_contents() . "\n";
        ob_end_clean();
    }

}

/**
 * Debug Class
 * 
 */
class Debug extends \Exception {

    private $sql;
    private $request;
    private $trace;
    private $layout;
    private $model;
    private $view;

    public function __construct($sql, $request, $errorTrace, $models, $layout, $view) {
        unset($models[count($models) - 1]);
        isset($sql) ? $this->sql = $sql : $this->sql = array();
        isset($request) ? $this->request = $request : $this->request = array();
        isset($errorTrace) ? $this->trace = $errorTrace : $this->trace = array();
        isset($models) ? $this->model = $models : $this->model = array();
        isset($layout) ? $this->layout = $layout : $this->layout = '';
        isset($view) ? $this->view = $view : $this->view = '';
    }

    public function display() {
        $contentSql = '';
        if (!empty($this->sql)) {
            $contentSql.= '<table class="_debugTableSql_" style="border-collapse: collapse;width:99%;text-align:center;font: normal 12px Calibri, Tahoma, sans-serif;"><tr style="color: #fff;background: #74A846;height: 20px;"><th>Request</th><th>Error</th><th>Execution Time</th><th>Affected</th></tr>';
            $i = 0;
            foreach ($this->sql as $value) {
                $contentSql .= '<tr class="' . (($i % 2) ? 'row-a' : 'row-b') . '"><td style="text-align:left">' . $value['request'] . '</td><td>' . $value['error'] . ' </td><td>' . $value['time'] . ' ms</td><td>' . $value['resultat'] . '</td></tr>';
                $i++;
            }
            $contentSql .= '</table>';
        }
        $lstModels = implode(', ', $this->model);
        $requestDetail = '';
        $detailrequest = '';
        $sqlDetail = '';
        $errorsDetail = '';

        if (!empty($this->request['cap'])) {
            $detailrequest = "<ul>";
            foreach ($this->request['cap'] as $key => $value) {
                if ($key == 'params') {
                    $i = 0;
                    $detailrequest .= "<li>[$key] => array(" . count($value) . ")</li><li><ul>";
                    foreach ($value as $k => $val) {

                        $detailrequest .= "<li>[$k] => $val</li>";
                        $i++;
                    }
                    $detailrequest .= "</ul></li>";
                }
                else
                    $detailrequest .= "<li>[$key] => $value</li>";
            }
            $detailrequest .= "</ul>";
        }

        $requestDetail = "<dl>
            <dt><h4>Layout : </h4></dt><dd>" . $this->layout . "</dd>
            <dt><h4>Request : </h4></dt><dd>[url] : " . $_SERVER['REQUEST_URI'] . "</dd>
            <dd>$detailrequest</dd>
            <dt><h4>View : </h4></dt><dd>" . $this->view . "</dd>    
            </dl>";
        $sqlDetail = "<dl>
            <dt><h4>Models Loaded (" . count($this->model) . ") : </h4></dt><dd>$lstModels</dd>
            <dt><h4>Sql Requests : </h4></dt><dd>$contentSql</dd>
            </dl>";

        if (!empty($this->trace)) {
            $errorsDetail .= "<dl><dt><h4>Message : </h4></dt><dd>" . $this->trace['message'] . "</dd>";
            $errorsDetail .= "<dt><h4>File : </h4></dt><dd>" . $this->trace['file'] . "</dd>";
            $errorsDetail .= "<dt><h4>Line : </h4></dt><dd>" . $this->trace['line'] . "</dd>";
            $errorsDetail .= '</dl><h4>Trace : </h4><table class="_debugTableSql_" style="border-collapse: collapse;width:99%;text-align:center;font: normal 12px Calibri, Tahoma, sans-serif;"><tr style="color: #fff;background: #74A846;height: 20px;"><th>Files</th><th>Fonctions</th></tr>';
            $trace = explode('#', $this->trace['trace']);
            for ($i = 1; $i < count($trace) - 1; $i++) {
                $line = explode(':', $trace[$i]);
                $errorsDetail .= '<tr class="' . (($i % 2) ? 'row-a' : 'row-b') . '" style="text-align:left"><td>' . $line[0] . $line[1] . '</td><td>' . $line[2] . '</td></tr>';
            }
            $errorsDetail .= '</table>';
        }

        $debug = "<style>#_debugBFmvc_ table td h3{color:#FFbb55}#_debugBFmvc_ table td dt h4{color:#FF55BA} ._debugTableSql_ tr.row-a{background: #F8F8F8;} ._debugTableSql_ tr.row-b{background: #EFEFEF;}#_debugBFmvc_ ul, ol, dl {margin: 10px 15px;padding: 0 15px;}#_debugBFmvc_ li, dd {padding: 0 30px;}</style>
            <div id='_debugBFmvc_' style='width:auto;margin:auto;padding:auto;text-align:center;'>
            <table style='text-align:left;border:2px solid #2e2e2e; border-collapse:collapse;font: normal 12px Calibri, Tahoma, sans-serif;width:750px;margin:30px auto auto auto;'>
            <tr><td colspan='2' style='vertical-align:bottom;background:#2e2e2e'><h3 style='margin:0;font-family: Georgia;font-size:20px;'><img style='vertical-align:middle; width:50px' src='http://imagesup.org/images12/1388947460-bficon.png' /> <span style='color:#00A2E8'>Butterfly</span> <span style='font-style:italic;color:#95ca05;'>MVC </span> v 1.0.2 - Debugger</h3></td></tr>
            <tr style='background:#F8F8F8;border:2px solid #2e2e2e;padding:0'>
                <td style='font-weight:bolder;font-size:14px;width:120px;text-align:center;'>Infos</td>
                <td><p><h2><img style='vertical-align:middle; width:40px' src='http://imagesup.org/images12/1388947166-bf.png' /> Butterfly <span>MVC </span> v 1.0.2</h2>
                    is a lightweight framework based on MVC architecture, realized by <span style='font-style:italic;color:#95ca05;'>Rachid LAJALI</span>
                    <span style='font-weight:bolder;'>&copy; 2013 - LAJALI Rachid</span>
                    </p>
                    <h3>Php Version</h3>
                    <p>
                        <ul>
                            <li>Current version of PHP : " . phpversion() . "</li>
                            <li>BF requires version of PHP and later : 5.3.x</li>
                        </ul>
                    </p>
                </td></t>
            <tr style='background:#D2D2EF;border:2px solid #2e2e2e;'>
                <td style='font-weight:bolder;font-size:14px;width:120px;text-align:center;color:#3232EF'>Request</td>
                <td><h3>Request details</h3>
                    <p>$requestDetail</p>
                </td>
            </tr>
            <tr style='background:#D2EFD2;border:2px solid #2e2e2e;'>
                <td style='font-weight:bolder;font-size:14px;width:120px;text-align:center;color:#32EF32'>Database</td>
                <td><h3>Database Interaction</h3>
                    <p>$sqlDetail</p>
                </td>
            </tr>
            <tr style='background:#EFD2D2;border:2px solid #2e2e2e;'>
                <td style='font-weight:bolder;font-size:14px;width:120px;text-align:center;color:#EF3232'>Errors</td>
                <td><h3>Error(s)</h3>
                    <p>$errorsDetail</p>
                </td>
            </tr>";
        $debug .= "</table></div>";
        echo $debug;
    }

}
