<?php

namespace Fromholdio\MiniGridField\ORM;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/*
 * From silvershop/silverstripe-hasonefield
 */

class HasOneRelationList extends DataList
{
    /**
     * @var DataObject
     */
    protected $record;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var DataObject
     */
    protected $parent;

    /**
     * HasOneRelationList constructor.
     * @param DataObject $parent
     * @param DataObject $record
     * @param string $name
     */
    public function __construct(DataObject $parent, DataObject $record, $name)
    {
        $this->record = $record;
        $this->name = $name;
        $this->parent = $parent;

        parent::__construct($record->ClassName);
    }

    public function add($item)
    {
        $this->parent->setField("{$this->name}ID", $item->ID);
        $this->parent->write();
    }

    public function remove($item)
    {
        $item->delete();
        $this->parent->setField("{$this->name}ID", 0);
        $this->parent->write();
    }

    public function hasRecord()
    {
        $parent = $this->parent;
        $field = $this->name . 'ID';

        if ($parent->{$field}) {
            return true;
        }

        return false;
    }
}
