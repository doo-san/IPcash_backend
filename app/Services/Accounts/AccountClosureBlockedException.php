<?php

namespace App\Services\Accounts;

use RuntimeException;

class AccountClosureBlockedException extends RuntimeException
{
    /** @param list<string> $blockers */
    public function __construct(public readonly array $blockers)
    {
        parent::__construct('Clôture impossible : '.implode(', ', $blockers));
    }
}
