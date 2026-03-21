<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">

<div class="credit-card {$card->getCardBanner()} selectable" data-card-id="{$card->id}">
    <div class="credit-card-last4">
        {substr($card->cardnumber, -4)}
    </div>
    <div class="credit-card-expiry">
        {sprintf("%02d", $card->expmonth)}/{$card->expyear}
    </div>
</div>