<?php

namespace App\Enums;

// Trois états + reversed — un timeout côté client n'est JAMAIS traduit en
// `failed` ici (voir CLAUDE.md règle 3) : tant que le traitement n'est pas
// tranché, le statut reste `pending`.
enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
