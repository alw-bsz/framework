<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
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
 * @category    Tests
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
// Setup error reporting.
// TODO leave to Zend and config?
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Define path to application directory
defined('APPLICATION_PATH')
        || define('APPLICATION_PATH', realpath(dirname(dirname(__FILE__))));

// Define application environment (use 'production' by default)
define('APPLICATION_ENV', 'testing');

// Configure include path.
$scriptDir = dirname(__FILE__);

set_include_path('.'
        . PATH_SEPARATOR . $scriptDir // Skriptverzeichnis
        . PATH_SEPARATOR . $scriptDir . DIRECTORY_SEPARATOR . 'support' // Support Klassen für Tests
        . PATH_SEPARATOR . dirname($scriptDir) . DIRECTORY_SEPARATOR . 'library' // OPUS Klassen
        . PATH_SEPARATOR . get_include_path()); // Standard Include-Pfad

// enable fallback autoloader for testing
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->suppressNotFoundWarnings(false);
$autoloader->setFallbackAutoloader(true);

// Zend_Loader is'nt available yet. We have to do a require_once in order
// to find the bootstrap class.
require_once 'Zend/Application.php';

// Do test environment initializiation.
$application = new Zend_Application(APPLICATION_ENV, 
            array(
                "config" => array(
                    APPLICATION_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'tests.ini',
                    APPLICATION_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'config.ini'
                )
            )
        );


$application->bootstrap(array('Database','Temp','OpusLocale'));
