<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Area;
use App\Models\Customer;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('import_csv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalDescription(
                    'CSV must have columns: phone, name, email, address, area, notes (header row required). '
                    . 'Existing customers (matched by phone) will be updated.'
                )
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV file')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->preserveFilenames(false)
                        ->storeFileNamesIn('original_name'),
                ])
                ->action(function (array $data) {
                    $this->processImport($data['file']);
                }),

            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->streamCustomersCsv()),
        ];
    }

    /* ---------- Import ---------- */

    protected function processImport(string $relativePath): void
    {
        $absolute = Storage::disk('local')->path($relativePath);
        if (! file_exists($absolute)) {
            Notification::make()->title('File not found')->danger()->send();
            return;
        }

        $handle = fopen($absolute, 'r');
        if (! $handle) {
            Notification::make()->title('Could not open file')->danger()->send();
            return;
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            Notification::make()->title('Empty CSV')->danger()->send();
            return;
        }

        // Normalise header keys to lowercase
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        // Pre-build a name-keyed area map
        $areas = Area::pluck('id', 'name')->mapWithKeys(
            fn ($v, $k) => [strtolower((string) $k) => $v]
        )->all();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $row     = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $row++;
            if (count($cells) < count($header)) {
                $cells = array_pad($cells, count($header), null);
            }
            $data = array_combine($header, $cells);

            $phone = trim((string) ($data['phone'] ?? ''));
            $name  = trim((string) ($data['name']  ?? ''));
            if (! $phone || ! $name) { $skipped++; continue; }

            $areaId = null;
            if (! empty($data['area'])) {
                $areaId = $areas[strtolower(trim($data['area']))] ?? null;
            }

            $exists = Customer::where('phone', $phone)->exists();

            Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'name'      => $name,
                    'email'     => $data['email']   ?? null,
                    'address'   => $data['address'] ?? null,
                    'area_id'   => $areaId,
                    'notes'     => $data['notes']   ?? null,
                    'is_active' => true,
                ]
            );

            $exists ? $updated++ : $created++;
        }

        fclose($handle);
        Storage::disk('local')->delete($relativePath);

        Notification::make()
            ->title('Import complete')
            ->body("Created: {$created}, Updated: {$updated}, Skipped: {$skipped}")
            ->success()
            ->send();
    }

    /* ---------- Export ---------- */

    protected function streamCustomersCsv()
    {
        $filename = 'customers_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'phone', 'name', 'email', 'address', 'area',
                'total_orders', 'total_spend', 'avg_order_value',
                'last_order_at', 'segment',
            ]);

            Customer::with('area')->orderBy('id')->lazy(500)->each(function (Customer $c) use ($out) {
                fputcsv($out, [
                    $c->phone,
                    $c->name,
                    $c->email,
                    str_replace(["\r", "\n"], ' ', (string) $c->address),
                    $c->area?->name,
                    $c->total_orders,
                    number_format((float) $c->total_spend, 2, '.', ''),
                    number_format((float) $c->avg_order_value, 2, '.', ''),
                    $c->last_order_at?->format('Y-m-d H:i:s'),
                    $c->segment_label,
                ]);
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
