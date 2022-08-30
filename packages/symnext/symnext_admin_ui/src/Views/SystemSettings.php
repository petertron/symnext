<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\SectionManager;

class SystemSettings extends AdminView
{
    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/system-settings.xsl';
    }

    protected function buildView(): void
    {
        $tree = $this->tree;
        $tree->addChild('site_mode_options');
        $item = $tree->site_mode_options->addChild('item', 'Development');
        $item->addAttribute('value', 'development');
        $item = $tree->site_mode_options->addChild('item', 'Production');
        $item->addAttribute('value', 'production');
        $item = $tree->site_mode_options->addChild('item', 'Maintenance');
        $item->addAttribute('value', 'maintenance');
        /*$tree->add([[
            'tag' => 'site_mode_options', [
                [
                    'tag' => 'item',
                    'value' => 'Development',
                    'attributes' => ['value' => 'development']
                ],
                [
                    'tag' => 'item',
                    'value' => 'Production',
                    'attributes' => ['value' => 'production']
                ],
                [
                    'tag' => 'item',
                    'value' => 'Maintenance',
                    'attributes' => ['value' => 'maintenance']
                ]
            ]
        ]]);*/
        $tree->params->addChild('config_file', CONFIG);
        #$options = $this->tree->addChild('apply-options');
    }
}
