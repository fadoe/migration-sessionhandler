<?php
namespace Marktjagd\SessionTest\Storage\Handler;

use Marktjagd\Session\Storage\Handler\MigrationSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class MigrationSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MigrationSessionHandler
     */
    private $migrationSession;

    /**
     * @var LegacyPdoSessionHandler
     */
    private $legacySessionTable;

    /**
     * @var PdoSessionHandler
     */
    private $sessionTable;

    protected function getPdo()
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    protected function getLegacyPdoSessionHandler()
    {
        $pdo = $this->getPdo();
        $sql = 'CREATE TABLE legacy_sessions (sess_id VARCHAR(128) PRIMARY KEY, sess_data TEXT, sess_time INTEGER)';
        $pdo->exec($sql);

        $dbOptions = array(
            'db_table' => 'legacy_sessions'
        );

        return new LegacyPdoSessionHandler($pdo, $dbOptions);
    }

    protected function getPdoSessionHandler()
    {
        $dbOptions = array(
            'db_table' => 'sessions'
        );
        $sessionHandler = new PdoSessionHandler($this->getPdo(), $dbOptions);
        $sessionHandler->createTable();

        return $sessionHandler;
    }

    protected function setUp()
    {
        $this->iniSet('error_reporting', -1 & ~E_USER_DEPRECATED);

        if (!class_exists('PDO') || !in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped('This test requires SQLite support in your environment');
        }

        $this->legacySessionTable = $this->getLegacyPdoSessionHandler();
        $this->sessionTable = $this->getPdoSessionHandler();

        $this->migrationSession = new MigrationSessionHandler(
            $this->legacySessionTable,
            $this->sessionTable
        );
    }

    public function testLegacySessionTableHasSession()
    {
        $this->legacySessionTable->write('123', 'test');

        $this->assertEquals('test', $this->migrationSession->read('123'));
        $this->assertEquals('', $this->legacySessionTable->read('123'));
        $this->assertEquals('test', $this->sessionTable->read('123'));
    }

    public function testNewSessionTableAlreadyUsed()
    {
        $this->sessionTable->write('123', 'test');
        $this->assertEquals('test', $this->migrationSession->read('123'));
        $this->assertEquals('', $this->legacySessionTable->read('123'));
    }

    public function testNewSessionCreated()
    {
        $this->assertEquals('', $this->migrationSession->read('123'));
        $this->assertEquals('', $this->legacySessionTable->read('123'));
        $this->assertEquals('', $this->sessionTable->read('123'));
    }
}
