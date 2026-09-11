<?php

namespace App\Enums;

// Miroir exact de `TransactionType` dans api/openapi.yaml.
enum TransactionType: string
{
    case TransferIn = 'transferIn';
    case TransferOut = 'transferOut';
    case CashIn = 'cashIn';
    case CashOut = 'cashOut';
    case CardPayment = 'cardPayment';
    case MerchantPayment = 'merchantPayment';
    case BillPayment = 'billPayment';
    case CreditPurchase = 'creditPurchase';
    case PocketTransferIn = 'pocketTransferIn';
    case PocketTransferOut = 'pocketTransferOut';
    case CardTopUp = 'cardTopUp';
    case CardWithdrawal = 'cardWithdrawal';
    case ForeignExchangeIn = 'foreignExchangeIn';
    case ForeignExchangeOut = 'foreignExchangeOut';
    case ForeignTransferOut = 'foreignTransferOut';
    case InsurancePurchase = 'insurancePurchase';
    case EsimActivation = 'esimActivation';
}
