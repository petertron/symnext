<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\ExtensionManager;

class SystemExtensions extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/system-extensions.xsl';
    }

    protected function buildView(): void
    {
        $tree = $this->xmlRoot;
        $options = $tree->appendElement('apply-options');

        $extension_info = ExtensionManager::getExtensionInfo();
        if (!empty($extension_info)) {
            $x_extensions = $tree->appendElement('extensions');
            foreach ($extension_info as $info) {
                $x_extension = $x_extensions->appendElement('extension');
                $x_extension->appendElement('name', $info['title']);
                $x_extension->appendElement('version', $info['version']);
                $x_authors = $x_extension->appendElement('authors');
                foreach ($info['authors'] as $author) {
                    $x_author = $x_authors->appendElement('author');
                    $x_author->appendElement('name', $author['name']);
                    if (isset($author['homepage'])) {
                        $x_author->appendElement('homepage', $info['homepage']);
                    }
                }
            }
        }
    }
}
