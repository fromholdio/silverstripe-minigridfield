<?php

namespace Fromholdio\MiniGridField\Forms;

use Fromholdio\MiniGridField\ORM\HasOneRelationList;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Versioned\Versioned;

class HasOneMiniGridField extends MiniGridField
{
    protected $record;

    public function __construct($name, $title, $parent, $showLimitMessage = null, $relationName = null)
    {
        parent::__construct($name, $title, $parent, null, 1, $showLimitMessage, $relationName);
        $this->setRecord($parent->{$this->getRelationName()}());
    }

    public function getGridField()
    {
        $field = parent::getGridField();
        $field->addExtraClass('hasone-minigridfield');
        return $field;
    }

    public function getGridConfig()
    {
        $config = parent::getGridConfig();
        $detailForm = $config->getComponentByType(GridFieldDetailForm::class);
        $detailForm->setShowPagination(false);
        $detailForm->setShowAdd(false);
        return $config;
    }

    public function getGridList()
    {
        $parent = $this->parent;
        $list = HasOneRelationList::create($parent, $this->record, $this->getRelationName());
        $list = $list->filter('ID', $this->record->ID);
        $this->extend('updateGridList', $list);
        return $list;
    }

    public function isVersioned()
    {
        $parent = $this->parent;
        $relation = $parent->{$this->getRelationName()}();
        return $relation->hasExtension(Versioned::class);
    }

    public function isSorted()
    {
        return false;
    }

    public function getLimit()
    {
        return 1;
    }

    public function getRecord()
    {
        return $this->record;
    }

    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }
}
