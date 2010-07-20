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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for licences in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Role extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Roles';

    /**
     * The privileges external fields, i.e. those not mapped directly to the
     * Opus_Db_Privileges table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'Privilege' => array(
                'model' => 'Opus_Privilege',
                'fetch' => 'lazy'
            ),
        );

    /**
     * Retrieve all Opus_Roles instances from the database.
     *
     * @return array Array of Opus_Roles objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Role', 'Opus_Db_Roles');
    }

    /**
     * Initialize model with the following fields:
     * - Name
     *
     * @return void
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true);
        $this->addField($name);

        $privilege = new Opus_Model_Field('Privilege');
        $privilege->setMandatory(true);
        $privilege->setMultiplicity('*');
        $this->addField($privilege);
    }

    protected function _fetchPrivilege() {
        $result = array();
        if (false === $this->isNewRecord()) {
            $table = Opus_Db_TableGateway::getInstance('Opus_Db_Privileges');
            $privileges = $table->getAdapter()->fetchCol(
                $table->select()
                ->from($table, array('id'))
                ->where('role_id = ?', $this->getId())
                ->where('privilege  != ?', 'readFile'));
            foreach($privileges as $privilegeId) {
                $result[] = new Opus_Privilege($privilegeId);
            }
        }
        return $result;
    }


    /**
     * Returns name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

}
