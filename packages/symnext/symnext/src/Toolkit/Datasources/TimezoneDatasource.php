<?php

/**
 * @package data-sources
 */

namespace Symnext\Toolkit\Datasources;

use Symnext\Toolkit\Datasource;
use Symnext\Toolkit\XMLElement;

/**
 * The `NavigationDatasource` outputs the Symphony page structure as XML.
 * This datasource supports filtering to narrow down the results to only
 * show pages that match a particular page type, have a specific parent, etc.
 *
 * @since Symphony 2.3
 */
class TimezoneDatasource extends Datasource
{
    public function execute(XMLElement $xml_tree, array &$param_pool = null): XMLElement
    {
        #$result = XMLDocument->appendElement($this->dsParamROOTELEMENT);
        $result = XMLDocument->appendElement('time-zones');

        // Timezones
        /*$options = DateTimeObj::getTimezonesSelectOptions((
            !empty($fields['region']['timezone'])
                ? $fields['region']['timezone']
                : date_default_timezone_get()
        ));*/
        $options = DateTimeObj::getTimezonesSelectOptions(date_default_timezone_get());

        /*if (is_array($attributes) && !empty($attributes)) {
            $obj->setAttributeArray($attributes);
        }

        if (!is_array($options) || empty($options)) {
            if (!isset($attributes['disabled'])) {
                $obj->setAttribute('disabled', 'disabled');
            }

            return $obj;
        }*/

        foreach ($options as $o) {
            //  Optgroup
            if (isset($o['label'])) {
                $optgroup = new XMLElement('optgroup');
                $optgroup->setAttribute('label', $o['label']);

                if (isset($o['data-label'])) {
                    $optgroup->setAttribute('data-label', $o['data-label']);
                }

                foreach ($o['options'] as $option) {
                    $optgroup->appendChild(
                        Widget::__SelectBuildOption($option)
                    );
                }

                $obj->appendChild($optgroup);
            } else {
                $obj->appendChild(Widget::__SelectBuildOption($o));
            }
        }

        return $result;
    }
}
