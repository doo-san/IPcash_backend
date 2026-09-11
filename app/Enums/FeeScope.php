<?php

namespace App\Enums;

enum FeeScope: string
{
    case P2pTransfer = 'p2pTransfer';
    case CashIn = 'cashIn';
    case CashOut = 'cashOut';
    case BillPayment = 'billPayment';
    case CardTopUp = 'cardTopUp';
    // Conversion XOF <-> sous-compte devise (IPchange / CashChange).
    case ForeignExchange = 'foreignExchange';
    // Achat d'assurance auto/moto.
    case Insurance = 'insurance';

    // Libellé lisible pour l'admin (FeeRuleResource).
    public function label(): string
    {
        return match ($this) {
            self::P2pTransfer => 'Transfert P2P',
            self::CashIn => 'Dépôt mobile money',
            self::CashOut => 'Retrait mobile money',
            self::BillPayment => 'Paiement de facture',
            self::CardTopUp => 'Recharge de carte',
            self::ForeignExchange => 'IPchange (conversion devise)',
            self::Insurance => 'Assurance',
        };
    }
}
