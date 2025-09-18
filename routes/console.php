<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\WbApiClient;
use App\Models\WbOrder;
use App\Models\WbSale;
use App\Models\WbStock;
use App\Models\WbIncome;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('wb:fetch {entity} {--dateFrom=} {--dateTo=}', function () {
    $entity = (string) $this->argument('entity');
    $dateFrom = $this->option('dateFrom') ?: now()->toDateString();
    $dateTo = $this->option('dateTo') ?: $dateFrom;

    $this->info("Fetching {$entity} from {$dateFrom} to {$dateTo}...");

    $client = new WbApiClient();

    $map = [
        'orders'  => [WbOrder::class, 'orders'],
        'sales'   => [WbSale::class,  'sales'],
        'stocks'  => [WbStock::class, 'stocks'],
        'incomes' => [WbIncome::class,'incomes'],
    ];

    if (!isset($map[$entity])) {
        $this->error('Unknown entity. Use: orders|sales|stocks|incomes');
        return 1;
    }

    [$modelClass, $endpoint] = $map[$entity];

    $count = 0;
    foreach ($client->iterate($endpoint, [
        'dateFrom' => $dateFrom,
        'dateTo'   => $dateTo,
    ]) as $item) {
        // Try to determine identifiers safely
        $externalId = (string) ($item['id'] ?? $item['order_id'] ?? $item['nmId'] ?? $item['barcode'] ?? md5(json_encode($item)));
        $date = (string) ($item['date'] ?? $item['created_at'] ?? $item['lastChangeDate'] ?? $dateFrom);
        $date = substr($date, 0, 10); // Y-m-d from possible datetime

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelClass();
        $model::updateOrCreate(
            ['external_id' => $externalId, 'date' => $date],
            [
                'amount'  => isset($item['amount']) ? (float) $item['amount'] : (isset($item['totalPrice']) ? (float) $item['totalPrice'] : null),
                'qty'     => isset($item['qty']) ? (int) $item['qty'] : (isset($item['quantity']) ? (int) $item['quantity'] : null),
                'status'  => $item['status'] ?? null,
                'payload' => json_encode($item, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION),
            ]
        );
        $count++;
        if ($count % 100 === 0) {
            $this->info("Saved {$count} records...");
        }
    }

    $this->info("Done. Saved {$count} {$entity} records.");
    return 0;
})->purpose('Fetch WB API data into DB');
