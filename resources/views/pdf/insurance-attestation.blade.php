@php
    $isAuto = $policy->insurance_type === \App\Enums\InsuranceType::Auto;
    $usage = ['personal' => 'Usage personnel', 'professional' => 'Usage professionnel'][$policy->vehicle_usage] ?? $policy->vehicle_usage;
    $ages = [
        'lessThan3' => 'Moins de 3 ans',
        'threeToFive' => 'De 3 à 5 ans',
        'fiveToTen' => 'De 5 à 10 ans',
        'moreThanTen' => '10 ans et plus',
    ];
    $bodyTypes = ['moto' => 'Moto', 'triporteur' => 'Triporteur'];
    $insured = $account->fullName() ?? $account->phone_number;
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Attestation d'assurance {{ $policy->policy_number }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 11px; color: #1a1a1a; margin: 28px 36px; }
        h1 { font-size: 18px; margin: 0 0 4px; letter-spacing: 1px; }
        .subtitle { color: #555; margin: 0 0 18px; }
        .box { border: 1px solid #222; padding: 10px 12px; margin-bottom: 12px; }
        .box h2 { font-size: 11px; text-transform: uppercase; margin: 0 0 8px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        td.label { width: 42%; color: #555; }
        td.value { font-weight: bold; }
        .period { font-size: 13px; }
        .guarantees { white-space: pre-line; }
        .footer { margin-top: 18px; color: #555; font-size: 9.5px; }
    </style>
</head>
<body>
    <h1>ATTESTATION D'ASSURANCE</h1>
    <p class="subtitle">{{ $isAuto ? 'Véhicule automobile' : 'Deux-roues / triporteur' }} — N° de police {{ $policy->policy_number }}</p>

    <div class="box">
        <h2>Assureur et intermédiaire</h2>
        <table>
            <tr><td class="label">Compagnie d'assurance</td><td class="value">{{ $insurerName }}</td></tr>
            <tr><td class="label">Intermédiaire</td><td class="value">{{ $intermediaryName }}</td></tr>
            <tr><td class="label">N° d'agrément</td><td class="value">{{ $agreementNumber }}</td></tr>
        </table>
    </div>

    <div class="box">
        <h2>Période de validité</h2>
        <p class="period">Du <strong>{{ $policy->starts_at->format('d/m/Y') }}</strong> au <strong>{{ $policy->ends_at->format('d/m/Y') }}</strong> inclus ({{ $policy->duration_months }} mois)</p>
    </div>

    <div class="box">
        <h2>Assuré</h2>
        <table>
            <tr><td class="label">Nom</td><td class="value">{{ $insured }}</td></tr>
            <tr><td class="label">Téléphone</td><td class="value">{{ $account->phone_number }}</td></tr>
        </table>
    </div>

    <div class="box">
        <h2>Véhicule assuré</h2>
        <table>
            <tr><td class="label">Immatriculation</td><td class="value">{{ $policy->plate_number }}</td></tr>
            @if ($policy->vehicle_brand)
                <tr><td class="label">Marque</td><td class="value">{{ $policy->vehicle_brand }}</td></tr>
            @endif
            @if ($isAuto)
                <tr><td class="label">Puissance fiscale</td><td class="value">{{ $policy->horsepower }} CV</td></tr>
                <tr><td class="label">Nombre de places</td><td class="value">{{ $policy->seats }}</td></tr>
                <tr><td class="label">Âge du véhicule</td><td class="value">{{ $ages[$policy->age_bracket] ?? '—' }}</td></tr>
            @else
                <tr><td class="label">Type</td><td class="value">{{ $bodyTypes[$policy->body_type] ?? '—' }}</td></tr>
                <tr><td class="label">Cylindrée</td><td class="value">{{ $policy->displacement_cc }} cm³</td></tr>
            @endif
            <tr><td class="label">Usage</td><td class="value">{{ $usage }}</td></tr>
        </table>
    </div>

    <div class="box">
        <h2>Garanties</h2>
        <p class="guarantees">{{ $guarantees }}</p>
    </div>

    <p class="footer">
        Attestation émise le {{ $policy->reviewed_at->format('d/m/Y à H:i') }} par {{ $intermediaryName }}
        (agrément n° {{ $agreementNumber }}) pour le compte de {{ $insurerName }}.
    </p>
</body>
</html>
