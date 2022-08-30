<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Core\App;
use Symnext\Database\DatabaseQuery;
#use Symnext\Database\EntryQuery;
use Symnext\Toolkit\XMLDocument;
use Symnext\SectionFields\FieldInput;
use Symnext\SectionFields\FieldTextArea;

class PublishIndex extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/publish-index.xsl';
    }

    protected function buildView(): void
    {
        global $Params;

        $tree = $this->getXMLRoot();
#echo $tree->ownerDocument->saveXML(); die;
        $section_handle = $Params['section_handle'];

        $values = App::Database()
            ->select()
            ->from('tbl_sections')
            ->where(['handle' => $section_handle])
            ->execute()
            ->rows();
        $section_doc = new XMLDocument();
        $section_doc->load($manifest_file);
        $section_root = $section_doc->documentElement;
        $tree['params']['section_name'] = $section_root['meta']['name']->nodeValue;
        $columns_visible = $section_root->findAll('fields/field[show_column="yes"]');
        if (count($columns_visible) == 0) die("No columns.");
        $x_columns = $tree->appendElement('columns');
        foreach ($columns_visible as $column) {
            $x_columns->appendElement('column', $column['name']->nodeValue);
        }
    }
}
/*
        if (!is_file($section_file)) {
            echo "Section not found.";
            exit();
        }
*/
/*
        $manifest_file = \MANIFEST . '/sections/' . $section_handle . '.txt';
        if (!is_file($manifest_file)) {
            echo "<code>$manifest_file</code> not found.";
            die;
        }
        $section = unserialize(file_get_contents($manifest_file));
        $section_file = \SECTIONS . '/section.' . $section_handle . '.xml';
        echo $section['name']; die;
*/
