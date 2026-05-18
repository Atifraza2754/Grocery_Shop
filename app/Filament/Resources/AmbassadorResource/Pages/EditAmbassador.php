<?php

namespace App\Filament\Resources\AmbassadorResource\Pages;

use App\Filament\Resources\AmbassadorResource;
use App\Models\Commission;
use App\Models\CommissionPlan;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditAmbassador extends EditRecord
{
    protected static string $resource = AmbassadorResource::class;

    protected function getHeaderActions(): array
    {
        return [



            /* ====================== PAY OUT ====================== */
            Actions\Action::make('pay_out')
                ->label('Pay out')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn () => $this->record->commission_pending > 0)
                ->modalHeading(fn () => 'Pay '
                    . $this->record->name
                    . ' — total remaining Rs '
                    . number_format($this->record->commission_pending, 2))
                ->modalDescription('Enter the amount you are paying now. Partial payments are allowed; oldest commissions are settled first.')
                ->form([
                    Forms\Components\TextInput::make('pay_amount')
                        ->label('Pay amount (Rs)')
                        ->numeric()
                        ->required()
                        ->prefix('Rs')
                        ->minValue(0.01)
                        ->step(0.01),

                    Forms\Components\Select::make('paid_method')
                        ->label('Method')
                        ->options([
                            'cash'     => 'Cash',
                            'transfer' => 'Bank Transfer',
                            'mobile'   => 'JazzCash / EasyPaisa',
                            'other'    => 'Other',
                        ])
                        ->default('cash')
                        ->required(),

                    Forms\Components\Textarea::make('note')->rows(2),
                ])
                ->action(function (array $data) {
                    $applied = Commission::applyAmbassadorPayment(
                        $this->record->id,
                        (float) $data['pay_amount'],
                        $data['paid_method'] ?? null,
                        $data['note'] ?? null,
                    );

                    Notification::make()
                        ->title('Paid out Rs ' . number_format($applied, 2))
                        ->body('Remaining: Rs ' . number_format($this->record->fresh()->commission_pending, 2))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
