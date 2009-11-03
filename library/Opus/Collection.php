<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Framework
 * @package     Opus
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Bridges Opus_Collection_Information to Opus_Model_Abstract.
 *
 * @category    Framework
 * @package     Opus
 */
class Opus_Collection extends Opus_Model_AbstractDb
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsContents';

    /**
     * Holds the role id of the collection.
     *
     * @var int
     */
    private $__role_id = null;

    /**
     * Holds the role model of the collection.
     *
     * @var int
     */
    private $__role = null;

    /**
     * Track from where on subCollections have to be stored. Blindly calling
     * store on all subCollections leads to performance issues.
     *
     * @var mixed  Defaults to null.
     */
    private $__updateBelow = null;

    /**
     * Path to location of available themes.
     *
     * @var string
     */
    protected static $_themesPath = '';

    /**
     * Name of the default theme.
     *
     */
    const DEFAULT_THEME_NAME = 'default';

    /**
     * The collections external fields, i.e. those not mapped directly to the
     * Opus_Db_CollectionsContents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
        'SubCollection' => array(
            'fetch' => 'lazy',
            'model' => 'Opus_Collection'),
        'ParentCollection' => array(
            'fetch' => 'lazy',
            'model' => 'Opus_Collection'),
        'Visibility' => array(),
        'SeveralAppearances' => array(),
        'Theme' => array(),
    );

    /**
     * Fetches existing or creates new collection.
     *
     * @param  int|string  $role           The role that this collection is in.
     * @param  int         $collection_id  (Optional) Id of an existing collection.
     * @param  int         $parent         (Optional) parent Id of a new collection.
     * @param  int         $left_sibling   (Optional) left sibling Id of a new collection.
     */
    public function __construct($role_id, $collection_id = null, $role = null) {
        $this->__role_id = $role_id;
        if (null === $role) {
            $this->__role = new Opus_CollectionRole($this->__role_id);
        } else {
            $this->__role = $role;
        }

        parent::__construct($collection_id, new Opus_Db_CollectionsContents($role_id));
    }

    /**
     * Sets up field by analyzing collection content table metadata.
     *
     * @return void
     */
    protected function _init() {

        // Add all database column names except primary keys as Opus_Model_Fields
        $table = new Opus_Db_CollectionsContents($this->__role_id);
        $info = $table->info();
        $dbFields = $info['metadata'];
        foreach (array_keys($dbFields) as $dbField) {
            if (in_array($dbField, $info['primary'])) {
                continue;
            }
            // Convert snake_case to CamelCase for fieldnames
            $fieldname = '';
            foreach(explode('_', $dbField) as $part) {
                $fieldname .= ucfirst($part);
            }
            $field = new Opus_Model_Field($fieldname);
            $this->addField($field);
        }

        // Add a field to hold subcollections
        $subCollectionField = new Opus_Model_Field('SubCollection');
        $subCollectionField->setMultiplicity('*');
        $this->addField($subCollectionField);

        // Add a field to hold parentcollections
        $parentCollectionField = new Opus_Model_Field('ParentCollection');
        $parentCollectionField->setMultiplicity('*');
        $this->addField($parentCollectionField);

        // Add a field to hold the collection role's name
        $collectionRoleNameField = new Opus_Model_Field('RoleName');
        $this->addField($collectionRoleNameField);

        // Add a field to hold the collection role's id
        $collectionRoleIdField = new Opus_Model_Field('RoleId');
        $this->addField($collectionRoleIdField);

        // Add a field to hold visibility
        $visibility = new Opus_Model_Field('Visibility');
        $this->addField($visibility);

        // Add a field to hold SeveralAppearances
        $severalAppearances = new Opus_Model_Field('SeveralAppearances');
        $this->addField($severalAppearances);

        // Add a field to hold collection specific theme
        $theme = new Opus_Model_Field('Theme');
        $themes = array();
        foreach (glob(self::$_themesPath . '/*') as $entry) {
            if (true === is_dir($entry)) {
                $themes[basename($entry)] = basename($entry);
            }
        }
        $theme->setDefault($themes);
        $theme->setSelection(true);
        $this->addField($theme);
    }

    /**
     * Fetches the entries in this collection.
     *
     * @return array $documents The documents in the collection.
     */
    public function getEntries() {
        $docIds = Opus_Collection_Information::getAllCollectionDocuments((int) $this->__role_id, (int) $this->getId());
        return Opus_Document::getAll($docIds);
    }

    /**
     * Adds a document to this collection.
     *
     * @param  Opus_Document  $document The document to add.
     * @return void
     */
    public function addEntry(Opus_Model_AbstractDb $model) {
        $linkTable = new Opus_Db_LinkDocumentsCollections((int) $this->__role_id);
        $link = $linkTable->createRow();
        $link->documents_id = $model->getId();
        $link->collections_id = $this->getId();
        $link->save();
    }

    /**
     * Returns visibility.
     *
     * @return Opus_Collection|array Subcollection(s).
     */
    protected function _fetchVisibility() {
        return Opus_Collection_Information::getVisibility((int) $this->__role_id, (int) $this->getId());
    }

    /**
     * Returns SeveralAppearances.
     *
     * @return Opus_Collection|array Subcollection(s).
     */
    protected function _fetchSeveralAppearances() {
        return Opus_Collection_Information::severalAppearances((int) $this->__role_id, (int) $this->getId());
    }

    /**
     * Returns visibility.
     *
     * @return Opus_Collection|array Subcollection(s).
     */
    protected function _storeVisibility() {

    }

    /**
     * SeveralAppearances.
     *
     * @return Opus_Collection|array Subcollection(s).
     */
    protected function _storeSeveralAppearances() {

    }

    /**
     * Set location of available themes.
     *
     * @param  string $path
     */
    public static function setThemesPath($path) {
        if (is_dir($path) === false) {
            throw new InvalidArgumentException("Argument should be a valid path.");
        }
        self::$_themesPath = $path;
    }

    /**
     * Fetch the name of the theme that is associated with this collection.
     *
     * @return string The name of the theme.
     */
    protected function _fetchTheme() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsThemes');
        if (false === $this->isNewRecord()) {
            $row = $table->fetchRow($table->select()->where('role_id = ?', $this->__role_id)->where('collection_id = ?', $this->getId()));
            if (true === is_null($row)) {
                $theme = self::DEFAULT_THEME_NAME;
            } else {
                $theme = $row->theme;
            }
        } else {
            $theme = self::DEFAULT_THEME_NAME;
        }
        return $theme;
    }

    /**
     * Store the name of the theme that is associated with this collection.
     *
     * @param string The name of the theme.
     */
    protected function _storeTheme($theme) {
        if (true === is_null($theme)) {
            return;
        }
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsThemes');
        $row = $table->fetchRow($table->select()->where('role_id = ?', $this->__role_id)->where('collection_id = ?', $this->getId()));
        if (true === is_null($row)) {
            $row = $table->createRow();
        }
        if (self::DEFAULT_THEME_NAME === $theme) {
            // No need to store default theme setting.
            $row->delete();
        } else {
            $row->role_id = $this->__role_id;
            $row->collection_id = $this->getId();
            $row->theme = $theme;
            $row->save();
        }
    }

    /**
     * Returns subcollections.
     *
     * @return Opus_Collection|array Subcollection(s).
     */
    protected function _fetchSubCollection() {
        $collections = Opus_Collection_Information::getSubCollections((int) $this->__role_id, (int) $this->getId(), true, true);
        $collectionIds = array();
        foreach ($collections as $collection) {
            $collectionIds[] = $collection['collections_id'];
        }
        $result = array();
        $resultOut = array();
        if (empty($collectionIds) === false) {
            $result = array();
            $table = new Opus_Db_CollectionsContents($this->__role_id);
            $rows = $table->find($collectionIds);
            // Sorting since find() destroyed the order of the IDs.
            foreach ($rows as $row) {
                $result[(int) $row->id] = new Opus_Collection((int) $this->__role_id, $row, $this->__role);
            }
            foreach ($collectionIds as $id) {
                $resultOut[] = $result[(int) $id];
            }
        }
        return $resultOut;
    }

    /**
     * Overwrites standard store procedure to account for subcollections.
     *
     * @return void
     */
    protected function _storeSubCollection($subCollections) {
        if (false === $this->_getField('SubCollection', true)->isModified()) {
            return;
        }
        $updatedSubCollections = array();
        // Store subcollections as they were before the update.
        $collections = Opus_Collection_Information::getSubCollections((int) $this->__role_id, (int) $this->getId(), true);
        $previousCollections = array();
        foreach ($collections as $collection) {
            $previousCollections[] = $collection['collections_id'];
        }
        foreach ($subCollections as $index => $subCollection) {
            $subCollection->store();
            $id = (int) $subCollection->getId();
            $updatedSubCollections[] = $id;
            if ($index < $this->__updateBelow) {
                continue;
            }
            if ($index === 0) {
                $leftSibling = 0;
            } else {
                $leftSibling = (int) $subCollections[$index - 1]->getId();
            }
            Opus_Collection_Information::newCollectionPosition((int) $this->__role_id, $id, (int) $this->getId(), $leftSibling);
        }
        // Remove subcollections that are not supposed to be there any more.
        $removeCollections = array_diff($previousCollections, $updatedSubCollections);
        foreach ($removeCollections as $removeCollection) {
            Opus_Collection_Information::deleteCollectionPosition((int) $this->__role_id, $removeCollection, (int) $this->getId());
        }
    }

    /**
     * Extend standard adder function to track changes in multivalue field.
     *
     * @return void
     */
    public function addSubCollection($subCollection) {
        // FIXME: Workaround for parent::addSubCollection($subCollection)
        parent::__call('addSubCollection', array($subCollection));
        $this->__updateBelow = count($this->_getField('SubCollection')->getValue()) - 1;
    }

    /**
     * Insert a subcollection at a specific position.
     *
     * @param  integer          $position      Where to insert the subcollection.
     * @param  Opus_Collection  $subCollection The subcollection to insert.
     *
     * @return void
     */
    public function insertSubCollectionAt($position, $subCollection) {
        $subCollections = $this->_getField('SubCollection')->getValue();
        if ($position > count($subCollections)) {
            $this->addSubCollection($subCollection);
        } else {
            array_splice($subCollections, $position, 0, array($subCollection));
            $this->setSubCollection($subCollections);
            $this->__updateBelow = $position;
        }
    }

    /**
     * Returns parentcollections.
     *
     * @param  int  $index (Optional) Index of the parentcollection to fetchl.
     * @return Opus_Collection|array Parentcollection(s).
     */
    protected function _fetchParentCollection() {
        $result = array();
        $collectionIds = Opus_Collection_Information::getAllParents($this->__role_id, (int) $this->getId());
        $result = array();
        $resultOut = array();
        if (empty($collectionIds) === false) {
            $result = array();
            $table = new Opus_Db_CollectionsContents($this->__role_id);
            $rows = $table->find($collectionIds);
            // Sorting since find() destroyed the order of the IDs.
            foreach ($rows as $row) {
                $result[(int) $row->id] = new Opus_Collection((int) $this->__role_id, $row, $this->__role);
            }
            foreach ($collectionIds as $id) {
                $resultOut[] = $result[(int) $id];
            }
        }
        return $resultOut;
    }

    /**
     * Overwrites store procedure.
     *
     * @return void
     */
    protected function _storeParentCollection() {

    }

    /**
     * Returns the name of the collection's role name.
     *
     * @return string The name of the collection's role.
     */
    protected function _fetchRoleName() {
        return $this->__role->getName();
    }

    /**
     * Returns the name of the collection's role id.
     *
     * @return string The id of the collection's role.
     */
    protected function _fetchRoleId() {
        return $this->__role_id;
    }

    /**
     * Overwrites store procedure.
     *
     * @return void
     */
    protected function _storeRoleName() {}

    /**
     * Overwrites store procedure.
     *
     * @return void
     */
    protected function _storeRoleId() {}

    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent collections.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray($call = null) {

        $role = $this->__role;

        $result = array(
                    'Id' => $this->getId(),
                    'RoleId' => $this->__role_id,
                    'RoleName' => $role->getDisplayName(),
                    'DisplayBrowsing' => $this->getDisplayName('browsing'),
                    'DisplayFrontdoor' => $this->getDisplayName('frontdoor'),
                    'DisplayDoclist' => $this->getDisplayName('doclist'),
        );

        foreach (array_keys($this->_fields) as $fieldname) {
            $field = $this->_getField($fieldname);
            $fieldvalue = $field->getValue();

            if ('SubCollection' === $call AND ('SubCollection' === $fieldname
                                                OR 'ParentCollection' === $fieldname)
                OR 'ParentCollection' === $call AND 'SubCollection' === $fieldname
                ) {
                continue;
            }
            if ('ParentCollection' === $call AND '1' === $this->getId()) {
                return false;
            }

            if ($field->hasMultipleValues()) {
                $fieldvalues = array();
                foreach($fieldvalue as $value) {
                    if ($value instanceof Opus_Collection) {
                            $val = $value->toArray($fieldname);
                            if (false !== $val) {
                                $fieldvalues[] = $val;
                            }
                    } else {
                        $fieldvalues[] = $value;
                    }
                }
                if (false === empty($fieldvalues)) {
                    $result[$fieldname] = $fieldvalues;
                }
            } else {
                if ($fieldvalue instanceof Opus_Collection) {
                    $val = $fieldvalue->toArray($fieldname);
                    if (false !== $val) {
                        $result[$fieldname] = $val;
                    }
                } else {
                    $result[$fieldname] = $fieldvalue;
                }
            }

        }
        return $result;

    }

    /**
     * Returns Xml representation of the collection.
     *
     * @param  array $excludeFields Fields to exclude from the Xml output.
     * @return DomDocument Xml representation of the collection.
     */
    public function toXml(array $excludeFields = null) {
        return parent::toXml(array('ParentCollection'));
    }

    /**
     * Overwrite to reconnect to correct primary table row in database after unserializing.
     *
     * @return void
     */
    public function __wakeup() {
        $table = new Opus_Db_CollectionsContents($this->__role_id);
        $this->_primaryTableRow->setTable($table);
    }

    /**
     * Overwrite standard deletion in favour of collections history tracking.
     *
     * @return void
     */
    public function delete() {
        Opus_Collection_Information::deleteCollection($this->__role_id, (int) $this->getId());
    }

    /**
     * Un-deleting a collection.
     *
     * @return void
     */
    public function undelete() {
        Opus_Collection_Information::undeleteCollection($this->__role_id, (int) $this->getId());
    }

    /**
     * Overwrite standard deletion in favour of collections history tracking.
     *
     * @return void
     */
    public function deletePosition($parentCollId) {
        Opus_Collection_Information::deleteCollectionPosition($this->__role_id, (int) $this->getId(), (int) $parentCollId);
    }

    /**
     * Returns false if inserting given collection under this collection would result in a cycle.
     *
     * @return void
     */
    public function allowedPastePosition($collection_id, $parent_id) {
        return Opus_Collection_Information::allowedPastePosition($this->__role_id, $collection_id, $parent_id, (int) $this->getId());
    }


    /**
     * Returns custom string representation depending on role settings.
     *
     * @return string
     */
    public function getDisplayName($context = 'browsing') {
        $role = $this->__role;
        $fieldnames = $role->_getField('Display' . ucfirst($context))->getValue();
        $display = '';
        if (false === empty($fieldnames)) {
            foreach (explode(',', $fieldnames) as $fieldname) {
                $field = $this->_getField(trim($fieldname));
                if (false === is_null($field)) {
                    $display .= $field->getValue() . ' ';
                }
            }
        } else {
            $display = $this->getName();
        }
        return trim($display);
    }
}
