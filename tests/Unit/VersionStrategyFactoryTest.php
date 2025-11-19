<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Warete\MoonshineUpgrade\Tests\TestCase;
use Warete\MoonshineUpgrade\VersionStrategies\Factory;
use Warete\MoonshineUpgrade\VersionStrategies\V4;

final class VersionStrategyFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_v4_strategy(): void
    {
        $factory = new Factory();

        $strategy = $factory->getByVersion(4, true, base_path());

        $this->assertInstanceOf(V4::class, $strategy);
    }

    #[Test]
    public function it_throws_exception_for_unsupported_version(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Provide supported version');

        $factory = new Factory();
        $factory->getByVersion(999, true, base_path());
    }

    #[Test]
    public function it_accepts_command_parameter(): void
    {
        $factory = new Factory();
        $command = $this->createMock(\Illuminate\Console\Command::class);

        $strategy = $factory->getByVersion(4, false, base_path(), $command);

        $this->assertInstanceOf(V4::class, $strategy);
    }

    #[Test]
    public function it_handles_different_base_paths(): void
    {
        $factory = new Factory();

        $paths = [
            base_path(),
            base_path('app'),
            base_path('app/MoonShine'),
        ];

        foreach ($paths as $path) {
            $strategy = $factory->getByVersion(4, true, $path);
            $this->assertInstanceOf(V4::class, $strategy);
        }
    }

    #[Test]
    public function it_handles_dry_run_flag(): void
    {
        $factory = new Factory();

        $strategyDryRun = $factory->getByVersion(4, true, base_path());
        $strategyNormal = $factory->getByVersion(4, false, base_path());

        $this->assertInstanceOf(V4::class, $strategyDryRun);
        $this->assertInstanceOf(V4::class, $strategyNormal);
    }
}
