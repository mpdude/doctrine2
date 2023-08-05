<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10880Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10880Process::class,
            GH10880ProcessOwner::class,
            GH10880ProcessStage::class,
        ]);
    }

    public function testProcessShouldBeUpdated(): void
    {
        $process = new GH10880Process();

        $stageA                = new GH10880ProcessStage();
        $stageA->process       = $process;
        $process->currentStage = $stageA;

        $stageB          = new GH10880ProcessStage();
        $stageB->process = $process;

        $owner          = new GH10880ProcessOwner();
        $owner->process = $process;
        $process->owner = $owner;

        $this->_em->persist($process);
        $this->_em->persist($stageA);
        $this->_em->persist($stageB);
        $this->_em->persist($owner);
        $this->_em->flush();
        $this->_em->clear();

        $ownerLoaded   = $this->_em->find(GH10880ProcessOwner::class, $owner->id);
        $processLoaded = $ownerLoaded->process;

        $stageBLoaded                = $this->_em->find(GH10880ProcessStage::class, $stageB->id);
        $processLoaded->currentStage = $stageBLoaded;

        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();
        $this->_em->flush();

        self::assertCount(1, $queryLog->queries);
        self::assertSame('UPDATE GH10880Process SET currentStage_id = ? WHERE id = ?', $queryLog->queries[0]['sql']);
    }
}

/**
 * @ORM\Entity
 */
class GH10880ProcessOwner
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="GH10880Process")
     *
     * @var GH10880Process
     */
    public $process;
}

/**
 * @ORM\Entity
 */
class GH10880Process
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id = null;

    /**
     * @ORM\OneToOne(targetEntity="GH10880ProcessOwner")
     *
     * @var GH10880ProcessOwner
     */
    public $owner;

    /**
     * @ORM\OneToOne(targetEntity="GH10880ProcessStage")
     *
     * @var GH10880ProcessStage
     */
    public $currentStage;
}

/**
 * @ORM\Entity
 */
class GH10880ProcessStage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="GH10880Process")
     *
     * @var GH10880Process
     */
    public $process;
}
