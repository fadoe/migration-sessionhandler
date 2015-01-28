<?php
namespace Marktjagd\Session\Storage\Handler;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class MigrationSessionHandler implements \SessionHandlerInterface
{

    private $legacyPdoSessionHandler;
    private $pdoSessionHandler;

    public function __construct(
        LegacyPdoSessionHandler $legacyPdoSessionHandler,
        PdoSessionHandler $pdoSessionHandler
    ) {
        $this->legacyPdoSessionHandler = $legacyPdoSessionHandler;
        $this->pdoSessionHandler = $pdoSessionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->pdoSessionHandler->close()
            && $this->legacyPdoSessionHandler->close();
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        return $this->legacyPdoSessionHandler->destroy($sessionId)
            && $this->pdoSessionHandler->destroy($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxLifeTime)
    {
        return $this->legacyPdoSessionHandler->gc($maxLifeTime)
            && $this->pdoSessionHandler->gc($maxLifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionId)
    {
        return $this->legacyPdoSessionHandler->open($savePath, $sessionId)
            && $this->pdoSessionHandler->open($savePath, $sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($sessionData = $this->legacyPdoSessionHandler->read($sessionId)) {
            $this->pdoSessionHandler->write($sessionId, $sessionData);
            $this->legacyPdoSessionHandler->destroy($sessionId);
        }

        return $this->pdoSessionHandler->read($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $sessionData)
    {
        return $this->pdoSessionHandler->write($sessionId, $sessionData);
    }
}
