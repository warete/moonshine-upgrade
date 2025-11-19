<?php

declare(strict_types=1);

namespace Warete\MoonshineUpgrade\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Warete\MoonshineUpgrade\Tests\TestCase;

final class ExampleTest extends TestCase
{
    #[Test]
    public function it_runs_basic_assertion(): void
    {
        $this->assertTrue(true);
    }
}
