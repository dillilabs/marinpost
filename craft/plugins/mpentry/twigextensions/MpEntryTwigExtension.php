<?php

namespace Craft;

use Twig_Extension;

class MpEntryTwigExtension extends \Twig_Extension
{

    public function getName()
    {
        return 'MpEntry Twig Extension';
    }

    public function getFilters()
    {
        return array(
            'htmlEntityDecode' => new \Twig_Filter_Method($this, 'htmlEntityDecode')
        );
    }

    public function htmlEntityDecode($string)
    {
        $decoded = html_entity_decode($string);
        $charset = craft()->templates->getTwig()->getCharset();

        return new \Twig_Markup($decoded, $charset);
    }

}
