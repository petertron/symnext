<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI, Symnext\Toolkit;

class PublishEdit extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/publish-edit.xsl';
    }

    protected function buildView(): void
    {
        global $Params;

        $tree = $this->getXMLRoot();

        $tree['params']['section_handle'] = $Params['section_handle'];
        $section_xml_file = \SECTIONS . '/section.' . $Params['section_handle'] . '.xml';
        if (isset($Params['entry_handle'])) {
            echo "wee"; die;
        }
        $tree['params']->appendElement('section_xml_file', $section_xml_file);
    }
}
