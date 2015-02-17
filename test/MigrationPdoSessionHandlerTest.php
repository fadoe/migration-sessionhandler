<?php
namespace Marktjagd\SessionTest\Storage\Handler;

use Marktjagd\Session\Storage\Handler\MigrationPdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class MigrationPdoSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    private static $SESSION_ID = '123';
    private static $SESSION_DATA = 'test';
    private static $SESSION_DATA_EMPTY = '';

    /**
     * @var MigrationPdoSessionHandler
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

        $this->migrationSession = new MigrationPdoSessionHandler(
            $this->legacySessionTable,
            $this->sessionTable
        );
    }

    public function testLegacySessionTableHasSession()
    {
        $this->legacySessionTable->write(self::$SESSION_ID, self::$SESSION_DATA);

        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->sessionTable->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA, $this->migrationSession->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->legacySessionTable->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA, $this->sessionTable->read(self::$SESSION_ID));
    }

    public function testNewSessionTableAlreadyUsed()
    {
        $this->sessionTable->write(self::$SESSION_ID, self::$SESSION_DATA);
        $this->assertEquals(self::$SESSION_DATA, $this->migrationSession->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->legacySessionTable->read(self::$SESSION_ID));
    }

    public function testNewSessionCreated()
    {
        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->migrationSession->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->legacySessionTable->read(self::$SESSION_ID));
        $this->assertEquals(self::$SESSION_DATA_EMPTY, $this->sessionTable->read(self::$SESSION_ID));
    }
}
