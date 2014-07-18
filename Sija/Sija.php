<?php
/**
 * sija framework.
 * 
 * @package sija-framework
 * @author Alex Chermenin <alex@chermenin.ru>
 */

namespace Sija;

use ActiveRecord, Exception;

/**
 * Init general autoload class.
 * 
 * @param string $class_name
 */
function sija_autoloader($class_name) {
    if (strpos($class_name, '\\') !== false) {
        $namespaces = explode('\\', $class_name);
        $class_name = array_pop($namespaces);
    }
    if (isset($namespaces)) {
        $class_name = implode($namespaces, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $class_name;
    }
    $filename = __DIR__ . "/../$class_name.php";
    if (file_exists($filename)) {
        require_once($filename);
    }
}
spl_autoload_register('Sija\sija_autoloader');

/**
 * Sija general class.
 */

class Sija {

    /**
     * Constructor.
     *
     * @return Sija
     */
    public function __construct() {

        // Init sessions.
        session_start();

        // Init debug mode.
        error_reporting(Config::$debug ? E_ALL : 0);

        // Init Active Record.
        ActiveRecord\Config::initialize(function($cfg)
        {
            $cfg->set_connections(Config::$connections);
            $cfg->set_default_connection(Config::$connection);
        });
    }

    /**
     * General executor.
     *
     * @param array $options
     * @return string
     */
    public function execute($options = array()) {

        // Parse only AJAX requests.
        if(!Config::$debug && (
            !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
        ) {
            header('HTTP/1.1 500 Internal server error');
            $response_obj = Response::create(500, "This API allow only AJAX requests.", $_SERVER['HTTP_ACCEPT']);
            return $response_obj->render();
        }

        // Parse incoming request info.
        $request = new Request();

        // Parse path elements.
        if (isset($options['path']) || isset($_SERVER['PATH_INFO'])) {
            $request->url_elements = explode('/', trim(isset($options['path']) ? $options['path'] : $_SERVER['PATH_INFO'], '/'));
        }

        // Parse request method & parameters
        $request->method = strtoupper(isset($options['method']) ? $options['method'] : $_SERVER['REQUEST_METHOD']);
        $request->parameters = null;
        if (!isset($options['method'])) {
            switch ($request->method) {
                case 'GET': $request->parameters = (object) $_GET; break;
                case 'POST': $request->parameters = (object) $_POST; break;
            }
        }
        if (isset($options['parameters']) && (is_array($options['parameters']) || is_object($options['parameters']))) {
            $request->parameters = is_array($options['parameters']) ? (object) $options['parameters'] : $options['parameters'];
        }

        // Parse incoming data.
        if (isset($options['json'])) {
            $request->json = is_object($options['json']) ? $options['json'] : json_decode($options['json']);
        } else {
            $request_data = file_get_contents('php://input');
            $request->json = json_decode($request_data);
        }

        // Route the request.
        if (!empty($request->url_elements)) {
            $controller_name = 'Sija\\Controllers\\' . ucfirst($request->url_elements[0]) . 'Controller';
            if (class_exists($controller_name)) {
                $controller = new $controller_name;
                $action_name = strtolower($request->method);
                try {
                    $response_status = 200;
                    $response_data = json_decode(call_user_func_array(array($controller, $action_name), array($request)));
                } catch (Exception $e) {
                    $response_status = $e->getCode();
                    $response_data = $e->getMessage();
                }
            }
            else {
                header('HTTP/1.1 404 Not Found');
                $response_status = 404;
                $response_data = 'Unknown request: ' . $request->url_elements[0];
            }
        }
        else {
            header('HTTP/1.1 500 Internal server error');
            $response_status = 500;
            $response_data = 'Unknown request';
        }

        // Return response
        $response_obj = Response::create($response_status, $response_data, $_SERVER['HTTP_ACCEPT']);
        return $response_obj->render();
    }

}








