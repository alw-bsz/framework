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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
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
class Opus_Account extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Accounts';

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Account table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'Role' => array(
                'model' => 'Opus_Role',
                'through' => 'Opus_Model_Dependent_Link_AccountRole',
                'fetch' => 'lazy'
            ),
    );

    /**
     * Retrieve all Opus_Account instances from the database.
     *
     * @return array Array of Opus_Account objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Account', 'Opus_Db_Accounts');
    }

    /**
     * Override to allow retrieving an account either by id or by the unique login name.
     * If neither id nor login are specified a new persistant instance gets created which
     * got idts id set as soon as it is stored via a call to _store().
     *
     * @param integer|Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param Zend_Db_Table_Abstract    $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @param string                    $id                (Optional) Login of existing record.
     * @throws Opus_Model_Exception     Thrown if passed id is invalid or login and id are specified.
     */
    public function __construct($id = null, Zend_Db_Table_Abstract $tableGatewayModel = null, $login = null) {
        if (false === is_null($login) && false === empty($login)) {
            if (false === is_null($id) && false === empty($id)) {
                 throw new Opus_Model_Exception('Login and id of an account are specified, specifie either id or login.');
             }
            $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
            $id = $table->fetchRow($table->select()->where('login = ?', $login));
            if (is_null($id) === true) {
                throw new Opus_Security_Exception('An account with the login name ' . $id . ' cannot be found.');
            }
        }
        parent::__construct($id, $tableGatewayModel);
    }

    /**
     * Initialize model with the following fields:
     * - Username
     * - Password
     *
     * @return void
     */
    protected function _init() {
        $login = new Opus_Model_Field('Login');
        $loginValidator = new Zend_Validate;
        $loginValidator->addValidator(new Zend_Validate_Alnum);
        $login->setValidator($loginValidator)->setMandatory(true);
        $this->addField($login);

        $password = new Opus_Model_Field('Password');
        $password->setMandatory(true);
        $this->addField($password);

    	$role = new Opus_Model_Field('Role');
    	$role->setMultiplicity('*');
    	$role->setDefault(Opus_Role::getAll());
    	$role->setSelection(true);
    	$this->addField($role);
    }

    /**
     * Stores the accounts credentials. Throws exception if something failes
     * during the store operation.
     *
     * @throws Opus_Security_Exception If storing failes.
     * @return void
     */
    public function store() {
        // Check for a proper credentials
        if ($this->isValid() === false) {
            throw new Opus_Security_Exception('Credentials are invalid.');
        }

        // Check if there is a account with the same
        // loginname before creating a new record.
        if (is_null($this->getId() === true)) {
            // brand new record here
            $accounts = Opus_Db_TableGateway::getInstance($tableGatewayClassName);
            $select = $accounts->select()->where('login=?', $this->getLogin());
            $row = $accounts->fetchRow($select);
            if ($row === false) {
                throw new Opus_Security_Exception('Account with login name ' . $this->getLogin() . ' already exists.');
            }
        }
        try {
            parent::store();
        } catch (Exception $ex) {
            throw new Opus_Security_Exception($ex->getMessage());
        }
    }

    /**
     * Validate the login before accepting the value.
     *
     * @param string $login Login name.
     * @throws Opus_Security_Exception Thrown if the login name is not valid.
     * @return Opus_Account Fluent interface.
     */
    public function setLogin($login) {
        $loginField = $this->getField('Login');
        if ($loginField->getValidator()->isValid($login) === false) {
            throw new Opus_Security_Exception('Login name should only contain alpha numeric characters.');
        }
        $loginField->setValue($login);
        return $this;
    }


    /**
     * Set a new password and reset isNewPasswordRequired flag.
     * The password goes through the PHP sha1 hash algorithm.
     *
     * @param string $password The new password to set.
     * @return Opus_Account Fluent interface.
     */
    public function setPassword($password) {
        $this->getField('Password')->setValue(sha1($password));
        return $this;
    }

    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return boolean
     */
    public function isPasswordCorrect($password) {
        return ($this->getPassword() === sha1($password));
    }

    /**
     * Returns long name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getLogin();
    }

}