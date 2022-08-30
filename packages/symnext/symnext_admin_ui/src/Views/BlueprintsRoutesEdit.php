<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\XMLWriter2;
use XMLReader;

class BlueprintsRoutesEdit extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-routes-edit.xsl';
    }

    protected function beforeGenerate()
    {
        if (empty($_POST)) return;

        $meta = $_POST['meta'];
    }

    protected function buildView(): void
    {
        global $Params;

        $route_num = $Params['route_num'] ?? null;

        if (isset($_POST['route'])) {
            #var_dump($_POST['route']); die;
            $reader = new XMLReader();
            $reader->open(\WORKSPACE . '/routes.xml');
            $routes = [];
            $route = null;
            while ($reader->read()) {
                if ($reader->nodeType == XMLReader::ELEMENT) {
                    if ($reader->depth == 0 and $reader->name !== 'routes') {
                        exit("Invalid routes file.");
                    }
                    if ($reader->depth == 1 and
                        in_array($reader->name, ['route', 'redirect'])) {
                            $path = $reader->getAttribute('path');
                            $method = $reader->getAttribute('method') ?? 'any';
                            $routes[] = [
                                'type' => $reader->name,
                                'path' => $path,
                                'method' => $method
                            ];
                            $route_ref = &$routes[count($routes) - 1];
                            switch ($reader->name) {
                                case 'route':
                                    $route_ref['view']
                                        = $reader->getAttribute('view');
                                    break;
                                case 'redirect':
                                    $route_ref['destination'] =
                                        $reader->getAttribute('destination');
                                    break;
                            }
                    }
                    if ($reader->depth == 2 and $reader->name == 'filter') {
                        if (!isset($route_ref['filters'])) {
                            $route_ref['filters'] = [];
                        }
                        $route_ref['filters'][] = [
                            'param' => $reader->getAttribute('param'),
                            'regex' => $reader->getAttribute('regex')
                        ];
                    }
                }
            }
            $reader->close();
            if ($route_num and isset($routes[$route_num - 1])) {
                $routes[$route_num - 1] = $_POST['route'];
            } else {
                $routes[] = $_POST['route'];
            }
            #var_dump($routes); die;

            // Save routes.
            $writer = new XMLWriter2();
            $writer->openUri(\WORKSPACE . '/routes.xml');
            $writer->setIndent(true);
            $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('routes');
            foreach ($routes as $route) {
                $writer->startElement($route['type'] ?? 'route');
                $writer->writeAttribute('path', $route['path']);
                if (isset($route['view'])) {
                    $writer->writeAttribute('view', $route['view']);
                } elseif (isset($route['destination'])) {
                    $writer->writeAttribute('destination', $route['destination']);
                }
                $writer->writeAttribute('method', $route['method'] ?? 'any');
                if (isset($route['filters'])) {
                    foreach ($route['filters'] as $filter) {
                        $writer->startElement('filter');
                        $writer->writeAttribute('param', $filter['param']);
                        $writer->writeAttribute('regex', $filter['regex']);
                        $writer->endElement();
                    }
                }
                $writer->endElement();
            }
            $writer->endElement();
            $writer->endDocument();
        }

        $tree = $this->xmlRoot;
        $x_params = $tree['params'];
        if (isset($route_num)) {
            $x_params->appendElement('route_num', $route_num);
        }
        $x_params->appendElement('routes_file', \WORKSPACE . '/routes.xml');

        $this->setBreadcrumbs(
            [__('Routes') => self::$admin_url . '/blueprints/routes']
        );

        $files = glob(VIEW_TEMPLATES . '/view.*.xml');
        if (!empty($files)) {
            $x_views = $tree->appendElement('views');
            foreach ($files as $file) {
                $x_view = $x_views->appendElement('view', $file);
            }
        }
   }
}
