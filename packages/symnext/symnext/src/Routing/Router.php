<?php

namespace Symnext\Routing;

class Router
{
    private $last_match;

    public function run(
        string $path_given,
        string $method_given = null
    )
    {
        global $Params;

        $route_table = RouteCompiler::compileRoutes();
        #print_r($route_table); die;
        #$ignores = array('GET' => 'route post', 'POST' => 'route get');
        #$ignore = $ignores[$_SERVER['REQUEST_METHOD']];

        $matches = null;
        foreach ($route_table as $group_regexp => $route_group) {
            $route_remainder = null;
            if ($group_regexp != '/') {
                $group_regexp = str_replace('/', '\\/', $group_regexp);
                if (preg_match("/^$group_regexp\\/(.+)/", $path_given, $matches) == 1) {
                    $route_remainder = $matches[1];
                }
            } else {
                $route_remainder = $path_given;
            }
            if (!$route_remainder) continue;
            foreach ($route_group as $route_data) {
                $regexp = $route_data['regexp'];
                $regexp = ($regexp == '/') ? '' : $regexp;
                if (preg_match(
                    '|^' . $regexp . '$|', $route_remainder, $matches
                ) == 1 and $route_data['method'] == $method_given) {
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    $last_match = [];
                    /*if (isset($route_data['view'])) {
                        $view_file = $route_data['view'] . '.xsl';
                        if (!is_file($view_file)) {
                            echo "View <code>$view_file</code> not found.";
                            die;
                        }
                        $last_match['view'] = $view_file;
                    }*/
                    if (isset($route_data['view'])) {
                        $last_match['view'] = $route_data['view'];
                    }
                    if (isset($route_data['class'])) {
                        $last_match['class'] = $route_data['class'];
                    }

                    // Query string
                    $query_string = server_safe('QUERY_STRING');
                    if ($query_string) {
                        \parse_str($query_string, $query_params);
                        if (!empty($query_params)) {
                            foreach ($query_params as $key => $value) {
                                $params['query-' . $key] = $value;
                            }
                        }
                    }


                    if (!empty($params)) {
                        $last_match['params'] = $params;
                        $Params = $params;
                    }
                    $this->last_match = $last_match;
                    return true;
                }
            }
        }
        return false;
        #header('Location:' . $route_matched, true, (int) $action_type[1]);
    }

    public function getLastMatch(): array
    {
        #var_dump($this->last_match);die;
        return $this->last_match;
    }
}
