<?php

namespace Symnext\Routing;

use Symnext\Toolkit\XMLDocument;
use Symnext\Toolkit\XMLElement;

class RouteCompiler
{
    private static $routes = [];

    public static function compileRoutes(): array
    {
        $files = [
            \Symnext\AdminUI\SRC . '/routes.xml',
            WORKSPACE . '/routes.xml'
        ];
        foreach ($files as $file) {
            $doc = new XMLDocument();
            $doc->load($file);
            self::compileRouteSet(
                $doc->documentElement,
                '/',
                [
                    'dir' => VIEW_TEMPLATES,
                    'class' => 'View',
                    'namespace' => 'Symnext\Toolkit'
                ]
            );
        }
        #print_r(self::$routes); die;
        return self::$routes;
    }

    private static function compileRouteSet(
        XMLElement $base_node,
        string $group_path,
        array $params
    )
    {
        global $Config;

        $dir = $params['dir'] ?? null;
        $group_path = str_replace('//', '', $group_path);
        foreach ($base_node->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) continue;
            $tag_name = $node->nodeName;
            #echo "Tag: $tag_name\n";
            $attrs = $node->getAttributes();
            $namespace = $attrs['namespace'] ?? $params['namespace'];
            $path = $attrs['path'] ?? null;
            if ($tag_name == 'group') {
                $sub_path = $path;
                self::compileRouteSet(
                    $node,
                    $group_path . '/' . $path,
                    [
                        'dir' => $attrs['dir'] ?? $params['dir'],
                        'namespace' => $namespace
                    ]
                );
                continue;
            }
            $method = null;
            switch ($tag_name) {
                case 'route':
                    $method = 'all';
                    break;
                case 'get':
                    $method = 'get';
                    break;
                case 'post':
                    $method = 'post';
                    break;
            }
            if (!$method) continue;

            $regexp = preg_replace_callback(
                '/(\$\w+)/',
                function ($matches) use ($attrs) {
                    $param_name = trim($matches[1], '$');
                    $filter = $attrs['filter.' . $param_name] ?? '[^\\/]+';
                    return "(?<$param_name>$filter)";
                },
                $path
            );
            #echo $regexp . ' .. ';
            $route_data = [
                'regexp' => $regexp,
                'method' => $method
            ];
            if (isset($attrs['view'])) {
                $route_data['view'] = $dir . '/view.' . $attrs['view'] . '.xsl';
            }
            if (isset($attrs['class'])) {
                $route_data['class'] = trim(
                    $namespace . '\\' . $attrs['class'], '\\'
                );
            }
            self::$routes[$group_path][] = $route_data;
        }
    }
}
