<?php

namespace Symnext\AdminUI;

use Symnext\Core\App;
use Symnext\Toolkit\View;
use Symnext\Toolkit\XMLElement;
use DOMDocument;

class AdminXMLDocument extends DOMDocument
{
    static $admin_url;

    protected $errors = [];

    public function __construct()
    {
        parent::__construct();
        $this->registerNodeClass('DOMElement', XMLElement::class);

        $tree = $this->appendChild($this->createElement('data'));
        self::$admin_url = \BASE_URL . '/' . App::Configuration()->get('admin_path', 'admin');
        $admin_path = self::$admin_url;
        $tree->appendElementList(
            'params,page,title,navigation,context,contents'
        );
        $tree['params']->appendElement('admin-url', self::$admin_url);
        $tree['page']->appendElementList('type,id,action,handle');
        $tree['navigation']->appendElementList('content,structure');
        $tree['context']->appendElementList('breadcrumbs,heading,actions');
        $this->addToNavigation(
            'System', 'Authors', $admin_path . '/system/authors'
        );
        $this->addToNavigation(
            'System', 'Preferences', $admin_path . '/system/preferences'
        );
        $this->addToNavigation(
            'System', 'Extensions', $admin_path . '/system/extensions'
        );
        $this->addToNavigation(
            'Blueprints', 'Routes', $admin_path . '/blueprints/routes'
        );
        $this->addToNavigation(
            'Blueprints', 'Views', $admin_path . '/blueprints/views'
        );
        $this->addToNavigation(
            'Blueprints', 'Sections', $admin_path . '/blueprints/sections'
        );
        $this->addToNavigation(
            'Blueprints', 'Data Sources', $admin_path . '/blueprints/datasources'
        );
        $this->addToNavigation(
            'Blueprints', 'Events', $admin_path . '/blueprints/events'
        );

        $sections = App::Database()
            ->select(['name', 'handle', 'nav_group'])
            ->from('tbl_sections')
            ->execute()
            ->rows();
        $nav_groups = array_unique(array_column($sections, 'nav_group'));
        sort($nav_groups);

        $x_content_nav = $tree['navigation']['content'];
        foreach ($nav_groups as $nav_group) {
            $items = array_filter($sections, function ($item) use ($nav_group) {
                return $item['nav_group'] == $nav_group;
            });
            $x_group = $x_content_nav->appendElement(
                'group', null, ['name' => $nav_group]
            );
            foreach ($items as $item) {
                $x_group->appendElement(
                    'item',
                    $item['name'],
                    ['href' => $admin_path . '/publish/' . $item['handle']]
                );
            }
        }
        #echo $tree->ownerDocument->saveXML(); die;
        #$this->xml_head_elements = $tree->appendElement('add-to-head');
    }

    protected function getRoot()
    {
        if (!isset($this->documentElement)) die ("Doc has nothing in it.");
        return $this->documentElement;
    }

    protected function buildView(): void
    {
    }

    protected function addElementToHead(
        string $name,
        string $value = null,
        array $attributes = null
    ): void
    {
        $element = $this->xml_head_elements->appendElement($name, $value);
        if (is_array($atributes)) {
            $this->setAttributes($attributes);
        }
    }

    protected function addStylesheetToHead(array $attrs = null): void
    {
        $attrs = array_merge(['rel' => 'stylesheet'], $attrs);
        $this->addElementToHead('link', null, $attrs);
    }

    public function setParam(string $name, $value)
    {
        $this->getRoot()['params'][$name] = strval($value);
    }

    /**
     * Add navigation group to context.
     */
    public function addToNavigation(
        string $group_name,
        string $name,
        string $link
    )
    {
        $tree = $this->getRoot();
        #echo $tree->ownerDocument->saveXML(); die;
        $x_struct_nav = $tree['navigation']['structure'];
        $x_group = $x_struct_nav->find("group[@name='$group_name']");
        if (!$x_group) {
            $x_group = $this->createElement('group');
            $x_group->setAttribute('name', $group_name);
            $ref_node = $x_struct_nav->firstChild ?? null;
            $x_struct_nav->insertBefore($x_group, $ref_node);
        }
        $x_group->appendElement('item', $name, ['href' => $link]);
    }

    public function setBreadcrumbs(array $crumbs)
    {
        $tree = $this->getRoot();
        $breadcrumbs = $tree['context']['breadcrumbs'];
        foreach ($crumbs as $text => $link) {
            $breadcrumbs->appendElement('crumb', $text, ['link' => $link]);
        }
    }

    protected function setSubheading(string $heading)
    {
        $this->getRoot()['context']['heading'] = $heading;
    }

    public function addActionToContext(array $action): void
    {
        //$tree->find('context/actions')->appendElement($action);
    }

    public function addGroupToContents(string $name, string $legend): DOMElement
    {
        $contents = $this->getRoot()->find('contents');
        $group = $contents->appendElement('group');
        $group->setAttribute('name', $name);
        #$group->setAttribute('legend', __($legend));
        $group->setAttribute('legend', $legend);
        return $group;
    }

    public function getValue(string $path)
    {
        $node = $this->getRoot()->find($path);
        return $node ? $node->nodeValue : null;
    }

    public function setValue(string $path, $value)
    {
        $node = $this->getRoot()->find($path);
        if (!node) {}
    }
}
