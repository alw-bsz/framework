<?php
/**
 * Defines the Opus Application Framework library test suite for the
 * Opus_Data_Model subpackage.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

// The phpunit testrunner defines the global PHPUnit_MAIN_METHOD to
// configure the method of test execution. When called via php directly
// PHPUnit_MAIN_METHOD is not defined and therefor gets defined to execute
// AllTests:main() to run the suite.
if ( defined('PHPUnit_MAIN_METHOD') === false ) {
    define('PHPUnit_MAIN_METHOD', 'Opus_Data_Model_AllTests::main');
}

// Use the TestHelper to setup Zend specific environment.
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Main test suite for grouping and executing all subsequent test suites.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 * @subpackage  Data_Model
 */
class Opus_Data_Model_AllTests {

    /**
     * If the test class is called directly via php command the test
     * run gets startet in this method.
     *
     * @return void
     */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Construct and return the test suite.
     *
     * @return PHPUnit_Framework_TestSuite The suite.
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Opus Application Framework - Opus_Data_Model');
        $suite->addTestSuite('Opus_Data_Model_SiteTest');
        $suite->addTestSuite('Opus_Data_Model_AccountTest');
        return $suite;
    }

}

// Execute the test run if necessary.
if (PHPUnit_MAIN_METHOD === 'Opus_Data_Model_AllTests::main') {
    Opus_Data_Model_AllTests::main();
}
