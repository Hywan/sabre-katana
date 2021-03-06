<?php

/**
 * @license
 *
 * sabre/katana.
 * Copyright (C) 2015 fruux GmbH (https://fruux.com/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sabre\Katana\Server;

use Sabre\Katana\Configuration;
use Sabre\Katana\Database;
use Sabre\Katana\Dav;
use Sabre\Katana\DavAcl;
use Sabre\Katana\CalDav;
use Sabre\CalDAV as SabreCalDav;
use Sabre\CardDAV as SabreCardDav;
use Sabre\DAV as SabreDav;
use Sabre\DAVACL as SabreDavAcl;

/**
 * Server main class.
 *
 * @copyright Copyright (C) 2015 fruux GmbH (https://fruux.com/).
 * @author Ivan Enderlin
 * @license GNU Affero General Public License, Version 3.
 */
class Server
{
    /**
     * Administrator login.
     *
     * @const string
     */
    const ADMINISTRATOR_LOGIN = 'admin';

    /**
     * Path to the configuration file.
     *
     * @const string
     */
    const CONFIGURATION_FILE  = 'katana://data/etc/configuration/server.json';

    /**
     * sabre/dav server.
     *
     * @var SabreDav\Server
     */
    protected $_server        = null;

    /**
     * Server configurations.
     *
     * @var Configuration
     */
    protected $_configuration = null;

    /**
     * Database.
     *
     * @var Database
     */
    protected $_database      = null;

    /**
     * Construct and initialize the server.
     *
     * @return void
     */
    public function __construct()
    {
        $this->initialize();

        return;
    }

    /**
     * Initialize the server.
     *
     * In this order:
     *    * configurations,
     *    * database,
     *    * server,
     *    * authentication,
     *    * principals,
     *    * CalDAV,
     *    * CardDAV,
     *    * ACL,
     *    * synchronization.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->initializeConfiguration();
        $this->initializeDatabase();
        $this->initializeServer();
        $this->initializeAuthentication();
        $this->initializePrincipals($principalBackend);
        $this->initializeCalDAV($principalBackend);
        $this->initializeCardDAV($principalBackend);
        $this->initializeACL();
        $this->initializeSynchronization();

        return;
    }

    /**
     * Initialize the server configurations.
     *
     * @return void
     */
    protected function initializeConfiguration()
    {
        $this->_configuration = new Configuration(static::CONFIGURATION_FILE);

        return;
    }

    /**
     * Initialize the database.
     *
     * @return void
     */
    protected function initializeDatabase()
    {
        $configuration   = $this->getConfiguration()->database;
        $this->_database = new Database(
            $configuration->dsn,
            $configuration->username,
            $configuration->password
        );

        return;
    }

    /**
     * Initialize the underlying server.
     *
     * @return void
     */
    protected function initializeServer()
    {
        $this->_server = new SabreDav\Server(null);
        $this->_server->setBaseUri(
            $this->getConfiguration()->base_url ?: '/'
        );
        $this->_server->addPlugin(new SabreDav\Browser\Plugin());

        return;
    }

    /**
     * Initialize the authentication.
     *
     * @return void
     */
    protected function initializeAuthentication()
    {
        $database = $this->getDatabase();
        $backend  = new Dav\Authentication\BasicBackend($database);
        $plugin   = new SabreDav\Auth\Plugin($backend);
        $this->getServer()->addPlugin($plugin);

        return;
    }

    /**
     * Initialize the principals.
     *
     * @param  DavAcl\Principal\Backend  &$backend    Retrieve the principals backend by-reference.
     * @return void
     */
    protected function initializePrincipals(DavAcl\Principal\Backend &$backend = null)
    {
        if (null === $backend) {
            $backend = new DavAcl\Principal\Backend($this->getDatabase());
        }

        $node = new CalDav\Principal\Collection($backend);
        $this->getServer()->tree->getNodeForPath('')->addChild($node);
        $this->getServer()->addPlugin(new DavAcl\User\Plugin($this->getDatabase()));

        return;
    }

    /**
     * Initialize CalDAV.
     *
     * @param  DavACl\Principal\Backend  $principalBackend  The principal backend.
     * @return void
     */
    protected function initializeCalDAV(DavAcl\Principal\Backend $principalBackend)
    {
        $backend = new SabreCalDav\Backend\PDO($this->getDatabase());
        $node    = new SabreCalDav\CalendarRoot($principalBackend, $backend);
        $this->getServer()->tree->getNodeForPath('')->addChild($node);
        $this->getServer()->addPlugin(new SabreCalDav\Plugin());
        $this->getServer()->addPlugin(new SabreCalDav\Schedule\Plugin());

        return;
    }

    /**
     * Initialize CardDAV.
     *
     * @param  DavAcl\Principal\Backend  $principalBackend  The principal backend.
     * @return void
     */
    protected function initializeCardDAV(DavAcl\Principal\Backend $principalBackend)
    {
        $backend = new SabreCardDav\Backend\PDO($this->getDatabase());
        $node    = new SabreCardDav\AddressBookRoot($principalBackend, $backend);
        $this->getServer()->tree->getNodeForPath('')->addChild($node);
        $this->getServer()->addPlugin(new SabreCardDav\Plugin());

        return;
    }

    /**
     * Initialize ACL.
     *
     * @return void
     */
    protected function initializeACL()
    {
        $plugin                               = new SabreDavAcl\Plugin();
        $plugin->adminPrincipals[]            = 'principals/' . static::ADMINISTRATOR_LOGIN;
        $plugin->allowAccessToNodesWithoutACL = true;
        $plugin->hideNodesFromListings        = true;
        $plugin->defaultUsernamePath          = 'principals/';

        $this->getServer()->addPlugin($plugin);

        return;
    }

    /**
     * Initialize synchronization.
     *
     * @return void
     */
    protected function initializeSynchronization()
    {
        $this->getServer()->addPlugin(new SabreDav\Sync\Plugin());

        return;
    }

    /**
     * Get the underlying server.
     *
     * @return SabreDav\Server
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * Get the server configurations.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Get the database.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    /**
     * Run the server, i.e. consume the current request.
     */
    public function run()
    {
        $this->getServer()->exec();

        return;
    }
}
