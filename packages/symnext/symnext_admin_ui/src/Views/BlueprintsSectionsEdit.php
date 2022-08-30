<?php

namespace Symnext\AdminUI\Views;

use Symnext\Core\App;
use Symnext\AdminUI;
use Symnext\Toolkit\SectionManager;
use Symnext\Toolkit\Section;
use Symnext\Toolkit\Lang;
use Symnext\Toolkit\General;
use Symnext\Stream\SNMemoryStream;

class BlueprintsSectionsEdit extends AdminView
{
    protected $postDataHasErrors = false;

    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-sections-edit.xsl';
    }

    protected function beforeFilter()
    {
        global $Params;
    }

    protected function edit()
    {
        global $Params;

            $stuff = App::Database->show('TABLES')->execute();
            #print_r($stuff); die;
        // Check whether URL handle is valid.
    }

    protected function buildView(): void
    {
        global $Params;

        $current_handle = $Params['handle'] ?? null;
        if ($current_handle and !SectionManager::sectionExists($current_handle)) {
            echo "Section does not exist.";
            exit;
        }
        $section_xml_file = $current_handle ?
            \SECTIONS . '/section.' . $current_handle . '.xml' :
            AdminUI\VIEW_TEMPLATES . '/section.new.xml';

        // Check post data.
        if (!empty($_POST)) {
            #print_r($_POST); die;
            $section = new Section;
            $_POST['meta']['current_handle'] = $current_handle;;
            $section->setFromPost($_POST);
    #var_dump($section); die;
            /*$fields = $_POST['fields'] ?? null;
            if (!empty($fields)) {
                foreach ($fields as $i => $values) {
                    $section->addField($values);
                }
            }*/
            //var_dump($section->get()); die;

            $contents = $section->writeXMLString();

            if ($section->hasErrors()) {
                SNMemoryStream::loadContents('section_file.xml', $contents);
                $section_xml_file = 'sn-memory://section_file.xml';
            } else {
                $handle = $_POST['meta']['handle'];
                $section_xml_file = \SECTIONS . '/section.'
                    . $handle . '.xml';
                file_put_contents($section_xml_file, $contents);
                file_put_contents(
                    \MANIFEST . '/sections/' . $handle . '.xml',
                    $section->writeXMLString(false)
                );
                if ($handle !== $current_handle) {
                    unlink(\SECTIONS . '/section.' . $current_handle . '.xml');
                    unlink(\MANIFEST . '/sections/' . $current_handle . '.xml');
                }
                $admin_path = App::Configuration()->get('admin_path', 'admin');
                redirect(\BASE_URL . '/' . $admin_path . '/blueprints/sections/edit/' . $handle);
            }
        }
            #$stuff = App::Database()->show()->like('tbl_section:article%')->execute()->rows();
            #print_r($stuff); die;

        #$z = App::Database()->showIndex()->from('tbl_section:article')->execute()->rows();
        $tree = $this->getXMLRoot();
        $params = $tree['params'];
        $params->appendElement('section_xml_file', $section_xml_file);
        $params->appendElement('validators_file', \WORKSPACE . '/validators.xml');
        $ignore = [
            \WORKSPACE . '/data-sources',
            \WORKSPACE . '/events',
            \WORKSPACE . '/sections',
            \WORKSPACE . '/text-formatters',
            \WORKSPACE . '/views',
            \WORKSPACE . '/utilities'
        ];
        $directories = General::listDirStructure(\WORKSPACE, null, true, \ROOT_DIR, $ignore);
        $x_workspace_dirs = $tree->appendElement('workspace_dirs');
        foreach ($directories as $dir) {
            $x_workspace_dirs->appendElement('dir', trim($dir, '/'));
        }
        #$x_fields_available = $tree->appendElement('fields_available');
        $x_field_names = $tree->appendElement('field_names');
        $class_files = glob(\Symnext\SECTION_FIELDS . '/*.php');
        if (!empty($class_files)) {
            foreach ($class_files as $class_file) {
                $class_name = '\\Symnext\\SectionFields\\' . basename($class_file, '.php');
                if (class_exists($class_name)) {
                    $obj = new $class_name();
                    #$obj->outputXML($x_fields_available);
                    $item = $x_field_names->appendElement('field', $obj->getName());
                    $item->setAttribute('class', get_class($obj));
                }
            }
        }
        #echo $this->xmlDoc->saveXML(); die;
        $field_templates = glob(
            AdminUI\SECTION_FIELD_TEMPLATES . '/field.*.settings.xsl'
        );
        sort($field_templates);
        $root = $this->getXSLRoot();
        foreach ($field_templates as $field_file) {
            $root->appendElement('xsl:include', null, ['href' => 'file://' . $field_file]);
        }
        #echo $this->stylesheet->saveXML(); die;
        $this->setBreadcrumbs(
            [__('Sections') => self::$admin_url . '/blueprints/sections']
        );
    }
}
