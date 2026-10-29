<?php

namespace App\Filament\Resources\HeroSlideResource\Pages;

use App\Filament\Resources\HeroSlideResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHeroSlide extends CreateRecord
{
    protected static string $resource = HeroSlideResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['type'] ?? 'image') === 'video') {
            $data['src'] = $data['video_url'] ?? '';
            $posterType = $data['poster_source_type'] ?? 'upload';
            $posterFile = is_array($data['poster_file'] ?? null) ? (array_first($data['poster_file']) ?? null) : ($data['poster_file'] ?? null);
            $data['poster'] = ($posterType === 'upload' && !empty($posterFile))
                ? $posterFile
                : ($data['poster_url'] ?? null);
        } else {
            $sourceType = $data['source_type'] ?? 'upload';
            $srcFile = is_array($data['src_file'] ?? null) ? (array_first($data['src_file']) ?? null) : ($data['src_file'] ?? null);
            $data['src'] = ($sourceType === 'upload' && !empty($srcFile))
                ? $srcFile
                : ($data['src_url'] ?? '');
            $data['poster'] = null;
        }

        $data['label'] = $data['label'] ?? '';

        unset($data['src_file'], $data['src_url'], $data['video_url'], $data['source_type'], $data['poster_file'], $data['poster_url'], $data['poster_source_type']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
