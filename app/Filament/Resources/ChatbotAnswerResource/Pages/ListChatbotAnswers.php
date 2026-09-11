<?php

namespace App\Filament\Resources\ChatbotAnswerResource\Pages;

use App\Filament\Resources\ChatbotAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChatbotAnswers extends ListRecords
{
    protected static string $resource = ChatbotAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
