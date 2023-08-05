<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10880Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10880BaseProcess::class,
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

        $ownerLoaded   = $this->_em->getRepository(GH10880ProcessOwner::class)->find($owner->id);
        $processLoaded = $ownerLoaded->process;

        $stageBLoaded = $this->_em->getRepository(GH10880ProcessStage::class)->find($stageB->id);
        $processLoaded->currentStage = $stageBLoaded;

        $queryLog = $this->getQueryLog();
        $queryLog->reset()->enable();
        $this->_em->flush();

        self::assertCount(1, $queryLog->queries);
        self::assertSame('UPDATE processes SET current_stage = ? WHERE id = ?', $queryLog->queries[0]['sql']);
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
     * @ORM\ManyToOne(targetEntity="GH10880Process", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="process_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @var GH10880Process
     */
    public $process;

}

/**
 * @ORM\Entity
 */
class GH10880Process extends GH10880BaseProcess
{
    /**
     * @ORM\OneToOne(targetEntity="GH10880ProcessOwner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_object_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var GH10880ProcessOwner
     */
    public $owner;

}

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="process_parent", type="string")
 * @ORM\Table(name="processes")
 * @ORM\DiscriminatorMap({
 *  "process" = "GH10880Process"
 * })
 */
class GH10880BaseProcess
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
     * @ORM\OneToOne(targetEntity="GH10880ProcessStage", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="current_stage", referencedColumnName="id", onDelete="SET NULL")
     * })
     *
     * @var GH10880ProcessStage
     */
    public $currentStage;

    /**
     * @ORM\OneToMany(targetEntity="GH10880ProcessStage", mappedBy="process", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id", referencedColumnName="process_id", onDelete="CASCADE")
     * })
     *
     * @var Collection
     */
    public $stages;

    /**
     *
     * @ORM\Column(name="parent_object_id", type="string", length=255, nullable=true)
     */
    public $parentObject;
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
     * @ORM\ManyToOne(targetEntity="GH10880Process", inversedBy="stages")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="process_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     *
     * @var GH10880Process
     */
    public $process;
}
