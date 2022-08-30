<?php

namespace Symnext\Toolkit;

class XMLWriter2 extends \XMLWriter
{
    public function setIndent($enable)
    {
        parent::setIndent($enable);
        if ($enable) {
            $this->setIndentString(str_pad('', 4, ' '));
        }
    }

    public function writeElementArray(
        array|object $items,
        array $exclude = null,
    ): void
    {
        foreach ($items as $name => $value) {
            if (is_array($exclude) and in_array($name, $exclude)) continue;
            if (is_array($value) and !empty($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $value2) {
                        $this->writeElement($name, $value2);
                    }
                } else {
                    $this->startElement($name);
                    $this->writeElementArray($value, $exclude);
                    $this->endElement();
                }
            } else {
                $this->writeElement($name, $value);
            }
        }
    }
}
