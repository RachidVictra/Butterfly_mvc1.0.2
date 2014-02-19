<?php

/**
 * Router File : 
 *
 * @author Rachid
 */
class Router {

    static $tabRoutes = array();

    static function parseUrl($urlBase) {
        if (!empty($urlBase->url)) {
            $url = trim($urlBase->url);
            $params = explode('/', $url);
            $controller = ucfirst($params[1]);
            $action = !empty($params[2]) ? $params[2] : 'index';
            $param = array_slice($params, 3);

            $lstParams = array();
            if (!empty($param[0])) {
                $paramTab = explode(',', $param[0]);

                for ($i = 0; $i < count($paramTab); $i++) {
                    if (preg_match('/[\w+]+=[\w+]/', $paramTab[$i])) {
                        $ligne = explode('=', $paramTab[$i]);
                        $lstParams[$ligne[0]] = $ligne[1];
                    }else
                        new Application\Errors\appError('Parameter', "the parameters must be defined (param = value) <br> for multiple parameters is defined as  (param1 = val1, param2 = val2, ..., paramn = valn)");
                    
                }
            }

            $request = array('controller' => $controller, 'action' => $action, 'params' => $lstParams);
            if (key_exists($url, self::$tabRoutes)) {
                $request = self::$tabRoutes[$url]['request'];
            } else {
                foreach (self::$tabRoutes as $key => $value) {
                    if (!empty($value['regexRegex'])) {
                        $slugUrl = explode('/', $url);
                        $slug = $slugUrl[count($slugUrl) - 1];
                        $urlMap = $value['request'];
                        $match = '';
                        $regex = $value['regexRegex'];
                        $regexVerf = $value['regexVerf'];
                        if (preg_match($regexVerf, $url)) {
                            if (preg_match($regex, $slug, $match)) {
                                $i = 0;
                                foreach ($match as $key => $value) {
                                    if ($i == 0) {
                                        unset($param);
                                        $i = 1;
                                    }
                                    $param[$key] = $value;
                                }
                                $controller = $urlMap['controller'];
                                $action = $urlMap['action'];
                                $request = array('controller' => $controller, 'action' => $action, 'params' => $param);
                            }
                        }
                    }
                }
            }
            return $request;
        }
    }

    static function routes($url, $request = array(), $pass = NULL, $regex = NULL) {
        self::$tabRoutes[$url] = array('request' => $request, 'regexRegex' => $pass, 'regexVerf' => $regex);
    }

}
