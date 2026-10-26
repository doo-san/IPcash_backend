<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\AiSupportBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSupportBudgetTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $phone): Account
    {
        return Account::create(['phone_number' => $phone]);
    }

    public function test_each_account_is_capped_per_day(): void
    {
        config(['services.anthropic.daily_limit_per_account' => 2, 'services.anthropic.daily_limit_global' => 100]);
        $budget = new AiSupportBudget;
        $account = $this->account('+221770000401');

        $this->assertTrue($budget->tryConsume($account));
        $this->assertTrue($budget->tryConsume($account));
        $this->assertFalse($budget->tryConsume($account));
        $this->assertTrue($budget->tryConsume($this->account('+221770000402')));
    }

    public function test_the_whole_app_is_capped_per_day(): void
    {
        config(['services.anthropic.daily_limit_per_account' => 10, 'services.anthropic.daily_limit_global' => 2]);
        $budget = new AiSupportBudget;

        $this->assertTrue($budget->tryConsume($this->account('+221770000403')));
        $this->assertTrue($budget->tryConsume($this->account('+221770000404')));
        $this->assertFalse($budget->tryConsume($this->account('+221770000405')));
    }

    public function test_an_account_over_its_cap_does_not_eat_the_global_budget(): void
    {
        config(['services.anthropic.daily_limit_per_account' => 1, 'services.anthropic.daily_limit_global' => 2]);
        $budget = new AiSupportBudget;
        $greedy = $this->account('+221770000406');

        $this->assertTrue($budget->tryConsume($greedy));
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($budget->tryConsume($greedy));
        }
        $this->assertTrue($budget->tryConsume($this->account('+221770000407')));
    }

    public function test_counters_reset_the_next_day(): void
    {
        config(['services.anthropic.daily_limit_per_account' => 1, 'services.anthropic.daily_limit_global' => 100]);
        $budget = new AiSupportBudget;
        $account = $this->account('+221770000408');

        $this->assertTrue($budget->tryConsume($account));
        $this->assertFalse($budget->tryConsume($account));

        $this->travel(1)->days();
        $this->assertTrue($budget->tryConsume($account));
    }
}
