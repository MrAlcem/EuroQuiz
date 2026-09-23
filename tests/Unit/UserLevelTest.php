<?php

namespace Tests\Unit;

use App\UserLevel;
use PHPUnit\Framework\TestCase;

class UserLevelTest extends TestCase
{
    public function test_from_score_is_beginner_below_the_pro_threshold(): void
    {
        $this->assertSame(UserLevel::Beginner, UserLevel::fromScore(0));
        $this->assertSame(UserLevel::Beginner, UserLevel::fromScore(99));
    }

    public function test_from_score_is_pro_between_the_pro_and_expert_thresholds(): void
    {
        $this->assertSame(UserLevel::Pro, UserLevel::fromScore(100));
        $this->assertSame(UserLevel::Pro, UserLevel::fromScore(299));
    }

    public function test_from_score_is_expert_at_or_above_the_expert_threshold(): void
    {
        $this->assertSame(UserLevel::Expert, UserLevel::fromScore(300));
        $this->assertSame(UserLevel::Expert, UserLevel::fromScore(1000));
    }
}
