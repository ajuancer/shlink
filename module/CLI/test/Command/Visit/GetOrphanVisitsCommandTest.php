<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Visit\GetOrphanVisitsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class GetOrphanVisitsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->commandTester = $this->testerForCommand(new GetOrphanVisitsCommand($this->visitsHelper));
    }

    #[Test]
    public function outputIsProperlyGenerated(): void
    {
        $visit = Visit::forBasePath(new Visitor('bar', 'foo', '', ''))->locate(
            VisitLocation::fromGeolocation(new Location('', 'Spain', '', 'Madrid', 0, 0, '')),
        );
        $this->visitsHelper->expects($this->once())->method('orphanVisits')->withAnyParameters()->willReturn(
            new Paginator(new ArrayAdapter([$visit])),
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(
            <<<OUTPUT
            +---------+---------------------------+------------+---------+--------+----------+
            | Referer | Date                      | User agent | Country | City   | Type     |
            +---------+---------------------------+------------+---------+--------+----------+
            | foo     | {$visit->getDate()->toAtomString()} | bar        | Spain   | Madrid | base_url |
            +---------+---------------------------+------------+---------+--------+----------+

            OUTPUT,
            $output,
        );
    }
}
