<?php

namespace Fromholdio\MiniGridField\Forms;

use Fromholdio\GridFieldLimiter\Forms\GridFieldLimiter;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;
use SilverStripe\View\Requirements;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class MiniGridField extends FormField
{
    protected $limit;
    protected $showLimitMessage;
    protected $allowedClasses;
    protected $sortField;
    protected $multiAdderDisabled;
    protected $deleteActionRemoveRelation;

    public function __construct($name, $title, $parent, $sortField = null, $limit = null, $showLimitMessage = null)
    {
        parent::__construct($name, $title, null);
        $this->name = $name;
        $this->title = $title;
        $this->parent = $parent;
        $this->setSortField($sortField);
        $this->setLimit($limit);
        $this->setShowLimitMessage($showLimitMessage);
        $this->setForm($parent->Form);
    }

    public function Field($properties = [])
    {
        Requirements::css('fromholdio/silverstripe-minigridfield:client/css/minigridfield.css');

        $parent = $this->parent;
        if ($parent->exists()) {
            $field = $this->getGridField();
        } else {
            $field = $this->getPreSaveField();
        }
        $this->extend('updateField', $field);
        return $field;
    }

    public function getPreSaveField()
    {
        $parent = $this->parent;
        $parentSingular = $parent->i18n_singular_name();
        $relationClass = $parent->getRelationClass($this->name);
        $relationSingular = strtolower($relationClass::singleton()->i18n_singular_name());

        $field = LiteralField::create(
            $this->name . 'Save',
            _t(
                __CLASS__ . '.PLEASESAVEPARENTTOADDOBJECTS',
                'Please save {parent} first to add a {relation}',
                [
                    'parent' => $parentSingular,
                    'relation' => $relationSingular
                ]
            )
        );
        $field->addExtraClass('minigridfield-pre-save');
        $this->extend('updatePreSaveField', $field);
        return $field;
    }

    public function getGridField()
    {
        $field = GridField::create(
            $this->name,
            $this->title,
            $list = $this->getGridList()
        );
        $config = $this->getGridConfig();
        $field->setConfig($config);
        $field->setList($list);
        $field->setForm($this->Form);

        $field->addExtraClass('minigridfield');

        $this->extend('updateGridField', $field);
        return $field;
    }

    public function getGridList()
    {
        $parent = $this->parent;
        $list = $parent->{$this->name}();
        $this->extend('updateGridList', $list);
        return $list;
    }

    public function getGridConfig()
    {
        $config = GridFieldConfig::create()
            ->addComponent(new GridFieldDetailForm())
            ->addComponent(new GridFieldDataColumns())
            ->addComponent(new GridFieldEditButton())
            ->addComponent(new GridFieldDeleteAction(
                $this->getDeleteActionRemoveRelation()
            ));

        if ($this->isVersioned()) {
            $config->addComponent(
                new VersionedGridFieldState()
            );
        }

        if ($this->isLimited()) {
            $config->addComponent(
                new GridFieldLimiter(
                    $this->getLimit(),
                    'before',
                    $this->getShowLimitMessage()
                )
            );
            $adderTargetFragment = 'limiter-before-left';
        }
        else {
            $adderTargetFragment = 'before';
        }

        if ($this->isMultiAdder()) {
            $adder = Injector::inst()->create(GridFieldAddNewMultiClass::class, $adderTargetFragment);
            $classes = $this->getAllowedClasses();
            if (!$classes) {
                $classes = $this->getAvailableClasses();
            }
            $adder->setClasses($classes);
        }
        else {
            $adder = new GridFieldAddNewButton($adderTargetFragment);
        }
        $config->addComponent($adder);

        if ($this->isSorted()) {
            $config->addComponent(
                new GridFieldOrderableRows($this->getSortField())
            );
        }

        return $config;
    }

    public function isVersioned()
    {
        $parent = $this->parent;
        $relation = $parent->{$this->name}();
        return $relation->first()->hasExtension(Versioned::class);
    }

    public function isLimited()
    {
        $limit = $this->getLimit();
        return $limit > 0;
    }

    public function isMultiAdder()
    {
        if ($this->getIsMultiAdderDisabled()) {
            return false;
        }
        if ($this->getAllowedClasses()) {
            return true;
        }
        if ($this->getAvailableClasses()) {
            return true;
        }
        return false;
    }

    public function isSorted()
    {
        $sortField = $this->getSortField();
        if (!$sortField || $sortField === '') {
            return false;
        }

        if ($this->isLimited()) {
            $limit = $this->getLimit();
            if ($limit === 1) {
                return false;
            }
        }

        return true;
    }

    public function getAvailableClasses()
    {
        $parent = $this->parent;
        $relationClass = $parent->getRelationClass($this->name);
        $classes = array_values(ClassInfo::subclassesFor($relationClass));
        sort($classes);
        $availableClasses = [];
        foreach ($classes as $i => $class) {
            if ($class === $relationClass) {
                continue;
            }
            $sglClass = $class::singleton();
            if ($sglClass->hasMethod('getMultiAddTitle')) {
                $title = $sglClass->getMultiAddTitle();
            } else {
                $title = $sglClass->i18n_singular_name();
            }
            $availableClasses[$class] = $title;
        }
        if (count($availableClasses) > 0) {
            return $availableClasses;
        }
        return null;
    }

    public function getAllowedClasses()
    {
        return $this->allowedClasses;
    }

    public function setAllowedClasses($classes)
    {
        if (is_array($classes) && count($classes) > 0) {
            $this->allowedClasses = $classes;
        }
        $this->allowedClasses = null;
        return $this;
    }

    public function getIsMultiAdderDisabled()
    {
        return (bool) $this->multiAdderDisabled;
    }

    public function setIsMultiAdderDisabled($value)
    {
        $this->multiAdderDisabled = (bool) $value;
        return $this;
    }

    public function getLimit()
    {
        return (int) $this->limit;
    }

    public function setLimit($value)
    {
        $this->limit = (int) $value;
        return $this;
    }

    public function getSortField()
    {
        return $this->sortField;
    }

    public function setSortField($value)
    {
        $this->sortField = $value;
        return $this;
    }

    public function getShowLimitMessage()
    {
        return (bool) $this->showLimitMessage;
    }

    public function setShowLimitMessage($value)
    {
        $this->showLimitMessage = (bool) $value;
        return $this;
    }

    public function getDeleteActionRemoveRelation()
    {
        $removeRelation = $this->deleteActionRemoveRelation;
        if (is_bool($removeRelation)) {
            return $removeRelation;
        }

        if ($this->isVersioned()) {
            return true;
        }
        return false;
    }

    public function setDeleteActionRemoveRelation($removeRelation)
    {
        if (is_bool($removeRelation)) {
            $this->deleteActionRemoveRelation = $removeRelation;
        }
        else {
            $this->deleteActionRemoveRelation = null;
        }
        return $this;
    }

    public function handleRequest(HTTPRequest $request)
    {
        return $this->Field()->handleRequest($request);
    }
}
