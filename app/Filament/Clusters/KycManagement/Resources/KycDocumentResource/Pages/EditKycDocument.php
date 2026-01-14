<?php

namespace App\Filament\Clusters\KycManagement\Resources\KycDocumentResource\Pages;

use App\Filament\Clusters\KycManagement\Resources\KycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Notifications\KycStatusUpdated;

class EditKycDocument extends EditRecord
{
    protected static string $resource = KycDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $originalStatus = $record->status;
        
        $record->update($data);
        
        if ($originalStatus !== $record->status && $record->user) {
            $record->user->notify(new KycStatusUpdated($record->status, $record->rejection_reason));
            
            // If approved, update user's verification timestamp if not set
            if ($record->status === 'approved' && !$record->user->kyc_verified_at) {
                $record->user->update(['kyc_verified_at' => now()]);
                
                 // Update control fields
                 $record->user->update([
                     'can_deposit' => true,
                     'can_withdraw' => true,
                     'can_transfer' => true,
                     'can_request' => true
                 ]);
            }
        }

        return $record;
    }
}
