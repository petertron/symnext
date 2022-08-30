<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI, Symnext\Toolkit;

class BlueprintsViewsEdit extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-views-edit.xsl';
    }

    protected function buildView(): void
    {
        global $Params;

        $tree = $this->getXMLRoot();
        if (isset($Params['handle'])) {
            $view_file = \VIEWS . '/view.' . $Params['handle'] . '.xml';
        } else {
            $view_file = AdminUI\VIEW_TEMPLATES . '/view.new.xml';
        }
        $tree['params']->appendElement('view_xml_file', $view_file);
        $ds_files = $tree->appendElement('datasource_files');
        $ds_files->appendElement('file', DATASOURCES . '/data.doing.xml');
        $ds_files->appendElement('file', DATASOURCES . '/data.creet.xml');
        $ds_files->appendElement('file', DATASOURCES . '/data.article.xml');

        $this->setBreadcrumbs(
            [__('Views') => self::$admin_url . '/blueprints/views']
        );
    }
}
