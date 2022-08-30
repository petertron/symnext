<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\View;
use Symnext\Toolkit\XMLDocument;
use Symnext\Toolkit\XMLElement;

class BlueprintsRoutesIndex extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/blueprints-routes-index.xsl';
    }

    protected function buildView(): void
    {
        parent::buildView();
        $tree = $this->getXMLRoot();
        $file = \WORKSPACE . '/routes.xml';
        if (is_file($file)) {
            $tree->appendElement('routes', $file);
        }
        $files = glob(VIEW_TEMPLATES . '/view.*.xml');
        if (!empty($files)) {
            $x_views = $tree->appendElement('views');
            foreach ($files as $file) {
                $x_view = $x_views->appendElement(
                    'view', $file,
                    ['handle' => explode('.', basename($file))[1]]
                );
            }
        }
    }
}
