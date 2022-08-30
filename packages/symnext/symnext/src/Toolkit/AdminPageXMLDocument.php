<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.0.0
 */

class AdminPageXMLDocument extends XMLDocument
{
    public function __construct()
    {
        parent::__construct('admindoc');
        $root = $this->documentElement;
        $root->appendElementList(
            'params,page,title,stylesheets,scripts,navigation,context,contents'
        );
        $this->find('page')->appendElementList('type,id,action,handle');
        $this->find('navigation')->appendElementList('content,structure');
        $this->find('context')->appendElementList('breadcrumbs,heading,actions');
        $this->addNavGroup('content/content', 'Content');
        $this->addNavGroup('structure/blueprints', 'Blueprints');
        $this->addNavGroup('structure/system', 'System');
        $this->addToNavGroup(
            'structure/blueprints', [
                '/symphony/blueprints/pages/' => 'Pages',
                '/symphony/blueprints/sections/' => 'Sections',
                '/symphony/blueprints/datadources/' => 'Data Sources',
                '/symphony/blueprints/events/' => 'Events'
            ]
        );
        $this->addToNavGroup(
            'structure/system', [
                '/symphony/system/authors/' => 'Authors',
                '/symphony/system/preferences/' => 'Preferences',
                '/symphony/system/extensions/' => 'Extensions',
            ]
        );

        //echo $this->saveXML(); die;
    }

    public static function create(): self
    {
        $instance = new self();
        $instance->addStylesheet('/symphony.min.css');
        #echo $instance->saveXML();die;
        return $instance;
    }

    public function setPageType(string $value): void
    {
        if (in_array($value, ['index', 'single'])) {
            $this->find('page/type')->textContent = $value;
        }
    }

    public function setPageId(string $value): void
    {
        $this->find('page/id')->textContent = $value;
    }

    public function setPageAction(string $value): void
    {
        if (in_array($value, ['new', 'edit'])) {
            $this->find('page/action')->textContent = $value;
        }
    }

    public function setPageHandle(string $value): void
    {
        $this->find('page/handle')->textContent = $value;
    }

    public function setTitle(string $title)
    {
        $this->find('title')->textContent = $title;
    }

    public function addStylesheet(string $href, array $attributes = null): void
    {
        $attributes = $attributes ?? [];
        $attributes['href'] = $href;
        $stylesheets = $this->find('stylesheets');
        $sheet = $stylesheets->appendElement('sheet', null, $attributes);
    }

    public function addNavGroup(string $path, string $heading)
    {
        $path_split = explode('/', $path);
        $division = $path_split[0];
        if (!in_array($division, ['content', 'structure'])) return;
        $group = $path_split[1] ?? null;
        if (!isset($group)) return;
        $where = $this->find('navigation/' . $division);
        if (!$this->find('group[@name=' . $group . ']', $where)) {
            $where->appendElement('group', null, ['name' => $group, 'heading' => $heading]);
        }
    }

    public function addToNavGroup(string $path, array $items)
    {
        $path_split = explode('/', $path);
        $division = $path_split[0];
        if (!in_array($division, ['content', 'structure'])) return;
        $group = $path_split[1] ?? null;
        if (!isset($group)) return;
        $where = $this->find('navigation/' . $division . '/group[@name="' . $group . '"]');
        if (isset($where)) {
            foreach ($items as $url => $name) {
                $where->appendElement('item', $name, ['url' => $url]);
            }
        }
    }

    public function setBreadcrumbs(array $crumbs)
    {
        $breadcrumbs = $this->find('context/breadcrumbs');
        foreach ($crumbs as $text => $link) {
            $breadcrumbs->appendElement(
                'crumb', $text, ['link' => $link]
            );
        }
    }

    public function setSubheading(string $heading)
    {
        $this->find('context/heading')->textContent = $heading;
    }

    public function addActionToContext(array $action): void
    {
        $this->find('context/actions')->appendElements($action);
    }

    public function addGroupToContents(string $name, string $legend): DOMElement
    {
        $contents = $this->find('contents');
        $group = $contents->appendElement('group');
        $group->setAttribute('name', $name);
        #$group->setAttribute('legend', __($legend));
        $group->setAttribute('legend', $legend);
        return $group;
    }
}
