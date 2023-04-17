<?php

namespace Fromholdio\MiniGridField\Extensions;

use SGN\HasOneEdit\HasOneEdit;
use SilverStripe\Core\Extension;

class HasOneEditMiniGridFieldExtension extends Extension
{
    public function updateRelationName(&$name)
    {
        if (HasOneEdit::isHasOneEditField($name)) {
            list($parentRelationName, $name) = HasOneEdit::getRelationNameAndField($name);
        }
    }
}
