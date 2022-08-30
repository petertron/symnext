<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
//use Symnext\Toolkit\SectionManager;

class BlueprintsSectionsIndex extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-sections-index.xsl';
    }

    protected function buildView(): void
    {
        $tree = $this->xmlRoot;
        $options = $tree->appendElement('apply-options');
    }
}
