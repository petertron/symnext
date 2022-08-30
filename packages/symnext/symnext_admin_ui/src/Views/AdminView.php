<?php

namespace Symnext\AdminUI\Views;

use Symnext\Core\App;
use Symnext\Toolkit\View;
use Symnext\AdminUI\AdminXMLDocument;
#use Symnext\Toolkit\XMLElement;

class AdminView extends View
{
    const XML_DOCUMENT_CLASS = 'Symnext\AdminUI\AdminXMLDocument';

    static $admin_url;

    protected $errors = [];

    protected function setXMLDocument()
    {
        global $Params;

        $doc = new AdminXMLDocument;
        foreach ($Params as $name => $value) {
            $doc->setParam($name, $value);
        }
        $this->xmlDoc = $doc;
    }

    protected function addToXMLTree(): void
    {
        $tree = $this->getXMLRoot();
        self::$admin_url = \BASE_URL . '/' . App::Configuration()->get('admin_path', 'admin');
        $admin_path = self::$admin_url;

        $tree['params']->appendElement('admin-url', self::$admin_url);
        $tree->appendElementList(
            'page,title,navigation,context,contents'
        );
        $tree['page']->appendElementList('type,id,action,handle');
        $tree['navigation']->appendElementList('content,structure');
        $tree['context']->appendElementList('breadcrumbs,heading,actions');
        $this->addNavGroup('content/content', 'Content');
        $this->addNavGroup('structure/blueprints', 'Blueprints');
        $this->addNavGroup('structure/system', 'System');
        $this->addToNavGroup(
            'structure/blueprints', [
                $admin_path . '/blueprints/routes' => 'Routes',
                $admin_path . '/blueprints/views' => 'Views',
                $admin_path . '/blueprints/sections' => 'Sections',
                $admin_path . '/blueprints/datasources' => 'Data Sources',
                $admin_path . '/blueprints/events' => 'Events'
            ]
        );
        $this->addToNavGroup(
            'structure/system', [
                $admin_path . '/system/authors' => 'Authors',
                $admin_path . '/system/settings' => 'Settings',
                $admin_path . '/system/extensions' => 'Extensions',
            ]
        );

        $sections = glob(SECTIONS . '/section.*.xml');
        if (!empty($sections)) {
            $x_sections = $tree->appendElement('sections');
            foreach ($sections as $section) {
                $x_sections->appendElement('section', $section);
            }
        }

        #echo $tree->ownerDocument->saveXML(); die;
        #$this->xml_head_elements = $tree->appendElement('add-to-head');
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

    public function setBreadcrumbs(array $crumbs)
    {
        $tree = $this->getXMLRoot();
        $breadcrumbs = $tree['context']['breadcrumbs'];
        foreach ($crumbs as $text => $link) {
            $breadcrumbs->appendElement('crumb', $text, ['link' => $link]);
        }
    }

    protected function setSubheading(string $heading)
    {
        $this->getXMLRoot()['context']['heading'] = $heading;
    }

    public function addActionToContext(array $action): void
    {
        //$tree->find('context/actions')->appendElement($action);
    }

    public function addGroupToContents(string $name, string $legend): DOMElement
    {
        $contents = $this->getXMLRoot()->find('contents');
        $group = $contents->appendElement('group');
        $group->setAttribute('name', $name);
        #$group->setAttribute('legend', __($legend));
        $group->setAttribute('legend', $legend);
        return $group;
    }
}
