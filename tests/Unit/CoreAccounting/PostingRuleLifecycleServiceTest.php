<?php

namespace Tests\Unit\CoreAccounting;

use App\Modules\CoreAccounting\Application\GLPostingEngine\PostingRuleLifecycleService;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingRuleVersion;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PostingRuleLifecycleServiceTest extends TestCase
{
    public function test_allows_valid_transition(): void
    {
        $service = new PostingRuleLifecycleService();
        /** @var PostingRuleVersion&\Mockery\MockInterface $version */
        $version = Mockery::mock(PostingRuleVersion::class)->makePartial();
        $version->status = 'draft';
        $version->shouldReceive('update')->once()->andReturnTrue();
        $version->shouldReceive('refresh')->once()->andReturnSelf();

        $result = $service->transition($version, 'review');

        $this->assertSame($version, $result);
    }

    public function test_rejects_invalid_transition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $service = new PostingRuleLifecycleService();
        /** @var PostingRuleVersion&\Mockery\MockInterface $version */
        $version = Mockery::mock(PostingRuleVersion::class)->makePartial();
        $version->status = 'draft';

        $service->transition($version, 'active');
    }
}

