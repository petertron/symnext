<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\View;

class BlueprintsViewsIndex extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-views-index.xsl';
    }

    protected function buildView(): void
    {
        parent::buildView();
        $tree = $this->getXMLRoot();
        $files = glob(VIEW_TEMPLATES . '/view.*.xml');
        if (!empty($files)) {
            $x_views = $tree->appendElement('views');
            foreach ($files as $file) {
                $x_views->appendElement('view', $file);
            }
        }
        #echo $this->xmlDoc->saveXML(); die;
        $this->setSubheading(__('Views'));
    }
}
